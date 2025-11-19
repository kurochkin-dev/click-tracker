<?php

declare(strict_types=1);

namespace App\Core\Config;

class Settings
{
    public static function definitions(): array
    {
        return [
            'app.env' => getenv('APP_ENV') ?: 'local',
            'app.debug' => filter_var(getenv('APP_DEBUG') ?: '1', FILTER_VALIDATE_BOOLEAN),

            'db.dsn' => sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('MYSQL_HOST') ?: 'mysql',
                getenv('MYSQL_DATABASE') ?: 'clicktracker'
            ),
            'db.user' => getenv('MYSQL_USER') ?: 'click',
            'db.pass' => getenv('MYSQL_PASSWORD') ?: 'clickpass',
            'db.options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 1,
            ],

            'redis.config' => [
                'scheme' => 'tcp',
                'host' => getenv('REDIS_HOST') ?: 'redis',
                'port' => (int)(getenv('REDIS_PORT') ?: 6379),
                'timeout' => 0.5,
                'read_write_timeout' => 0.5,
            ],

            'mongo.uri' => sprintf(
                'mongodb://%s:%s@%s:%d',
                getenv('MONGO_INITDB_ROOT_USERNAME') ?: 'root',
                getenv('MONGO_INITDB_ROOT_PASSWORD') ?: 'secret',
                getenv('MONGO_HOST') ?: 'mongo',
                (int)(getenv('MONGO_PORT') ?: 27017),
            ),
            'mongo.db' => getenv('MONGO_DB') ?: 'clicktracker',

            'streams.ingest' => getenv('INGEST_STREAM') ?: 'events:ingest',
            'streams.maxlen' => (int)(getenv('INGEST_MAXLEN') ?: 100000),
        ];
    }
}
