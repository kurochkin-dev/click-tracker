<?php

declare(strict_types=1);

namespace App\Domain\Events;

use PDO;

readonly class EventsRepository
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    /**
     * Increments daily event count or creates new record
     *
     * @param string $date Date in Y-m-d format
     * @param string $userId User ID
     * @param string|null $campaignId Campaign ID or null
     * @param string $action Action type (e.g., 'click', 'impression')
     */
    public function incrementDailyCount(string $date, string $userId, ?string $campaignId, string $action): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO events_agg_daily (date, user_id, campaign_id, action, count)
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");
        $stmt->execute([$date, $userId, $campaignId, $action]);
    }

    /**
     * Counts unique users for a campaign on a specific date
     *
     * @param string $campaignId Campaign ID
     * @param string $date Date in Y-m-d format
     * @return int Number of unique users
     */
    public function countUniqueUsers(string $campaignId, string $date): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as count
            FROM events_agg_daily
            WHERE campaign_id = ? AND date = ?
        ");
        $stmt->execute([$campaignId, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
}
