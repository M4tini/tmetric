<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$config = new Config();

$response = match ($_POST['method']) {
    'post' => $config->getTMetricClient()->post('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries?' . http_build_query(['userId' => $config->tmetric_user_id]), [
        GuzzleHttp\RequestOptions::JSON => [
            'project'   => [
                'id' => $_POST['project'],
            ],
            'note'      => $_POST['note'],
            'startTime' => $_POST['date'] . 'T' . $_POST['start'],
            'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
        ],
    ]),
    'put' => $config->getTMetricClient()->put('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries/' . $_POST['id'], [
        GuzzleHttp\RequestOptions::JSON => [
            'project'   => [
                'id' => $_POST['project'],
            ],
            'note'      => $_POST['note'],
            'startTime' => $_POST['date'] . 'T' . $_POST['start'],
            'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
        ],
    ]),
    // For some reason, the delete request documented in their swagger does not delete anything, so we simulate the app.
    'delete' => $config->getTMetricClient()->post('accounts/' . $config->tmetric_workspace_id . '/timeentries/' . $config->tmetric_user_id . '/bulk', [
        GuzzleHttp\RequestOptions::JSON => [
            [
                'startTime' => $_POST['date'] . 'T' . $_POST['start'],
                'endTime'   => $_POST['date'] . 'T' . $_POST['end'],
            ],
        ],
    ]),
};
$result = json_decode((string) $response->getBody(), true);

var_dump($result);

echo '<a href="https://app.tmetric.com/#/tracker/' . $config->tmetric_workspace_id . '/?day=' . str_replace('-', '', $_POST['date']) . '" target="_blank">check</a>';
echo '<style>body { cursor: crosshair; height: 100%; margin: 0; }</style>';
echo <<<SCRIPT
<script>
document.body.onclick = () => window.close()
document.addEventListener('keydown', function (event) { if (event.key === 'Enter') window.close() })
</script>
SCRIPT;
