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

    /**
     * Gets daily statistics aggregated by date
     *
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array<int, array{date: string, total_events: int, clicks: int, impressions: int}>
     */
    public function getDailyStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT 
                date,
                SUM(count) as total_events,
                SUM(CASE WHEN action = 'click' THEN count ELSE 0 END) as clicks,
                SUM(CASE WHEN action = 'impression' THEN count ELSE 0 END) as impressions
            FROM events_agg_daily
            WHERE 1=1
        ";

        $params = [];

        if ($dateFrom !== null) {
            $sql .= " AND date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= " AND date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY date ORDER BY date ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'date' => $row['date'],
                'total_events' => (int)$row['total_events'],
                'clicks' => (int)$row['clicks'],
                'impressions' => (int)$row['impressions'],
            ];
        }, $results);
    }
}
