<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Migrations;

use App\Infrastructure\Database\Migration\Migration;
use PDO;

readonly class CreateEventsTables implements Migration
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Returns migration name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'CreateEventsTables';
    }

    /**
     * Applies migration
     */
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS events_agg_daily (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                campaign_id VARCHAR(255) NULL,
                action VARCHAR(50) NOT NULL,
                count INT UNSIGNED DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_date_user_campaign_action (date, user_id, campaign_id, action),
                KEY idx_date (date),
                KEY idx_campaign_date (campaign_id, date),
                KEY idx_user_date (user_id, date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS campaign_stats (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                impressions INT UNSIGNED DEFAULT 0,
                clicks INT UNSIGNED DEFAULT 0,
                unique_users INT UNSIGNED DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_campaign_date (campaign_id, date),
                KEY idx_date (date),
                KEY idx_campaign (campaign_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Rolls back migration
     */
    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS campaign_stats");
        $this->pdo->exec("DROP TABLE IF EXISTS events_agg_daily");
    }
}

