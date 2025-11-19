<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Domain\Events\EventAggregationService;
use App\Core\Container\ContainerFactory;
use App\Queue\RedisStreamClient;
use App\Worker\AggregationWorker;
use MongoDB\Client as MongoClient;

$root = dirname(__DIR__, 2);
$container = ContainerFactory::create($root);

$redis = $container->get(\Redis::class);
$mongo = $container->get(MongoClient::class);

$stream = new RedisStreamClient(
    $redis,
    $_ENV['INGEST_STREAM'] ?? 'events:ingest',
    'aggregation-group',
    'worker-' . gethostname() . '-' . getmypid()
);

$worker = new AggregationWorker(
    $stream,
    $container->get(EventAggregationService::class),
    $mongo,
    $_ENV['MONGO_DB'] ?? 'clicktracker'
);

echo "Worker started. Processing events...\n";
$worker->process();
