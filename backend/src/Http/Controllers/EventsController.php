<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Events\EventIngestService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class EventsController
{
    public function __construct(
        private EventIngestService $ingestService
    )
    {
    }

    /**
     * Ingests events from HTTP request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ingest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, ['error' => 'invalid_json'], 400);
        }

        $events = $data['events'] ?? null;
        if (!is_array($events) || $events === []) {
            return $this->json($response, ['error' => 'events_required'], 422);
        }

        $idempotencyKey = $request->getHeaderLine('X-Idempotency-Key') ?: null;
        $result = $this->ingestService->ingest($events, $idempotencyKey);

        return $this->json($response, $result, 202);
    }

    /**
     * Creates JSON response
     *
     * @param ResponseInterface $response
     * @param array<string, mixed> $payload
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
