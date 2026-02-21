<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Reports;

use App\Domain\Campaigns\CampaignStatsRepository;
use App\Domain\Events\EventsRepository;
use App\Domain\Reports\ReportsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Redis;

/**
 * Тесты для ReportsService.
 *
 * Проверяет логику агрегации отчётов, расчёт CTR и кэширование в Redis.
 */
class ReportsServiceTest extends TestCase
{
    /** @var CampaignStatsRepository&MockObject */
    private CampaignStatsRepository $campaignStatsRepo;

    /** @var EventsRepository&MockObject */
    private EventsRepository $eventsRepo;

    /** @var Redis&MockObject */
    private Redis $redis;

    private ReportsService $service;

    protected function setUp(): void
    {
        $this->campaignStatsRepo = $this->createMock(CampaignStatsRepository::class);
        $this->eventsRepo        = $this->createMock(EventsRepository::class);
        $this->redis             = $this->createMock(Redis::class);

        $this->service = new ReportsService(
            campaignStatsRepository: $this->campaignStatsRepo,
            eventsRepository:        $this->eventsRepo,
            redis:                   $this->redis,
            cacheTtl:                300,
        );
    }

    /**
     * Проверяет корректный расчёт суммарного CTR для кампании.
     */
    #[Test]
    public function getCampaignReportCalculatesCtrCorrectly(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $this->campaignStatsRepo
            ->method('getCampaignStats')
            ->willReturn([
                ['date' => '2024-11-01', 'clicks' => 100, 'impressions' => 400, 'unique_users' => 80],
                ['date' => '2024-11-02', 'clicks' => 150, 'impressions' => 600, 'unique_users' => 120],
            ]);

        $report = $this->service->getCampaignReport('camp1', '2024-11-01', '2024-11-02');

        self::assertSame('camp1', $report['campaign_id']);
        self::assertSame(250, $report['stats']['clicks']);
        self::assertSame(1000, $report['stats']['impressions']);
        self::assertSame(0.25, $report['stats']['ctr']);
    }

    /**
     * Проверяет, что CTR равен 0.0 при нулевых показах.
     */
    #[Test]
    public function getCampaignReportReturnZeroCtrWhenNoImpressions(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $this->campaignStatsRepo
            ->method('getCampaignStats')
            ->willReturn([
                ['date' => '2024-11-01', 'clicks' => 0, 'impressions' => 0, 'unique_users' => 0],
            ]);

        $report = $this->service->getCampaignReport('camp1');

        self::assertSame(0.0, $report['stats']['ctr']);
    }

    /**
     * Проверяет, что данные возвращаются из кэша без обращения к репозиторию.
     */
    #[Test]
    public function getCampaignReportReturnsCachedData(): void
    {
        $cachedReport = [
            'campaign_id' => 'camp1',
            'period'      => ['from' => '2024-11-01', 'to' => '2024-11-02'],
            'stats'       => ['clicks' => 999, 'impressions' => 1000, 'unique_users' => 500, 'ctr' => 0.999],
            'daily'       => [],
        ];

        $this->redis
            ->method('get')
            ->willReturn(json_encode($cachedReport));

        $this->campaignStatsRepo->expects(self::never())->method('getCampaignStats');

        $report = $this->service->getCampaignReport('camp1', '2024-11-01', '2024-11-02');

        self::assertSame(999, $report['stats']['clicks']);
    }

    /**
     * Проверяет, что daily-блок отчёта содержит правильное количество записей.
     */
    #[Test]
    public function getCampaignReportContainsDailyBreakdown(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $this->campaignStatsRepo
            ->method('getCampaignStats')
            ->willReturn([
                ['date' => '2024-11-01', 'clicks' => 10, 'impressions' => 40, 'unique_users' => 8],
                ['date' => '2024-11-02', 'clicks' => 20, 'impressions' => 80, 'unique_users' => 15],
                ['date' => '2024-11-03', 'clicks' => 5, 'impressions' => 20, 'unique_users' => 4],
            ]);

        $report = $this->service->getCampaignReport('camp1');

        self::assertCount(3, $report['daily']);
        self::assertSame('2024-11-01', $report['daily'][0]['date']);
    }

    /**
     * Проверяет расчёт общих итогов по всем кампаниям.
     */
    #[Test]
    public function getAllCampaignsReportCalculatesTotalCorrectly(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $this->campaignStatsRepo
            ->method('getAllCampaignsStats')
            ->willReturn([
                ['campaign_id' => 'c1', 'clicks' => 100, 'impressions' => 400, 'unique_users' => 80],
                ['campaign_id' => 'c2', 'clicks' => 200, 'impressions' => 800, 'unique_users' => 150],
            ]);

        $report = $this->service->getAllCampaignsReport('2024-11-01', '2024-11-02');

        self::assertSame(300, $report['total']['clicks']);
        self::assertSame(1200, $report['total']['impressions']);
        self::assertCount(2, $report['campaigns']);
    }

    /**
     * Проверяет, что CTR рассчитывается для каждой кампании отдельно.
     */
    #[Test]
    public function getAllCampaignsReportCalculatesCtrPerCampaign(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $this->campaignStatsRepo
            ->method('getAllCampaignsStats')
            ->willReturn([
                ['campaign_id' => 'c1', 'clicks' => 50, 'impressions' => 200, 'unique_users' => 40],
            ]);

        $report = $this->service->getAllCampaignsReport();

        self::assertSame(0.25, $report['campaigns'][0]['ctr']);
    }

    /**
     * Проверяет, что getDailyReport возвращает данные из репозитория событий.
     */
    #[Test]
    public function getDailyReportReturnsDataFromRepository(): void
    {
        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setex')->willReturn(true);

        $dailyData = [
            ['date' => '2024-11-01', 'total_events' => 500, 'clicks' => 100, 'impressions' => 400],
        ];

        $this->eventsRepo
            ->method('getDailyStats')
            ->willReturn($dailyData);

        $report = $this->service->getDailyReport('2024-11-01', '2024-11-01');

        self::assertSame($dailyData, $report['daily']);
        self::assertSame('2024-11-01', $report['period']['from']);
    }
}
