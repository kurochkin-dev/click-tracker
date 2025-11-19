<?php

declare(strict_types=1);

namespace App\Domain\Events;

use Redis;

readonly class EventIngestService
{
    public function __construct(
        private Redis          $redis,
        private EventValidator $validator,
        private string         $stream,
        private int            $maxlen
    )
    {
    }

    /**
     * Ingests events into Redis Streams
     *
     * @param array<int, array<string, mixed>> $events
     * @param string|null $idempotencyKey
     * @return array{accepted: int, skipped: int, errors: array<int, array<string, mixed>>|object, idempotent: bool}
     */
    public function ingest(array $events, ?string $idempotencyKey = null): array
    {
        $nowMs = (int)floor(microtime(true) * 1000);
        $valid = [];
        $errors = [];

        foreach ($events as $idx => $event) {
            $error = $this->validator->validate($event);
            if ($error !== null) {
                $errors[$idx] = $error;
                continue;
            }

            $valid[] = $this->validator->normalize($event, $nowMs);
        }

        if (empty($valid)) {
            return [
                'accepted' => 0,
                'skipped' => count($events),
                'errors' => $errors,
                'idempotent' => false,
            ];
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $idemResult = $this->checkIdempotency($idempotencyKey);
            if ($idemResult['idempotent']) {
                return $idemResult;
            }
        }

        $accepted = 0;
        foreach ($valid as $event) {
            $payload = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->redis->xAdd($this->stream, '*', ['payload' => $payload], $this->maxlen, true);
            $accepted++;
        }

        return [
            'accepted' => $accepted,
            'skipped' => count($events) - $accepted,
            'errors' => $errors ?: (object)[],
            'idempotent' => false,
        ];
    }

    /**
     * Checks idempotency key and returns result
     *
     * @param string $key
     * @return array{accepted: int, skipped: int, errors: object, idempotent: bool}|array{idempotent: bool}
     */
    private function checkIdempotency(string $key): array
    {
        $idemRedisKey = 'idem:' . sha1($key);
        $stored = $this->redis->set($idemRedisKey, '1', ['nx', 'ex' => 300]);

        if ($stored === false) {
            return [
                'accepted' => 0,
                'skipped' => 0,
                'errors' => (object)[],
                'idempotent' => true,
            ];
        }

        return ['idempotent' => false];
    }
}
