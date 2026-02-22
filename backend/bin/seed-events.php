<?php

declare(strict_types=1);

/**
 * Скрипт генерации тестовых событий для аналитики.
 *
 * Использование:
 *   php bin/seed-events.php [количество_событий] [url_api]
 *
 * Примеры:
 *   php bin/seed-events.php
 *   php bin/seed-events.php 5000
 *   php bin/seed-events.php 1000 http://localhost:8088
 */

$totalEvents = (int)($argv[1] ?? 2000);
$apiUrl      = rtrim($argv[2] ?? 'http://localhost:8088', '/') . '/v1/events';
$batchSize   = 100;

$goodIds = array_map(
    fn(int $i): string => (string)$i,
    range(1, 200)
);

$actions = ['contact_reveal', 'good_view', 'profile_view', 'message_send'];

$actionWeights = [
    'good_view'      => 60,
    'contact_reveal' => 25,
    'profile_view'   => 12,
    'message_send'   => 3,
];

$countries = ['RS', 'UA', 'DE', 'US', 'GB', 'FR', 'PL', 'CA', 'GE', 'TR', 'RU', 'IT', 'ES', 'NL'];

$cities = [
    'RS' => ['Belgrade', 'Novi Sad', 'Nis', 'Kragujevac'],
    'UA' => ['Kyiv', 'Lviv', 'Odessa', 'Kharkiv'],
    'DE' => ['Berlin', 'Munich', 'Hamburg', 'Frankfurt'],
    'US' => ['New York', 'Los Angeles', 'Chicago', 'Houston'],
    'GB' => ['London', 'Manchester', 'Birmingham', 'Leeds'],
    'FR' => ['Paris', 'Lyon', 'Marseille', 'Toulouse'],
    'PL' => ['Warsaw', 'Krakow', 'Wroclaw', 'Gdansk'],
    'CA' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary'],
    'GE' => ['Tbilisi', 'Batumi', 'Kutaisi'],
    'TR' => ['Istanbul', 'Ankara', 'Izmir'],
    'RU' => ['Moscow', 'Saint Petersburg', 'Novosibirsk'],
    'IT' => ['Rome', 'Milan', 'Naples', 'Turin'],
    'ES' => ['Madrid', 'Barcelona', 'Valencia', 'Seville'],
    'NL' => ['Amsterdam', 'Rotterdam', 'The Hague', 'Utrecht'],
];

$sources = ['good', 'profile', 'search', 'direct', 'external'];

$contactTypes = ['phone', 'email', 'telegram', 'whatsapp'];

$nowMs        = (int)floor(microtime(true) * 1000);
$thirtyDaysMs = 30 * 24 * 60 * 60 * 1000;

/**
 * Выбирает элемент с учётом весов.
 *
 * @param array<string, int> $weights
 */
function weightedRandom(array $weights): string
{
    $total = array_sum($weights);
    $rand  = random_int(1, $total);
    $sum   = 0;
    foreach ($weights as $item => $weight) {
        $sum += $weight;
        if ($rand <= $sum) {
            return $item;
        }
    }
    return array_key_first($weights);
}

echo "Отправляем {$totalEvents} событий на {$apiUrl} батчами по {$batchSize}...\n\n";

$sent    = 0;
$errors  = 0;
$batches = 0;

while ($sent < $totalEvents) {
    $remaining    = $totalEvents - $sent;
    $currentBatch = min($batchSize, $remaining);
    $events       = [];

    for ($i = 0; $i < $currentBatch; $i++) {
        $country = $countries[array_rand($countries)];
        $cityList = $cities[$country] ?? ['Unknown'];
        $city     = $cityList[array_rand($cityList)];
        $action   = weightedRandom($actionWeights);
        $goodId   = $goodIds[array_rand($goodIds)];

        $event = [
            'user_id' => 'user_' . bin2hex(random_bytes(5)),
            'action'  => $action,
            'good_id' => $goodId,
            'country' => $country,
            'city'    => $city,
            'source'  => $sources[array_rand($sources)],
            'ts'      => $nowMs - random_int(0, $thirtyDaysMs),
        ];

        if ($action === 'contact_reveal') {
            $event['contact_type'] = $contactTypes[array_rand($contactTypes)];
        }

        $events[] = $event;
    }

    $payload = json_encode(['events' => $events], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    $batches++;

    if ($curlError) {
        echo "  [ОШИБКА cURL] Батч #{$batches}: {$curlError}\n";
        $errors += $currentBatch;
        $sent   += $currentBatch;
        continue;
    }

    $result = json_decode($responseBody, true);

    if ($httpCode === 202 && is_array($result)) {
        $accepted = $result['accepted'] ?? 0;
        $skipped  = $result['skipped']  ?? 0;
        $sent    += $currentBatch;
        echo sprintf(
            "  Батч #%d: принято=%d, пропущено=%d | Итого: %d/%d\n",
            $batches, $accepted, $skipped, $sent, $totalEvents
        );
    } else {
        echo "  [ОШИБКА HTTP {$httpCode}] Батч #{$batches}: {$responseBody}\n";
        $errors += $currentBatch;
        $sent   += $currentBatch;
    }
}

echo "\n=== Готово ===\n";
echo "Всего событий: {$totalEvents}\n";
echo "Ошибок:        {$errors}\n";
echo "Батчей:        {$batches}\n";
