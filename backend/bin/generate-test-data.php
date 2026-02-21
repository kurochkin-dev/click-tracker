<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Container\ContainerFactory;
use App\Domain\Events\EventIngestService;

$root = dirname(__DIR__, 2);
$container = ContainerFactory::create($root);

/** @var EventIngestService $ingestService */
$ingestService = $container->get(EventIngestService::class);

// Параметры генерации
$campaigns = ['campaign-1', 'campaign-2', 'campaign-3', 'campaign-4', 'campaign-5'];
$days = 7; // Генерируем данные за последние 7 дней
$eventsPerDay = 50;

$endDate = date('Y-m-d'); // Сегодня
$startDate = date('Y-m-d', strtotime("-{$days} days")); // 7 дней назад

echo "Generating test data...\n";
echo "Campaigns: " . count($campaigns) . "\n";
echo "Date range: {$startDate} to {$endDate}\n";
echo "Events per day: {$eventsPerDay}\n\n";

$totalEvents = 0;
$startTimestamp = strtotime($startDate);
$endTimestamp = strtotime($endDate);
$daysCount = (int)(($endTimestamp - $startTimestamp) / 86400) + 1;

for ($day = 0; $day < $daysCount; $day++) {
    $currentDate = date('Y-m-d', $startTimestamp + ($day * 86400));
    $dateStart = strtotime($currentDate . ' 00:00:00');
    
    echo "Generating events for {$currentDate}...\n";
    
    $batch = [];
    
    for ($i = 0; $i < $eventsPerDay; $i++) {
        $campaignId = $campaigns[array_rand($campaigns)];
        $userId = 'user-' . rand(1, 100);
        
        // Генерируем случайное время в течение дня
        $randomHour = rand(0, 23);
        $randomMinute = rand(0, 59);
        $randomSecond = rand(0, 59);
        $ts = $dateStart + ($randomHour * 3600) + ($randomMinute * 60) + $randomSecond;
        
        // 70% impression, 30% click
        $action = (rand(1, 100) <= 30) ? 'click' : 'impression';
        
        $batch[] = [
            'user_id' => $userId,
            'action' => $action,
            'campaign_id' => $campaignId,
            'ts' => $ts * 1000, // в миллисекундах
        ];
    }
    
    // Отправляем батчами по 20 событий через EventIngestService
    // Это отправит события в Redis Streams, откуда их заберёт воркер
    $chunks = array_chunk($batch, 20);
    foreach ($chunks as $chunk) {
        $result = $ingestService->ingest($chunk);
        $totalEvents += $result['accepted'];
        echo "  Sent batch: {$result['accepted']} accepted, {$result['skipped']} skipped\n";
    }
    
    // Небольшая задержка между днями
    usleep(100000); // 0.1 секунды
}

echo "\n✅ Generated {$totalEvents} events total\n";
echo "Events are sent to Redis Streams.\n";
echo "Make sure worker is running to process them:\n";
echo "  docker compose exec php-fpm php /var/www/backend/bin/worker.php\n";
echo "\nAfter worker processes events, check frontend at http://localhost:3000\n";

