<?php

declare(strict_types=1);

namespace App\Domain\Events;

/**
 * Валидатор входящих событий.
 * Поддерживает поля: good_id, city, country, source, contact_type.
 */
class EventValidator
{
    /** @var string[] Допустимые типы действий */
    private const array VALID_ACTIONS = [
        'contact_reveal',
        'good_view',
        'profile_view',
        'message_send',
    ];

    /** @var string[] Допустимые источники перехода */
    private const array VALID_SOURCES = [
        'good',
        'profile',
        'search',
        'direct',
        'external',
    ];

    /** @var string[] Допустимые типы контактов */
    private const array VALID_CONTACT_TYPES = [
        'phone',
        'email',
        'telegram',
        'whatsapp',
    ];

    /**
     * Валидирует структуру и данные события.
     *
     * @param array<string, mixed> $event Данные события
     * @return array{msg: string, fields?: array<int, string>}|null Массив с ошибкой или null если валидно
     */
    public function validate(array $event): ?array
    {
        $required = ['user_id', 'action'];
        $missing = array_values(array_diff($required, array_keys($event)));
        if ($missing) {
            return ['msg' => 'missing_fields', 'fields' => $missing];
        }

        if (!is_string($event['user_id']) || $event['user_id'] === '') {
            return ['msg' => 'user_id_invalid'];
        }

        if (!is_string($event['action']) || !in_array($event['action'], self::VALID_ACTIONS, true)) {
            return ['msg' => 'action_invalid', 'fields' => self::VALID_ACTIONS];
        }

        if (isset($event['good_id']) && !is_string($event['good_id'])) {
            return ['msg' => 'good_id_invalid'];
        }

        if (isset($event['ts']) && !is_numeric($event['ts'])) {
            return ['msg' => 'ts_invalid'];
        }

        if (isset($event['country']) && (!is_string($event['country']) || strlen($event['country']) !== 2)) {
            return ['msg' => 'country_invalid', 'fields' => ['must be ISO 3166-1 alpha-2, e.g. RS, UA, DE']];
        }

        if (isset($event['source']) && !in_array($event['source'], self::VALID_SOURCES, true)) {
            return ['msg' => 'source_invalid', 'fields' => self::VALID_SOURCES];
        }

        if (isset($event['contact_type']) && !in_array($event['contact_type'], self::VALID_CONTACT_TYPES, true)) {
            return ['msg' => 'contact_type_invalid', 'fields' => self::VALID_CONTACT_TYPES];
        }

        return null;
    }

    /**
     * Нормализует данные события: добавляет timestamp если не задан.
     *
     * @param array<string, mixed> $event     Данные события
     * @param int                  $defaultTs Дефолтный timestamp в миллисекундах
     * @return array<string, mixed> Нормализованное событие с полем ts
     */
    public function normalize(array $event, int $defaultTs): array
    {
        $event['ts'] = isset($event['ts']) && is_numeric($event['ts'])
            ? (int)$event['ts']
            : $defaultTs;

        return $event;
    }
}
