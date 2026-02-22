<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reports\ReportsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Контроллер аналитических отчётов.
 */
readonly class ReportsController
{
    public function __construct(
        private ReportsService $reportsService
    ) {
    }

    /**
     * Возвращает отчёт по конкретному объявлению.
     *
     * @param ServerRequestInterface    $request
     * @param ResponseInterface         $response
     * @param array<string, string>     $args
     * @return ResponseInterface
     */
    public function good(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $goodId = $args['goodId'] ?? null;
        if ($goodId === null || $goodId === '') {
            return $this->json($response, ['error' => 'good_id_required'], 400);
        }

        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $report = $this->reportsService->getGoodReport($goodId, $dates['from'], $dates['to']);
        return $this->json($response, $report);
    }

    /**
     * Возвращает сводный отчёт по всем объявлениям.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function goods(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $report = $this->reportsService->getAllGoodsReport($dates['from'], $dates['to']);
        return $this->json($response, $report);
    }

    /**
     * Возвращает суточный отчёт активности.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
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
     * Возвращает гео-отчёт: топ стран и городов.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function geo(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $dates = $this->validateAndExtractDates($request, $response);
        if ($dates instanceof ResponseInterface) {
            return $dates;
        }

        $queryParams = $request->getQueryParams();
        $action      = $queryParams['action'] ?? null;

        $validActions = ['contact_reveal', 'good_view', 'profile_view', 'message_send'];
        if ($action !== null && !in_array($action, $validActions, true)) {
            return $this->json($response, ['error' => 'invalid_action', 'valid' => $validActions], 400);
        }

        $report = $this->reportsService->getGeoReport($dates['from'], $dates['to'], $action);
        return $this->json($response, $report);
    }

    /**
     * Формирует JSON-ответ.
     *
     * @param ResponseInterface    $response
     * @param array<string, mixed> $payload  Данные ответа
     * @param int                  $status   HTTP-статус
     * @return ResponseInterface
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Валидирует и извлекает параметры дат из запроса.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return array{from: string|null, to: string|null}|ResponseInterface Даты или ответ с ошибкой
     */
    private function validateAndExtractDates(ServerRequestInterface $request, ResponseInterface $response): array|ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $dateFrom    = $queryParams['date_from'] ?? null;
        $dateTo      = $queryParams['date_to'] ?? null;

        if ($dateFrom !== null && !$this->isValidDate($dateFrom)) {
            return $this->json($response, ['error' => 'invalid_date_from_format'], 400);
        }

        if ($dateTo !== null && !$this->isValidDate($dateTo)) {
            return $this->json($response, ['error' => 'invalid_date_to_format'], 400);
        }

        return ['from' => $dateFrom, 'to' => $dateTo];
    }

    /**
     * Проверяет корректность формата даты Y-m-d.
     *
     * @param string $date Строка с датой
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
