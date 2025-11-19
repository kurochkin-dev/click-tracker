<?php

declare(strict_types=1);

namespace App\Domain\Campaigns;

use PDO;

readonly class CampaignStatsRepository
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    /**
     * Increments clicks count for a campaign on a specific date
     *
     * @param string $campaignId Campaign ID
     * @param string $date Date in Y-m-d format
     */
    public function incrementClicks(string $campaignId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_stats (campaign_id, date, clicks, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE clicks = clicks + 1
        ");
        $stmt->execute([$campaignId, $date]);
    }

    /**
     * Increments impressions count for a campaign on a specific date
     *
     * @param string $campaignId Campaign ID
     * @param string $date Date in Y-m-d format
     */
    public function incrementImpressions(string $campaignId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO campaign_stats (campaign_id, date, impressions, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE impressions = impressions + 1
        ");
        $stmt->execute([$campaignId, $date]);
    }

    /**
     * Updates unique users count for a campaign on a specific date
     *
     * @param string $campaignId Campaign ID
     * @param string $date Date in Y-m-d format
     * @param int $uniqueUsers Number of unique users
     */
    public function updateUniqueUsers(string $campaignId, string $date, int $uniqueUsers): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE campaign_stats
            SET unique_users = ?
            WHERE campaign_id = ? AND date = ?
        ");
        $stmt->execute([$uniqueUsers, $campaignId, $date]);
    }

    /**
     * Gets campaign statistics for a date range
     *
     * @param string $campaignId Campaign ID
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array<int, array{campaign_id: string, date: string, clicks: int, impressions: int, unique_users: int}>
     */
    public function getCampaignStats(string $campaignId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT campaign_id, date, clicks, impressions, unique_users
            FROM campaign_stats
            WHERE campaign_id = ?
        ";

        $params = [$campaignId];

        if ($dateFrom !== null) {
            $sql .= " AND date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= " AND date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY date ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'campaign_id' => $row['campaign_id'],
                'date' => $row['date'],
                'clicks' => (int)$row['clicks'],
                'impressions' => (int)$row['impressions'],
                'unique_users' => (int)$row['unique_users'],
            ];
        }, $results);
    }

    /**
     * Gets statistics for all campaigns
     *
     * @param string|null $dateFrom Start date in Y-m-d format
     * @param string|null $dateTo End date in Y-m-d format
     * @return array<int, array{campaign_id: string, clicks: int, impressions: int, unique_users: int}>
     */
    public function getAllCampaignsStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT 
                campaign_id,
                SUM(clicks) as clicks,
                SUM(impressions) as impressions,
                MAX(unique_users) as unique_users
            FROM campaign_stats
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

        $sql .= " GROUP BY campaign_id ORDER BY clicks DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'campaign_id' => $row['campaign_id'],
                'clicks' => (int)$row['clicks'],
                'impressions' => (int)$row['impressions'],
                'unique_users' => (int)$row['unique_users'],
            ];
        }, $results);
    }
}
