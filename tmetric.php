<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

$httpClient = new GuzzleHttp\Client([
    'base_uri' => 'https://app.tmetric.com',
]);
$response = $httpClient->post('/api/v3/accounts/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/timeentries?' . http_build_query([
        'userId' => $_ENV['TMETRIC_USER_ID'],
    ]), [
    'headers' => [
        'Authorization' => 'Bearer ' . $_ENV['TMETRIC_TOKEN'],
    ],
    'json'    => [
        'project'   => [
            'id' => $_POST['project'] ?? 271216,
        ],
        'note'      => $_POST['note'],
        'startTime' => $_POST['date'] . 'T' . $_POST['start'],
        'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
    ],
]);
$result = json_decode((string) $response->getBody(), true);

var_dump($result);

echo '<a href="https://app.tmetric.com/#/tracker/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/?day=' . str_replace('-', '', $_POST['date']) . '" target="_blank">check</a>';
