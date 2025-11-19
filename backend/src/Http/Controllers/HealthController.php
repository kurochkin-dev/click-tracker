<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Redis;

readonly class HealthController
{
    public function __construct(
        private PDO   $pdo,
        private Redis $redis
    )
    {
    }

    /**
     * Health check endpoint
     *
     * @param ServerRequestInterface $request Query params: ?check=mysql,redis or ?check=all
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $check = $queryParams['check'] ?? 'all';

        $status = [
            'app' => 'ok',
            'time' => gmdate('c'),
        ];

        if ($check === 'all' || str_contains($check, 'mysql')) {
            $status['mysql'] = $this->checkMySQL();
        }

        if ($check === 'all' || str_contains($check, 'redis')) {
            $status['redis'] = $this->checkRedis();
        }

        $response->getBody()->write(json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Checks MySQL connection
     *
     * @return string 'ok' or 'fail'
     */
    private function checkMySQL(): string
    {
        try {
            $this->pdo->query('SELECT 1');
            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    /**
     * Checks Redis connection
     *
     * @return string 'ok' or 'fail'
     */
    private function checkRedis(): string
    {
        try {
            $ping = $this->redis->ping();
            return ($ping === '+PONG' || $ping === true) ? 'ok' : 'fail';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
