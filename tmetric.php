<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

$httpClient = new GuzzleHttp\Client([
    'base_uri' => 'https://app.tmetric.com/api/v3/accounts/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/',
]);
$headers = [
    'Authorization' => 'Bearer ' . $_ENV['TMETRIC_TOKEN'],
];

$response = match ($_POST['action']) {
    'post' => $httpClient->post('timeentries?' . http_build_query(['userId' => $_ENV['TMETRIC_USER_ID']]), [
        GuzzleHttp\RequestOptions::HEADERS => $headers,
        GuzzleHttp\RequestOptions::JSON    => [
            'project'   => [
                'id' => $_POST['project'],
            ],
            'note'      => $_POST['note'],
            'startTime' => $_POST['date'] . 'T' . $_POST['start'],
            'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
        ],
    ]),
    'put' => $httpClient->put('timeentries/' . $_POST['id'], [
        GuzzleHttp\RequestOptions::HEADERS => $headers,
        GuzzleHttp\RequestOptions::JSON    => [
            'project'   => [
                'id' => $_POST['project'],
            ],
            'note'      => $_POST['note'],
            'startTime' => $_POST['date'] . 'T' . $_POST['start'],
            'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
        ],
    ]),
    // For some reason, the delete request documented in their swagger does not delete anything, so we simulate the app.
    'delete' => $httpClient->post('https://app.tmetric.com/api/accounts/97840/timeentries/128921/bulk', [
        GuzzleHttp\RequestOptions::HEADERS => $headers,
        GuzzleHttp\RequestOptions::JSON    => [
            [
                'startTime' => $_POST['date'] . 'T' . $_POST['start'],
                'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
            ],
        ],
    ]),
};
$result = json_decode((string) $response->getBody(), true);

var_dump($result);

echo '<a href="https://app.tmetric.com/#/tracker/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/?day=' . str_replace('-', '', $_POST['date']) . '" target="_blank">check</a>';
echo '<style>body { cursor: crosshair; height: 100%; margin: 0; }</style>';
echo '<script>document.body.onclick = () => window.close()</script>';
