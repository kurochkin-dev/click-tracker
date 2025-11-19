<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reports\ReportsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class ReportsController
{
    public function __construct(
        private ReportsService $reportsService
    ) {
    }

    /**
     * Gets campaign report
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function campaign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $campaignId = $args['campaignId'] ?? null;
        if ($campaignId === null || $campaignId === '') {
            return $this->json($response, ['error' => 'campaign_id_required'], 400);
        }

        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $report = $this->reportsService->getCampaignReport($campaignId, $dates['from'], $dates['to']);
        return $this->json($response, $report);
    }

    /**
     * Gets all campaigns report
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function campaigns(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $report = $this->reportsService->getAllCampaignsReport($dates['from'], $dates['to']);
        return $this->json($response, $report);
    }

    /**
     * Gets daily report
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function daily(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $report = $this->reportsService->getDailyReport($dates['from'], $dates['to']);
        return $this->json($response, $report);
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

    /**
     * Validates and extracts date parameters from request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array{from: string|null, to: string|null}|ResponseInterface Returns dates or error response
     */
    private function validateAndExtractDates(ServerRequestInterface $request, ResponseInterface $response): array|ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $dateFrom = $queryParams['date_from'] ?? null;
        $dateTo = $queryParams['date_to'] ?? null;

        if ($dateFrom !== null && !$this->isValidDate($dateFrom)) {
            return $this->json($response, ['error' => 'invalid_date_from_format'], 400);
        }

        if ($dateTo !== null && !$this->isValidDate($dateTo)) {
            return $this->json($response, ['error' => 'invalid_date_to_format'], 400);
        }

        return ['from' => $dateFrom, 'to' => $dateTo];
    }

    /**
     * Validates date format (Y-m-d)
     *
     * @param string $date Date string
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

