<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Migrations;

use App\Infrastructure\Database\Migration\Migration;
use PDO;

/**
 * Миграция адаптации схемы БД для хранения аналитики объявлений.
 */
readonly class AdaptForNozhove implements Migration
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Возвращает имя миграции.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'AdaptForNozhove';
    }

    /**
     * Применяет миграцию: пересоздаёт таблицы под nozhove.com.
     */
    public function up(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $this->pdo->exec('DROP TABLE IF EXISTS campaign_stats');
        $this->pdo->exec('DROP TABLE IF EXISTS events_agg_daily');

        $this->pdo->exec("
            CREATE TABLE events_agg_daily (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date        DATE            NOT NULL,
                user_id     VARCHAR(255)    NOT NULL,
                good_id     VARCHAR(255)    NULL COMMENT 'ID объявления',
                action      VARCHAR(50)     NOT NULL COMMENT 'contact_reveal, good_view, profile_view, message_send',
                city        VARCHAR(100)    NULL,
                country     CHAR(2)         NULL COMMENT 'ISO 3166-1 alpha-2',
                source      VARCHAR(20)     NULL COMMENT 'good, profile, search, direct, external',
                contact_type VARCHAR(20)   NULL COMMENT 'phone, email, telegram, whatsapp',
                count       INT UNSIGNED    DEFAULT 1,
                created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_daily (date, user_id, good_id, action, city, country, source),
                KEY idx_date (date),
                KEY idx_good_date (good_id, date),
                KEY idx_user_date (user_id, date),
                KEY idx_country_date (country, date),
                KEY idx_action_date (action, date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec("
            CREATE TABLE good_stats (
                id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                good_id          VARCHAR(255)    NOT NULL COMMENT 'ID объявления',
                date             DATE            NOT NULL,
                good_views       INT UNSIGNED    DEFAULT 0 COMMENT 'Просмотры страницы товара',
                contact_reveals  INT UNSIGNED    DEFAULT 0 COMMENT 'Раскрытий контакта',
                profile_views    INT UNSIGNED    DEFAULT 0 COMMENT 'Просмотры профиля продавца',
                message_sends    INT UNSIGNED    DEFAULT 0 COMMENT 'Отправок сообщений',
                unique_users     INT UNSIGNED    DEFAULT 0,
                updated_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_good_date (good_id, date),
                KEY idx_date (date),
                KEY idx_good (good_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Откатывает миграцию: восстанавливает оригинальные таблицы.
     */
    public function down(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $this->pdo->exec('DROP TABLE IF EXISTS good_stats');
        $this->pdo->exec('DROP TABLE IF EXISTS events_agg_daily');

        $this->pdo->exec("
            CREATE TABLE events_agg_daily (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                date        DATE         NOT NULL,
                user_id     VARCHAR(255) NOT NULL,
                campaign_id VARCHAR(255) NULL,
                action      VARCHAR(50)  NOT NULL,
                count       INT UNSIGNED DEFAULT 1,
                created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_date_user_campaign_action (date, user_id, campaign_id, action),
                KEY idx_date (date),
                KEY idx_campaign_date (campaign_id, date),
                KEY idx_user_date (user_id, date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec("
            CREATE TABLE campaign_stats (
                id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id VARCHAR(255) NOT NULL,
                date        DATE         NOT NULL,
                impressions INT UNSIGNED DEFAULT 0,
                clicks      INT UNSIGNED DEFAULT 0,
                unique_users INT UNSIGNED DEFAULT 0,
                updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_campaign_date (campaign_id, date),
                KEY idx_date (date),
                KEY idx_campaign (campaign_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
