<?php

declare(strict_types=1);

namespace App\Domain\Reports;

use App\Domain\Goods\GoodStatsRepository;
use App\Domain\Events\EventsRepository;
use Redis;

/**
 * Сервис формирования аналитических отчётов.
 * Все отчёты кешируются в Redis.
 */
readonly class ReportsService
{
    public function __construct(
        private GoodStatsRepository $goodStatsRepository,
        private EventsRepository    $eventsRepository,
        private Redis               $redis,
        private int                 $cacheTtl
    ) {
    }

    /**
     * Возвращает отчёт по конкретному объявлению с кешированием.
     *
     * @param string      $goodId   ID объявления
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array{good_id: string, period: array{from: string|null, to: string|null}, stats: array{good_views: int, contact_reveals: int, profile_views: int, message_sends: int, unique_users: int}, daily: array<int, array{date: string, good_views: int, contact_reveals: int, profile_views: int, message_sends: int, unique_users: int}>} Отчёт по объявлению
     */
    public function getGoodReport(string $goodId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('good', $goodId, $dateFrom, $dateTo);
        $cached   = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $dailyStats = $this->goodStatsRepository->getGoodStats($goodId, $dateFrom, $dateTo);

        $totals = ['good_views' => 0, 'contact_reveals' => 0, 'profile_views' => 0, 'message_sends' => 0, 'unique_users' => 0];
        foreach ($dailyStats as $stat) {
            $totals['good_views']      += $stat['good_views'];
            $totals['contact_reveals'] += $stat['contact_reveals'];
            $totals['profile_views']   += $stat['profile_views'];
            $totals['message_sends']   += $stat['message_sends'];
            $totals['unique_users']     = max($totals['unique_users'], $stat['unique_users']);
        }

        $report = [
            'good_id' => $goodId,
            'period'  => ['from' => $dateFrom, 'to' => $dateTo],
            'stats'   => $totals,
            'daily'   => $dailyStats,
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Возвращает сводный отчёт по всем объявлениям с кешированием.
     *
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array{period: array{from: string|null, to: string|null}, goods: array<int, array{good_id: string, good_views: int, contact_reveals: int, profile_views: int, message_sends: int, unique_users: int}>, total: array{good_views: int, contact_reveals: int, profile_views: int, message_sends: int}} Сводный отчёт по всем объявлениям
     */
    public function getAllGoodsReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('goods', 'all', $dateFrom, $dateTo);
        $cached   = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $goodsStats = $this->goodStatsRepository->getAllGoodsStats($dateFrom, $dateTo);

        $total = ['good_views' => 0, 'contact_reveals' => 0, 'profile_views' => 0, 'message_sends' => 0];
        foreach ($goodsStats as $stat) {
            $total['good_views']      += $stat['good_views'];
            $total['contact_reveals'] += $stat['contact_reveals'];
            $total['profile_views']   += $stat['profile_views'];
            $total['message_sends']   += $stat['message_sends'];
        }

        $report = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'goods'  => $goodsStats,
            'total'  => $total,
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Возвращает суточный отчёт активности с кешированием.
     *
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @return array{period: array{from: string|null, to: string|null}, daily: array<int, array{date: string, total_events: int, good_views: int, contact_reveals: int, profile_views: int, message_sends: int}>} Суточный отчёт
     */
    public function getDailyReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cacheKey = $this->buildCacheKey('daily', 'all', $dateFrom, $dateTo);
        $cached   = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $report = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'daily'  => $this->eventsRepository->getDailyStats($dateFrom, $dateTo),
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Возвращает гео-отчёт: топ стран и городов по событиям.
     *
     * @param string|null $dateFrom Начальная дата в формате Y-m-d
     * @param string|null $dateTo   Конечная дата в формате Y-m-d
     * @param string|null $action   Фильтр по типу действия
     * @return array{period: array{from: string|null, to: string|null}, action: string|null, geo: array<int, array{country: string|null, city: string|null, events: int}>} Гео-разбивка
     */
    public function getGeoReport(?string $dateFrom = null, ?string $dateTo = null, ?string $action = null): array
    {
        $cacheKey = $this->buildCacheKey('geo', $action ?? 'all', $dateFrom, $dateTo);
        $cached   = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $report = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'action' => $action,
            'geo'    => $this->eventsRepository->getGeoStats($dateFrom, $dateTo, $action),
        ];

        $this->setCache($cacheKey, $report);
        return $report;
    }

    /**
     * Формирует ключ кеша для отчёта.
     *
     * @param string      $type     Тип отчёта
     * @param string      $id       Идентификатор
     * @param string|null $dateFrom Начальная дата
     * @param string|null $dateTo   Конечная дата
     * @return string Ключ кеша
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
     * Получает данные из кеша Redis.
     *
     * @param string $key Ключ кеша
     * @return array<string, mixed>|null Данные или null если не найдено
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
     * Сохраняет данные в кеш Redis.
     *
     * @param string               $key  Ключ кеша
     * @param array<string, mixed> $data Данные для кеширования
     */
    private function setCache(string $key, array $data): void
    {
        try {
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->redis->setex($key, $this->cacheTtl, $encoded);
        } catch (\Throwable) {
            // Не прерываем работу при недоступности кеша
        }
    }
}
