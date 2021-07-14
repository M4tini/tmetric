<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

echo '
<html lang="en">
<head>
    <title>EZ TMetric</title>
    <style>
        * {
          font-family: sans-serif;
        }
        body {
          background: #fafafa;
        }
        button {
          height: 40px;
          padding: 0 8px;
        }
        table {
          border-collapse: collapse;
        }
        td {
          border: 1px solid #444;
          padding: 4px 8px;
        }
    </style>
    <script>
        function modifyDate(name, addDays = 1) {
          const dateFrom = document.getElementsByName(name)[0]
          const date = new Date(dateFrom.value)
          date.setDate(date.getDate() + addDays);

          dateFrom.value = [
            date.getFullYear(),
            ("0" + (date.getMonth() + 1)).substr(-2),
            ("0" + (date.getDate())).substr(-2)
          ].join("-")
        }
    </script>
</head>
<body>
    <form action="/" method="post">
    <table>
    <tr>
        <td>
          GitHub organization:<br>
          <input type="text" name="organization" value="' . ($_POST['organization'] ?? $_ENV['GITHUB_ORGANIZATION']) . '" disabled/>
        </td>
        <td>
          GitHub user:<br>
          <input type="text" name="user" value="' . ($_POST['user'] ?? $_ENV['GITHUB_USER']) . '"/>
        </td>
        <td>
          Action:<br>
          <select name="action">
';

$actions = [
    'sync',
    'github-user',
    'github-organization',
    'github-repositories',
    'github-commits',
    'github-contributions',
    'tmetric-users',
    'tmetric-projects',
    'tmetric-time-entries',
];
foreach ($actions as $action) {
    $selected = (isset($_POST['action']) && $_POST['action'] === $action) ? ' selected="selected"' : '';
    echo '<option value="' . $action . '"' . $selected . '>' . $action . '</option>';
}
$dateFrom = $_POST['date_from'] ?? date('Y-m-d');
$dateTo = $_POST['date_to'] ?? date('Y-m-d');
$weekend = in_array(DateTime::createFromFormat('Y-m-d', $dateFrom)->format('l'), ['Saturday', 'Sunday']);
$style = ($weekend) ? ' style="background:#faa"' : '';

echo '
          </select>
        </td>
        <td' . $style . '>
          Date from:<br>
          <input type="date" name="date_from" value="' . $dateFrom . '"/>
        </td>
        <td' . $style . '>
          Date to:<br>
          <input type="date" name="date_to" value="' . $dateTo . '"/>
        </td>
        <td style="text-align:center;">
          <a href="#" onclick="modifyDate(\'date_from\', -1);modifyDate(\'date_to\', -1);document.forms[0].submit()">&lt;</a>
          <a href="#" onclick="modifyDate(\'date_from\', 1);modifyDate(\'date_to\', 1);document.forms[0].submit()">&gt;</a>
          <br>
          <a href="#" onclick="modifyDate(\'date_from\', -7);modifyDate(\'date_to\', -7);document.forms[0].submit()">&lt;&lt;</a>
          <a href="#" onclick="modifyDate(\'date_from\', 7);modifyDate(\'date_to\', 7);document.forms[0].submit()">&gt;&gt;</a>
        </td>
        <td>
          <button type="submit">search</button>
        </td>
        <td>
          <a href="/" onclick="return window.confirm(\'okok?\')">reset</a>
        </td>
    </tr>
    </table>
    </form>
';

