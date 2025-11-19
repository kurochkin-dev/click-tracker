<?php

declare(strict_types=1);

namespace App\Queue;

use RuntimeException;
use Throwable;

readonly class RedisStreamClient
{
    public function __construct(
        private \Redis $redis,
        private string $stream,
        private string $group,
        private string $consumer
    )
    {
    }

    /**
     * Creates consumer group if it doesn't exist
     *
     * @throws Throwable
     */
    public function createGroupIfNotExists(): void
    {
        try {
            $this->redis->xGroup('CREATE', $this->stream, $this->group, '0', true);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'BUSYGROUP')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Reads messages from Redis Streams (new messages first, then claims old pending)
     *
     * @param int $count Maximum number of messages to read
     * @return array{id: string, payload: array<string, mixed>}|null Returns message or null if no messages
     * @throws RuntimeException If Redis connection is lost
     */
    public function read(int $count = 1): ?array
    {
        try {
            $this->redis->ping();
        } catch (\RedisException $e) {
            throw new RuntimeException("Redis connection lost: " . $e->getMessage(), 0, $e);
        }

        $message = $this->readNew($count);
        if ($message !== null) {
            return $message;
        }

        return $this->claimOldPending($count);
    }

    /**
     * Reads new messages (not yet delivered)
     *
     * @param int $count
     * @return array{id: string, payload: array<string, mixed>}|null
     */
    private function readNew(int $count): ?array
    {
        try {
            $result = $this->redis->xReadGroup(
                $this->group,
                $this->consumer,
                [$this->stream => '>'],
                $count,
                0
            );
        } catch (\RedisException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'timeout') ||
                str_contains($message, 'empty') ||
                str_contains($message, 'read error')) {
                return null;
            }
            throw $e;
        }

        return $this->parseResult($result);
    }

    /**
     * Claims old pending messages (older than 60 seconds) for current consumer
     *
     * @param int $count
     * @return array{id: string, payload: array<string, mixed>}|null
     */
    private function claimOldPending(int $count): ?array
    {
        try {
            $minIdleTime = 60000;
            $start = '0';
            $result = $this->redis->xAutoClaim(
                $this->stream,
                $this->group,
                $this->consumer,
                $minIdleTime,
                $start,
                $count
            );
        } catch (\RedisException) {
            return null;
        }

        if (empty($result) || !is_array($result) || !isset($result[1]) || !is_array($result[1])) {
            return null;
        }

        $messages = $result[1];
        if (empty($messages)) {
            return null;
        }

        return $this->parseMessages($messages);
    }

    /**
     * Parses Redis Streams result into message format
     *
     * @param array<string, array<string, array<string, string>>>|false $result
     * @return array{id: string, payload: array<string, mixed>}|null
     */
    private function parseResult(array|false $result): ?array
    {
        if (empty($result) || !is_array($result) || !isset($result[$this->stream])) {
            return null;
        }

        $messages = $result[$this->stream];
        if (empty($messages) || !is_array($messages)) {
            return null;
        }

        return $this->parseMessages($messages);
    }

    /**
     * Parses messages array into message format
     *
     * @param array<string, array<string, string>> $messages
     * @return array{id: string, payload: array<string, mixed>}|null
     */
    private function parseMessages(array $messages): ?array
    {
        $id = array_key_first($messages);
        if ($id === null) {
            return null;
        }

        $data = $messages[$id];
        if (!is_array($data) || !isset($data['payload'])) {
            return null;
        }

        $payload = json_decode((string)$data['payload'], true);
        if (!is_array($payload)) {
            return null;
        }

        return ['id' => $id, 'payload' => $payload];
    }

    /**
     * Acknowledges message processing
     *
     * @param string $messageId Message ID to acknowledge
     */
    public function ack(string $messageId): void
    {
        $this->redis->xAck($this->stream, $this->group, [$messageId]);
    }
}
