<?php

declare(strict_types=1);

namespace App\Worker;

use App\Domain\Events\EventAggregationService;
use App\Queue\RedisStreamClient;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client as MongoClient;
use MongoDB\Database;

class AggregationWorker
{
    private Database $mongoDb;

    public function __construct(
        private readonly RedisStreamClient       $stream,
        private readonly EventAggregationService $aggregationService,
        private readonly MongoClient             $mongo,
        private readonly string                  $mongoDbName
    )
    {
        $this->mongoDb = $this->mongo->selectDatabase($this->mongoDbName);
        $this->ensureMongoTTLIndex();
    }

    /**
     * Ensures TTL index exists on raw_events collection
     */
    private function ensureMongoTTLIndex(): void
    {
        $collection = $this->mongoDb->selectCollection('raw_events');
        try {
            $collection->createIndex(['ts' => 1], ['expireAfterSeconds' => 2592000]);
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), 'already exists')) {
                error_log("Mongo index creation warning: " . $e->getMessage());
            }
        }
    }

    /**
     * Main worker loop - processes events from Redis Streams
     */
    public function process(): void
    {
        try {
            $this->stream->createGroupIfNotExists();
        } catch (\Throwable $e) {
            error_log("Failed to create consumer group: " . $e->getMessage());
            throw $e;
        }

        echo "Worker started. Waiting for events...\n";

        while (true) {
            try {
                $message = $this->stream->read();
                if ($message === null) {
                    usleep(100000);
                    continue;
                }

                echo "Processing event: {$message['id']}\n";
                $this->saveRawEvent($message['payload']);
                $this->aggregationService->aggregate($message['payload']);
                $this->stream->ack($message['id']);
                echo "Processed event: {$message['id']}\n";
                echo "Waiting for next event...\n";
            } catch (\Throwable $e) {
                error_log("Worker error: " . $e->getMessage());
                echo "Error: " . $e->getMessage() . "\n";
                usleep(500000);
            }
        }
    }

    /**
     * Saves raw event to MongoDB
     *
     * @param array{user_id: string, action: string, campaign_id?: string|null, ts?: int} $event
     */
    private function saveRawEvent(array $event): void
    {
        $this->mongoDb->selectCollection('raw_events')->insertOne([
            'user_id' => $event['user_id'],
            'action' => $event['action'],
            'campaign_id' => $event['campaign_id'] ?? null,
            'ts' => new UTCDateTime($event['ts'] ?? time() * 1000),
            'created_at' => new UTCDateTime(),
        ]);
    }
}