if (isset($_POST['action'])) {
    $client = new Github\Client();
    $client->authenticate($_ENV['GITHUB_TOKEN'], null, Github\Client::AUTH_ACCESS_TOKEN);

    switch ($_POST['action']) {
        case 'github-user';
            $user = new Github\Api\User($client);
            var_dump($user->show($_POST['user']));
            break;

        case 'github-organization';
            $organizations = new Github\Api\Organization($client);
            var_dump($organizations->show($_ENV['GITHUB_ORGANIZATION']));
            break;

        case 'github-repositories';
            $repositories = new Github\Api\Repo($client);
            var_dump($repositories->org($_ENV['GITHUB_ORGANIZATION'], [
                'sort'     => 'full_name',
                'per_page' => 100, // max 100
                'page'     => 1,
            ]));
            break;

        case 'github-commits';
            $commits = new Github\Api\Repository\Commits($client);
            var_dump('Hardcoded repository: api');
            var_dump($commits->all($_ENV['GITHUB_ORGANIZATION'], 'api', [
                'author'   => $_POST['user'],
                'per_page' => 100, // max 100
                'page'     => 1,
                'since'    => $_POST['date_from'] . 'T00:00:00Z',
                'until'    => $_POST['date_to'] . 'T23:59:59Z',
            ]));
            break;

        case 'github-contributions';
            $graphQL = new Github\Api\GraphQL($client);
            $results = $graphQL->execute('{
  user(
    login: "' . $_POST['user'] . '"
  ) {
    contributionsCollection(
      from: "' . $_POST['date_from'] . 'T00:00:00",
      to: "' . $_POST['date_to'] . 'T23:59:59"
    ) {
      commitContributionsByRepository(
        maxRepositories: 100
      ) {
        repository {
          name
        },
        contributions {
          totalCount
        }
      }
    }
  }
}');
            var_dump($results['data']['user']['contributionsCollection']['commitContributionsByRepository']);
            break;

        case 'tmetric-users';
            var_dump([
                128000 => 'Yoan-Alexander Grigorov',
                128919 => 'Nick de Vries',
                128921 => 'Martin Boer',
                217331 => 'Nick Zwaans',
            ]);
            break;

        case 'tmetric-projects';
            $httpClient = new GuzzleHttp\Client([
                'base_uri' => 'https://app.tmetric.com',
            ]);
            $response = $httpClient->get('/api/v3/accounts/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/timeentries/projects?' . http_build_query([
                    'userId' => $_ENV['TMETRIC_USER_ID'],
                ]), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['TMETRIC_TOKEN'],
                ],
            ]);
            $projects = json_decode((string) $response->getBody(), true);

            $res = [];
            foreach ($projects as $project) {
                if ($project['status'] !== 'active') {
                    continue;
                }
                $res[$project['id']] = $project['name'];
            }
            var_dump($res);
            break;

        case 'tmetric-time-entries';
            $httpClient = new GuzzleHttp\Client([
                'base_uri' => 'https://app.tmetric.com',
            ]);
            $response = $httpClient->get('/api/v3/accounts/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/timeentries?' . http_build_query([
                    'startDate' => $_POST['date_from'] . 'T00:00:00',
                    'endDate'   => $_POST['date_to'] . 'T23:59:59',
                    'userId'    => $_ENV['TMETRIC_USER_ID'],
                ]), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['TMETRIC_TOKEN'],
                ],
            ]);
            $timeEntries = json_decode((string) $response->getBody(), true);

            var_dump($timeEntries);
            break;

        case 'sync':
            $graphQL = new Github\Api\GraphQL($client);

            $results = $graphQL->execute('{
  user(
    login: "' . $_POST['user'] . '"
  ) {
    contributionsCollection(
      from: "' . $_POST['date_from'] . 'T00:00:00",
      to: "' . $_POST['date_to'] . 'T23:59:59"
    ) {
      commitContributionsByRepository(
        maxRepositories: 100
      ) {
        repository {
          name
        },
        contributions {
          totalCount
        }
      }
    }
  }
}');
            $repositories = [];
            foreach ($results['data']['user']['contributionsCollection']['commitContributionsByRepository'] as $contribution) {
                $repositories[] = $contribution['repository']['name'];
            }
            $projects = [
                271216 => 'API',
                271222 => 'Microservices',
                271224 => 'Contract Module',
                271225 => 'Automation Rules',
                271226 => 'Performance',
                461354 => 'Shipment collections',
                461355 => 'Rate management',
                461356 => 'Analytics',
            ];

            $res = [];

            foreach ($repositories as $repository) {
                $commits = new Github\Api\Repository\Commits($client);

                try {
                    $commitList = $commits->all($_ENV['GITHUB_ORGANIZATION'], $repository, [
                        'author'   => $_POST['user'],
                        'per_page' => 100, // max 100
                        'page'     => 1,
                        'since'    => $_POST['date_from'] . 'T00:00:00Z',
                        'until'    => $_POST['date_to'] . 'T23:59:59Z',
                    ]);
                } catch (Github\Exception\RuntimeException $exception) {
                    var_dump('Error retrieving commits: ' . $exception->getMessage());
                    continue;
                }

                if (empty($commitList)) {
                    continue;
                }

                foreach ($commitList as $commit) {
                    $message = $commit['commit']['message'];
                    $date = DateTime::createFromFormat(DateTimeInterface::ISO8601, $commit['commit']['author']['date']);
                    $projectOptions = [];
                    foreach ($projects as $projectId => $projectName) {
                        $selected = '';
                        if (str_contains($repository, 'microservice-') && $projectName === 'Microservices') {
                            $selected = ' selected="selected"';
                        }
                        if (str_contains($repository, 'app') || str_contains($repository, 'backoffice') && $projectName === 'Contract Module') {
                            $selected = ' selected="selected"';
                        }
                        if (str_contains($repository, 'infrastructure') && $projectName === 'Performance') {
                            $selected = ' selected="selected"';
                        }
                        if (str_contains(strtolower($message), 'queue') && $projectName === 'Performance') {
                            $selected = ' selected="selected"';
                        }
                        $projectOptions[] = '<option value="' . $projectId . '"' . $selected . '>' . $projectName . '</option>';
                    }

                    if (str_contains($message, 'Merge pull request') || str_contains($message, 'Merge branch')) {
                        continue;
                    }
                    $startHour = $date->format('H');
                    $sortKey = $date->format('YmdHi') . $repository . $message;
                    $res[$sortKey] = '
                        <form action="/tmetric.php" method="post" target="_blank" style="margin:0;">
                            <td>' . implode('</td><td>', [
                            $commit['commit']['author']['name'],
                            $repository,
                            $date->format('Y-m-d'),
                            $date->format('H:i'),
                            $message,
                            '
                            <input type="hidden" name="action" value="post">
                            <input type="text" name="note" value="' . ucfirst(trim(preg_replace('/:\w+:/', '', $message))) . '" style="width:100%;" required><br>
                            <input type="date" name="date" value="' . $date->format('Y-m-d') . '" required>
                            <input type="time" name="start" value="' . $startHour . ':00" required>
                            <input type="time" name="end" value="' . (++$startHour < 10 ? '0' : '') . $startHour . ':00" required>
                            <select name="project" required>' . implode('', $projectOptions) . '</select>',
                            '
                            <button type="submit">log</button>',
                        ]) . '</td>
                        </form>';
                }
            }
            ksort($res);

            echo '<h2>GitHub</h2><table><tr>' . implode('</tr><tr>', $res) . '</tr></table>';

            $httpClient = new GuzzleHttp\Client([
                'base_uri' => 'https://app.tmetric.com',
            ]);
            $response = $httpClient->get('/api/v3/accounts/' . $_ENV['TMETRIC_WORKSPACE_ID'] . '/timeentries?' . http_build_query([
                    'startDate' => $_POST['date_from'] . 'T00:00:00',
                    'endDate'   => $_POST['date_to'] . 'T23:59:59',
                    'userId'    => $_ENV['TMETRIC_USER_ID'],
                ]), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['TMETRIC_TOKEN'],
                ],
            ]);
            $timeEntries = json_decode((string) $response->getBody(), true);

            $res = [];
            $totalDiff = (new DateTime())->setTime(0, 0, 0);

            foreach ($timeEntries as $timeEntry) {
                $start = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['startTime'] . 'Z');
                $end = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['endTime'] . 'Z');
                $diff = $start->diff($end);
                $totalDiff->add($diff);
                $projectOptions = [];
                foreach ($projects as $projectId => $projectName) {
                    $selected = '';
                    if ($timeEntry['project']['id'] === $projectId) {
                        $selected = ' selected="selected"';
                    }
                    $projectOptions[] = '<option value="' . $projectId . '"' . $selected . '>' . $projectName . '</option>';
                }

                $sortKey = $start->format('YmdHi');
                $res[$sortKey] = '
                    <form action="/tmetric.php" method="post" target="_blank" style="margin:0;">
                        <td>' . implode('</td><td>', [
                        $start->format('Y-m-d'),
                        $start->format('H:i') . ' - ' . $end->format('H:i'),
                        $diff->format('%h h %i m'),
                        $timeEntry['project']['name'],
                        $timeEntry['note'],
                        '
                        <input type="hidden" name="action" value="put">
                        <input type="hidden" name="id" value="' . $timeEntry['id'] . '">
                        <input type="text" name="note" value="' . $timeEntry['note'] . '" style="width:100%;" required><br>
                        <input type="date" name="date" value="' . $start->format('Y-m-d') . '" required>
                        <input type="time" name="start" value="' . $start->format('H:i') . '" required>
                        <input type="time" name="end" value="' . $end->format('H:i') . '" required>
                        <select name="project" required>' . implode('', $projectOptions) . '</select>',
                        '
                        <button type="submit">edit</button>',
                    ]) . '</td>
                    </form>
                    
                    <form action="/tmetric.php" method="post" target="_blank" style="margin:0;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="' . $timeEntry['id'] . '">
                        <input type="hidden" name="date" value="' . $start->format('Y-m-d') . '">
                        <input type="hidden" name="start" value="' . $start->format('H:i') . '">
                        <input type="hidden" name="end" value="' . $end->format('H:i') . '">
                        <td><button type="submit" onclick="return window.confirm(\'okok?\')">x</button></td>
                    </form>';
            }
            ksort($res);

            echo '<h2>TMetric (' . $totalDiff->format('G \h i \m') . ')</h2><table><tr>' . implode('</tr><tr>', $res) . '</tr></table>';
            break;
    }
}

echo '
</body>
</html>
';
