<?php

declare(strict_types=1);

namespace App\Domain\Events;

class EventValidator
{
    /**
     * Validates event structure and data
     *
     * @param array<string, mixed> $event
     * @return array{msg: string, fields?: array<int, string>}|null Returns error array or null if valid
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

        if (!is_string($event['action']) || $event['action'] === '') {
            return ['msg' => 'action_invalid'];
        }

        if (isset($event['campaign_id']) && !is_string($event['campaign_id'])) {
            return ['msg' => 'campaign_id_invalid'];
        }

        if (isset($event['ts']) && !is_numeric($event['ts'])) {
            return ['msg' => 'ts_invalid'];
        }

        return null;
    }

    /**
     * Normalizes event data (adds timestamp if missing)
     *
     * @param array<string, mixed> $event
     * @param int $defaultTs Default timestamp in milliseconds
     * @return array<string, mixed> Normalized event with ts field
     */
    public function normalize(array $event, int $defaultTs): array
    {
        $event['ts'] = isset($event['ts']) && is_numeric($event['ts'])
            ? (int)$event['ts']
            : $defaultTs;

        return $event;
    }
}
