<?php

declare(strict_types=1);

namespace App\Domain\Events;

use PDO;

/**
 * Репозиторий агрегированных событий.
 */
readonly class EventsRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Инкрементирует суточный счётчик события или создаёт новую запись.
     *
     * @param string      $date        Дата в формате Y-m-d
     * @param string      $userId      ID пользователя
     * @param string|null $goodId      ID объявления или null
     * @param string      $action      Тип действия (contact_reveal, good_view и т.д.)
     * @param string|null $city        Город пользователя
     * @param string|null $country     Страна пользователя (ISO 3166-1 alpha-2)
     * @param string|null $source      Источник перехода
     */
    public function incrementDailyCount(
        string $date,
        string $userId,
        ?string $goodId,
        string $action,
        ?string $city = null,
        ?string $country = null,
        ?string $source = null,
    ): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO events_agg_daily (date, user_id, good_id, action, city, country, source, count)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");
        $stmt->execute([$date, $userId, $goodId, $action, $city, $country, $source]);
    }

    /**
     * Считает уникальных пользователей для объявления на указанную дату.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     * @return int Количество уникальных пользователей
     */
    public function countUniqueUsers(string $goodId, string $date): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT user_id) as count
            FROM events_agg_daily
            WHERE good_id = ? AND date = ?
        ");
        $stmt->execute([$goodId, $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Возвращает суточную статистику событий, агрегированную по дате.
     *
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array<int, array{date: string, total_events: int, good_views: int, contact_reveals: int, profile_views: int, message_sends: int}> Суточная статистика
     */
    public function getDailyStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "
            SELECT
                date,
                SUM(count) as total_events,
                SUM(CASE WHEN action = 'good_view'       THEN count ELSE 0 END) as good_views,
                SUM(CASE WHEN action = 'contact_reveal'  THEN count ELSE 0 END) as contact_reveals,
                SUM(CASE WHEN action = 'profile_view'    THEN count ELSE 0 END) as profile_views,
                SUM(CASE WHEN action = 'message_send'    THEN count ELSE 0 END) as message_sends
            FROM events_agg_daily
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

        $sql .= ' GROUP BY date ORDER BY date ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'date'            => $row['date'],
                'total_events'    => (int)$row['total_events'],
                'good_views'      => (int)$row['good_views'],
                'contact_reveals' => (int)$row['contact_reveals'],
                'profile_views'   => (int)$row['profile_views'],
                'message_sends'   => (int)$row['message_sends'],
            ];
        }, $results);
    }

    /**
     * Возвращает топ стран/городов по количеству событий.
     *
     * @param string|null $dateFrom  Начальная дата в формате Y-m-d
     * @param string|null $dateTo    Конечная дата в формате Y-m-d
     * @param string|null $action    Фильтр по типу действия
     * @param int         $limit     Максимальное количество строк
     * @return array<int, array{country: string|null, city: string|null, events: int}> Гео-разбивка
     */
    public function getGeoStats(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $action = null,
        int $limit = 20
    ): array {
        $sql = "
            SELECT
                country,
                city,
                SUM(count) as events
            FROM events_agg_daily
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

        if ($action !== null) {
            $sql .= ' AND action = ?';
            $params[] = $action;
        }

        $sql .= " GROUP BY country, city ORDER BY events DESC LIMIT {$limit}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            return [
                'country' => $row['country'],
                'city'    => $row['city'],
                'events'  => (int)$row['events'],
            ];
        }, $results);
    }
}
