<?php

declare(strict_types=1);

namespace App\Domain\Reports;

use App\Domain\Campaigns\CampaignStatsRepository;
use App\Domain\Events\EventsRepository;
use Redis;

readonly class ReportsService
{
    public function __construct(
        private CampaignStatsRepository $campaignStatsRepository,
        private EventsRepository        $eventsRepository,
        private Redis                   $redis,
        private int                     $cacheTtl
    ) {
    }

    /**
     * Gets campaign report with caching
     *
     * @param string $campaignId Campaign ID
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array{campaign_id: string, period: array{from: string|null, to: string|null}, stats: array{clicks: int, impressions: int, unique_users: int, ctr: float}, daily: array<int, array{date: string, clicks: int, impressions: int, unique_users: int}>}
     */
    public function getCampaignReport(string $campaignId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('campaign', $campaignId, $dateFrom, $dateTo);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dailyStats = $this->campaignStatsRepository->getCampaignStats($campaignId, $dateFrom, $dateTo);

        $totalClicks = 0;
        $totalImpressions = 0;
        $maxUniqueUsers = 0;

        $daily = [];
        foreach ($dailyStats as $stat) {
            $totalClicks += $stat['clicks'];
            $totalImpressions += $stat['impressions'];
            $maxUniqueUsers = max($maxUniqueUsers, $stat['unique_users']);

            $daily[] = [
                'date' => $stat['date'],
                'clicks' => $stat['clicks'],
                'impressions' => $stat['impressions'],
                'unique_users' => $stat['unique_users'],
            ];
        }

        $ctr = $totalImpressions > 0 ? round($totalClicks / $totalImpressions, 4) : 0.0;

        $report = [
            'campaign_id' => $campaignId,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'stats' => [
                'clicks' => $totalClicks,
                'impressions' => $totalImpressions,
                'unique_users' => $maxUniqueUsers,
                'ctr' => $ctr,
            ],
            'daily' => $daily,
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Gets all campaigns report with caching
     *
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array{period: array{from: string|null, to: string|null}, campaigns: array<int, array{campaign_id: string, clicks: int, impressions: int, unique_users: int, ctr: float}>, total: array{clicks: int, impressions: int, unique_users: int}}
     */
    public function getAllCampaignsReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('campaigns', 'all', $dateFrom, $dateTo);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $campaignsStats = $this->campaignStatsRepository->getAllCampaignsStats($dateFrom, $dateTo);

        $totalClicks = 0;
        $totalImpressions = 0;
        $totalUniqueUsers = 0;

        $campaigns = [];
        foreach ($campaignsStats as $stat) {
            $ctr = $stat['impressions'] > 0 ? round($stat['clicks'] / $stat['impressions'], 4) : 0.0;

            $campaigns[] = [
                'campaign_id' => $stat['campaign_id'],
                'clicks' => $stat['clicks'],
                'impressions' => $stat['impressions'],
                'unique_users' => $stat['unique_users'],
                'ctr' => $ctr,
            ];

            $totalClicks += $stat['clicks'];
            $totalImpressions += $stat['impressions'];
            $totalUniqueUsers = max($totalUniqueUsers, $stat['unique_users']);
        }

        $report = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'campaigns' => $campaigns,
            'total' => [
                'clicks' => $totalClicks,
                'impressions' => $totalImpressions,
                'unique_users' => $totalUniqueUsers,
            ],
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Gets daily report with caching
     *
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array{period: array{from: string|null, to: string|null}, daily: array<int, array{date: string, total_events: int, clicks: int, impressions: int}>}
     */
    public function getDailyReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('daily', 'all', $dateFrom, $dateTo);
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dailyStats = $this->eventsRepository->getDailyStats($dateFrom, $dateTo);

        $report = [
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'daily' => $dailyStats,
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Builds cache key for report
     *
     * @param string $type Report type
     * @param string $id Identifier
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return string
     */
    private function buildCacheKey(string $type, string $id, ?string $dateFrom, ?string $dateTo): string
    {
        $parts = ['report', $type, $id];
        if ($dateFrom !== null) {
            $parts[] = $dateFrom;
        }
        if ($dateTo !== null) {
            $parts[] = $dateTo;
        }
        return implode(':', $parts);
    }

    /**
     * Gets data from cache
     *
     * @param string $key Cache key
     * @return array<string, mixed>|null
     */
    private function getFromCache(string $key): ?array
    {
        try {
            $cached = $this->redis->get($key);
            if ($cached === false) {
                return null;
            }

            $decoded = json_decode($cached, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sets data to cache
     *
     * @param string $key Cache key
     * @param array<string, mixed> $data Data to cache
     */
    private function setCache(string $key, array $data): void
    {
        try {
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->redis->setex($key, $this->cacheTtl, $encoded);
        } catch (\Throwable) {
            // Silently fail if cache is unavailable
        }
    }
}

