<?php

declare(strict_types=1);

namespace App\Domain\Goods;

use PDO;

/**
 * Репозиторий агрегированной статистики объявлений.
 * Работает с таблицей good_stats.
 */
readonly class GoodStatsRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Инкрементирует счётчик просмотров объявления на указанную дату.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     */
    public function incrementGoodViews(string $goodId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO good_stats (good_id, date, good_views, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE good_views = good_views + 1
        ");
        $stmt->execute([$goodId, $date]);
    }

    /**
     * Инкрементирует счётчик раскрытий контакта для объявления на указанную дату.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     */
    public function incrementContactReveals(string $goodId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO good_stats (good_id, date, contact_reveals, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE contact_reveals = contact_reveals + 1
        ");
        $stmt->execute([$goodId, $date]);
    }

    /**
     * Инкрементирует счётчик просмотров профиля продавца.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     */
    public function incrementProfileViews(string $goodId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO good_stats (good_id, date, profile_views, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE profile_views = profile_views + 1
        ");
        $stmt->execute([$goodId, $date]);
    }

    /**
     * Инкрементирует счётчик отправок сообщений.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     */
    public function incrementMessageSends(string $goodId, string $date): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO good_stats (good_id, date, message_sends, unique_users)
            VALUES (?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE message_sends = message_sends + 1
        ");
        $stmt->execute([$goodId, $date]);
    }

    /**
     * Обновляет количество уникальных пользователей для объявления на дату.
     *
     * @param string $goodId      ID объявления
     * @param string $date        Дата в формате Y-m-d
     * @param int    $uniqueUsers Количество уникальных пользователей
     */
    public function updateUniqueUsers(string $goodId, string $date, int $uniqueUsers): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE good_stats
            SET unique_users = ?
            WHERE good_id = ? AND date = ?
        ");
        $stmt->execute([$uniqueUsers, $goodId, $date]);
    }

    /**
     * Возвращает статистику объявления за диапазон дат.
     *
     * @param string      $goodId   ID объявления
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array<int, array{good_id: string, date: string, good_views: int, contact_reveals: int, profile_views: int, message_sends: int, unique_users: int}> Статистика по дням
     */
    public function getGoodStats(string $goodId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT good_id, date, good_views, contact_reveals, profile_views, message_sends, unique_users
            FROM good_stats
            WHERE good_id = ?
        ";

        $params = [$goodId];

        if ($dateFrom !== null) {
            $sql .= ' AND date >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= ' AND date <= ?';
            $params[] = $dateTo;
        }

        $sql .= ' ORDER BY date ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row): array => [
            'good_id'         => $row['good_id'],
            'date'            => $row['date'],
            'good_views'      => (int)$row['good_views'],
            'contact_reveals' => (int)$row['contact_reveals'],
            'profile_views'   => (int)$row['profile_views'],
            'message_sends'   => (int)$row['message_sends'],
            'unique_users'    => (int)$row['unique_users'],
        ], $results);
    }

    /**
     * Возвращает агрегированную статистику по всем объявлениям.
     *
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array<int, array{good_id: string, good_views: int, contact_reveals: int, profile_views: int, message_sends: int, unique_users: int}> Список объявлений с суммарной статистикой
     */
    public function getAllGoodsStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT
                good_id,
                SUM(good_views)      as good_views,
                SUM(contact_reveals) as contact_reveals,
                SUM(profile_views)   as profile_views,
                SUM(message_sends)   as message_sends,
                MAX(unique_users)    as unique_users
            FROM good_stats
            WHERE 1=1
        ";

        $params = [];

        if ($dateFrom !== null) {
            $sql .= ' AND date >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo !== null) {
            $sql .= ' AND date <= ?';
            $params[] = $dateTo;
        }

        $sql .= ' GROUP BY good_id ORDER BY contact_reveals DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row): array => [
            'good_id'         => $row['good_id'],
            'good_views'      => (int)$row['good_views'],
            'contact_reveals' => (int)$row['contact_reveals'],
            'profile_views'   => (int)$row['profile_views'],
            'message_sends'   => (int)$row['message_sends'],
            'unique_users'    => (int)$row['unique_users'],
        ], $results);
    }
}
