<?php

declare(strict_types=1);

use App\Core\Container\ContainerFactory;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\HealthController;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__, 2);
$container = ContainerFactory::create($root);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/health', [HealthController::class, 'health']);
$app->post('/v1/events', [EventsController::class, 'ingest']);

$app->run();