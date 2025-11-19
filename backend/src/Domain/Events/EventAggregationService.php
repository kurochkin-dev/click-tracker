<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\Campaigns\CampaignStatsRepository;
use PDO;
use Throwable;

readonly class EventAggregationService
{
    public function __construct(
        private PDO                     $pdo,
        private EventsRepository        $eventsRepository,
        private CampaignStatsRepository $campaignStatsRepository
    )
    {
    }

    /**
     * Aggregates event data into MySQL tables within a transaction
     *
     * @param array{user_id: string, action: string, campaign_id?: string|null, ts: int} $event
     * @throws Throwable If transaction fails
     */
    public function aggregate(array $event): void
    {
        $date = date('Y-m-d', (int)($event['ts'] / 1000));
        $userId = $event['user_id'];
        $campaignId = $event['campaign_id'] ?? null;
        $action = $event['action'];

        $this->pdo->beginTransaction();

        try {
            $this->eventsRepository->incrementDailyCount($date, $userId, $campaignId, $action);

            if (in_array($action, ['impression', 'click'], true) && $campaignId !== null) {
                if ($action === 'click') {
                    $this->campaignStatsRepository->incrementClicks($campaignId, $date);
                } else {
                    $this->campaignStatsRepository->incrementImpressions($campaignId, $date);
                }

                $uniqueUsers = $this->eventsRepository->countUniqueUsers($campaignId, $date);
                $this->campaignStatsRepository->updateUniqueUsers($campaignId, $date, $uniqueUsers);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
