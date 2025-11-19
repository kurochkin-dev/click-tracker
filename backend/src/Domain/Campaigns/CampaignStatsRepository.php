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
}
