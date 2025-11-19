<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Domain\Events\EventIngestService;
use App\Domain\Events\EventValidator;
use App\Domain\Reports\ReportsService;
use App\Domain\Campaigns\CampaignStatsRepository;
use App\Domain\Events\EventsRepository;
use Psr\Container\ContainerInterface;
use MongoDB\Client as MongoClient;
use Redis;
use PDO;
use function DI\factory;

class ServiceProvider
{
    public static function definitions(): array
    {
        return [
            PDO::class => factory(function (ContainerInterface $c): PDO {
                return new PDO(
                    $c->get('db.dsn'),
                    $c->get('db.user'),
                    $c->get('db.pass'),
                    $c->get('db.options')
                );
            }),

            Redis::class => factory(function (ContainerInterface $c): Redis {
                $config = $c->get('redis.config');
                $redis = new Redis();
                $redis->connect($config['host'], $config['port']);
                $redis->setOption(Redis::OPT_READ_TIMEOUT, 2.0);
                return $redis;
            }),

            MongoClient::class => factory(function (ContainerInterface $c): MongoClient {
                return new MongoClient($c->get('mongo.uri'));
            }),

            EventIngestService::class => factory(function (ContainerInterface $c): EventIngestService {
                return new EventIngestService(
                    $c->get(\Redis::class),
                    $c->get(EventValidator::class),
                    $c->get('streams.ingest'),
                    $c->get('streams.maxlen')
                );
            }),

            ReportsService::class => factory(function (ContainerInterface $c): ReportsService {
                return new ReportsService(
                    $c->get(CampaignStatsRepository::class),
                    $c->get(EventsRepository::class),
                    $c->get(\Redis::class),
                    $c->get('cache.ttl')
                );
            }),
        ];
    }
}