<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Базовый класс интеграционных тестов.
 *
 * Интеграционные тесты требуют запущенного Docker окружения.
 * Используют реальные соединения с MySQL, MongoDB, Redis.
 */
abstract class IntegrationTestCase extends TestCase
{
}
