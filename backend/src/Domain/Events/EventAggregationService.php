<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\Goods\GoodStatsRepository;
use PDO;
use Throwable;

/**
 * Сервис агрегации событий аналитики.
 * Обрабатывает события из Redis Stream и записывает агрегаты в MySQL.
 */
readonly class EventAggregationService
{
    public function __construct(
        private PDO                  $pdo,
        private EventsRepository     $eventsRepository,
        private GoodStatsRepository  $goodStatsRepository
    ) {
    }

    /**
     * Агрегирует данные события в таблицы MySQL внутри транзакции.
     *
     * @param array{user_id: string, action: string, good_id?: string|null, ts: int, city?: string|null, country?: string|null, source?: string|null} $event Данные события
     * @throws Throwable Если транзакция завершилась с ошибкой
     */
    public function aggregate(array $event): void
    {
        $date    = date('Y-m-d', (int)($event['ts'] / 1000));
        $userId  = $event['user_id'];
        $goodId  = $event['good_id'] ?? null;
        $action  = $event['action'];
        $city    = $event['city'] ?? null;
        $country = $event['country'] ?? null;
        $source  = $event['source'] ?? null;

        $this->pdo->beginTransaction();

        try {
            $this->eventsRepository->incrementDailyCount(
                $date,
                $userId,
                $goodId,
                $action,
                $city,
                $country,
                $source
            );

            if ($goodId !== null) {
                $this->dispatchGoodAction($goodId, $date, $action);

                $uniqueUsers = $this->eventsRepository->countUniqueUsers($goodId, $date);
                $this->goodStatsRepository->updateUniqueUsers($goodId, $date, $uniqueUsers);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Инкрементирует соответствующий счётчик в good_stats в зависимости от типа действия.
     *
     * @param string $goodId ID объявления
     * @param string $date   Дата в формате Y-m-d
     * @param string $action Тип действия
     */
    private function dispatchGoodAction(string $goodId, string $date, string $action): void
    {
        match ($action) {
            'good_view'      => $this->goodStatsRepository->incrementGoodViews($goodId, $date),
            'contact_reveal' => $this->goodStatsRepository->incrementContactReveals($goodId, $date),
            'profile_view'   => $this->goodStatsRepository->incrementProfileViews($goodId, $date),
            'message_send'   => $this->goodStatsRepository->incrementMessageSends($goodId, $date),
            default          => null,
        };
    }
}
