<?php

require_once __DIR__ . '/config.php';

$config = new Config();

echo '
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>EZ TMetric</title>
    <link rel="stylesheet" href="/assets/tmetric.css?d=' . filemtime('assets/tmetric.css') . '">
    <script src="/assets/tmetric.js?d=' . filemtime('assets/tmetric.js') . '"></script>
  </head>
  <body>
    <form action="/" method="post">
    <table>
      <tr>
        <td>
          GitHub organization<br>
          <input type="text" name="github_organization" value="' . $config->github_organization . '" disabled/>
        </td>
        <td>
          GitHub user<br>
          <input type="text" name="github_user" value="' . $config->github_user . '"/>
        </td>
        <td>
          Action<br>
          <select name="action">
';

$actions = [
    'sync',
    'report',
    'github-user',
    'github-organization',
    'github-repositories',
    'github-commits',
    'github-contributions',
    'tmetric-projects',
    'tmetric-time-entries',
];
foreach ($actions as $action) {
    if (in_array($action, $config->actions) || $config->actions === ['*']) {
        $selected = ($config->action === $action) ? 'selected="selected"' : '';
        echo '<option value="' . $action . '" ' . $selected . '>' . $action . '</option>';
    }
}

echo '
          </select>
        </td>
        <td ' . $config->backgroundColor($config->dateFrom) . '>
          Date from<br>
          <input type="date" name="date_from" value="' . $config->date_from . '"/>
        </td>
        <td ' . $config->backgroundColor($config->dateTo) . '>
          Date to<br>
          <input type="date" name="date_to" value="' . $config->date_to . '"/>
        </td>
        <td class="center">
          <a href="#" onclick="modifyDate(\'date_from\', -1);modifyDate(\'date_to\', -1);document.forms[0].submit()" title="Yesterday">◀</a>
          <a href="#" onclick="modifyDate(\'date_from\', 1);modifyDate(\'date_to\', 1);document.forms[0].submit()" title="Tomorrow">▶</a>
          <br>
          <a href="#" onclick="modifyDate(\'date_from\', -7);modifyDate(\'date_to\', -7);document.forms[0].submit()" title="Last week">◀◀</a>
          <a href="#" onclick="modifyDate(\'date_from\', 7);modifyDate(\'date_to\', 7);document.forms[0].submit()" title="Next week">▶▶</a>
        </td>
        <td>
          <button type="submit">search</button>
        </td>
        <td>
          <a href="/" onclick="return window.confirm(\'okok?\')">reset</a>
        </td>
        <td class="months">
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 1);document.forms[0].submit()">jan</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 2);document.forms[0].submit()">feb</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 3);document.forms[0].submit()">mrt</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 4);document.forms[0].submit()">apr</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 5);document.forms[0].submit()">may</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 6);document.forms[0].submit()">jun</button>
          <br>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 7);document.forms[0].submit()">jul</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 8);document.forms[0].submit()">aug</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 9);document.forms[0].submit()">sep</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 10);document.forms[0].submit()">oct</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 11);document.forms[0].submit()">nov</button>
          <button type="button" onclick="targetMonth(\'' . $config->dateFrom->format('Y') . '\', 12);document.forms[0].submit()">dec</button>
        </td>
      </tr>
    </table>
    </form>
';

switch ($config->action) {
    case 'github-user':
        $user = new Github\Api\User($config->getGithubClient());
        var_dump($user->show($config->github_user));
        break;

    case 'github-organization':
        $organizations = new Github\Api\Organization($config->getGithubClient());
        var_dump($organizations->show($config->github_organization));
        break;

    case 'github-repositories':
        $repositories = new Github\Api\Repo($config->getGithubClient());
        var_dump($repositories->org($config->github_organization, [
            'sort'     => 'full_name',
            'per_page' => 100, // max 100
            'page'     => 1,
        ]));
        break;

    case 'github-commits':
        $commits = new Github\Api\Repository\Commits($config->getGithubClient());
        var_dump('Hardcoded repository: api');
        var_dump($commits->all($config->github_organization, 'api', [
            'author'   => $config->github_user,
            'per_page' => 100, // max 100
            'page'     => 1,
            'since'    => $config->date_from . 'T00:00:00Z',
            'until'    => $config->date_to . 'T23:59:59Z',
        ]));
        break;

    case 'github-contributions':
        $graphQL = new Github\Api\GraphQL($config->getGithubClient());
        $results = $graphQL->execute($config->getContributionsQuery());
        var_dump($results['data']['user']['contributionsCollection']['commitContributionsByRepository']);
        break;

    case 'tmetric-projects':
        $response = $config->getTMetricClient()->get('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries/projects?' . http_build_query([
                'userId' => $config->tmetric_user_id,
            ]));
        $projects = json_decode((string) $response->getBody(), true);
        var_dump($projects);
        break;

    case 'tmetric-time-entries':
        $response = $config->getTMetricClient()->get('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries?' . http_build_query([
                'startDate' => $config->date_from . 'T00:00:00',
                'endDate'   => $config->date_to . 'T23:59:59',
                'userId'    => $config->tmetric_user_id,
            ]));
        $timeEntries = json_decode((string) $response->getBody(), true);
        var_dump($timeEntries);
        break;

    case 'sync':
        if ($config->dateFrom->diff($config->dateTo)->days > 7) {
            var_dump('Max date range is 7 days to avoid API overload.');
            exit;
        }

        $graphQL = new Github\Api\GraphQL($config->getGithubClient());
        $results = $graphQL->execute($config->getContributionsQuery());

        $repositories = [];
        foreach ($results['data']['user']['contributionsCollection']['commitContributionsByRepository'] as $contribution) {
            $repositories[] = $contribution['repository']['name'];
        }
        $projects = $config->getTMetricProjects();

        $res = [];
        foreach ($repositories as $repository) {
            $commits = new Github\Api\Repository\Commits($config->getGithubClient());

            try {
                $commitList = $commits->all($config->github_organization, $repository, [
                    'author'   => $config->github_user,
                    'per_page' => 100, // max 100
                    'page'     => 1,
                    'since'    => $config->date_from . 'T00:00:00Z',
                    'until'    => $config->date_to . 'T23:59:59Z',
                ]);
            } catch (Github\Exception\RuntimeException $exception) {
                var_dump('Error retrieving commits for ' . $config->github_organization . '/' . $repository . ' - ' . $exception->getMessage());
                continue;
            }

            if (empty($commitList)) {
                continue;
            }

            foreach ($commitList as $commit) {
                $message = $commit['commit']['message'];
                $dateAuth = DateTime::createFromFormat(DateTimeInterface::ISO8601, $commit['commit']['author']['date']);
                $dateCommit = DateTime::createFromFormat(DateTimeInterface::ISO8601, $commit['commit']['committer']['date']);
                $sameDates = $commit['commit']['author']['date'] === $commit['commit']['committer']['date'];
                $projectOptions = [];
                foreach ($projects as $projectId => $projectName) {
                    $selected = '';
                    if ((str_contains($repository, 'microservice-') || str_contains($repository, 'integration-') || str_contains($repository, '-plugin')) && $projectName === 'Microservices') {
                        $selected = 'selected="selected"';
                    }
                    if ((str_contains($repository, 'app') || str_contains($repository, 'backoffice')) && $projectName === 'Admin / V2') {
                        $selected = 'selected="selected"';
                    }
                    if (str_contains($repository, 'infrastructure') && $projectName === 'Performance') {
                        $selected = 'selected="selected"';
                    }
                    if (str_contains(strtolower($message), 'queue') && $projectName === 'Performance') {
                        $selected = 'selected="selected"';
                    }
                    $projectOptions[] = '<option value="' . $projectId . '" ' . $selected . '>' . $projectName . '</option>';
                }

                if (str_contains($message, 'Merge pull request') || str_contains($message, 'Merge branch')) {
                    continue;
                }
                $sortKey = $dateCommit->format('YmdHi') . $repository . $message;
                $res[$sortKey] = '
                        <form action="/tmetric.php" method="post" target="_blank">
                            <td>' . implode('</td><td>', [
                        $commit['commit']['committer']['name'],
                        $repository,
                        implode('<br>', array_filter([
                            $sameDates ? '' : '<span class="gray-text">' . $dateAuth->format('Y-m-d') . '</span>',
                            $dateCommit->format('Y-m-d'),
                        ])),
                        implode('<br>', array_filter([
                            $sameDates ? '' : '<span class="gray-text">' . $dateAuth->format('H:i') . '</span>',
                            $dateCommit->format('D @ H:i'),
                        ])),
                        $message,
                        '
                            <input type="hidden" name="action" value="post">
                            <input type="text" name="note" value="' . ucfirst(trim(preg_replace('/:\w+:/', '', $message))) . '" required><br>
                            <input type="date" name="date" value="' . $dateCommit->format('Y-m-d') . '" required>
                            <input type="time" name="start" value="' . (clone $dateCommit)->sub(new DateInterval($_ENV['LOG_TIME_INTERVAL']))->format($_ENV['LOG_TIME_FORMAT']) . '" required>
                            <input type="time" name="end" value="' . $dateCommit->format($_ENV['LOG_TIME_FORMAT']) . '" required>
                            <select name="project" required>' . implode('', $projectOptions) . '</select>',
                        '
                            <button type="submit">log</button>',
                    ]) . '</td>
                        </form>';
            }
        }
        ksort($res);

        echo '<h2>GitHub</h2><table><tr>' . implode('</tr><tr>', $res) . '</tr></table>';

        $response = $config->getTMetricClient()->get('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries?' . http_build_query([
                'startDate' => $config->date_from . 'T00:00:00',
                'endDate'   => $config->date_to . 'T23:59:59',
                'userId'    => $config->tmetric_user_id,
            ]));
        $timeEntries = json_decode((string) $response->getBody(), true);

        $res = [];
        $totalDiff = (new DateTime())->setTime(0, 0, 0);

        foreach ($timeEntries as $timeEntry) {
            $start = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['startTime'] . 'Z');
            $end = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['endTime'] . 'Z');
            $diff = $start->diff($end ?: new DateTime());
            $totalDiff->add($diff);
            $projectOptions = [];
            foreach ($projects as $projectId => $projectName) {
                $selected = '';
                if ($timeEntry['project']['id'] === $projectId) {
                    $selected = 'selected="selected"';
                }
                $projectOptions[] = '<option value="' . $projectId . '" ' . $selected . '>' . $projectName . '</option>';
            }
            $note = $timeEntry['note'] ?? $timeEntry['task']['name'];

            $sortKey = $start->format('YmdHi');
            $res[$sortKey] = '
                    <form action="/tmetric.php" method="post" target="_blank">
                        <td>' . implode('</td><td>', [
                    $start->format('Y-m-d'),
                    $start->format('H:i') . ' - ' . $end->format('H:i'),
                    $diff->format('%h h %i m'),
                    $timeEntry['project']['name'],
                    $note,
                    '
                        <input type="hidden" name="action" value="put">
                        <input type="hidden" name="id" value="' . $timeEntry['id'] . '">
                        <input type="text" name="note" value="' . $note . '" required><br>
                        <input type="date" name="date" value="' . $start->format('Y-m-d') . '" required>
                        <input type="time" name="start" value="' . $start->format('H:i') . '" required>
                        <input type="time" name="end" value="' . $end->format('H:i') . '" required>
                        <select name="project" required>' . implode('', $projectOptions) . '</select>',
                    '
                        <button type="submit">edit</button>',
                ]) . '</td>
                    </form>
                    
                    <form action="/tmetric.php" method="post" target="_blank">
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

    case 'report':
        $projects = $config->getTMetricProjects();

        $tables[0] = '<table class="left">
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>';
        do {
            $color = $config->backgroundColor($config->dateFrom);
            $cols = [
                $config->dateFrom->format('d/m'),
                $config->dateFrom->format('D'),
            ];

            $tables[0] .= '<tr><td ' . $color . '>' . implode('</td><td ' . $color . '>', $cols) . '</td></tr>';

            $config->dateFrom->modify('+1 day');
        } while ($config->dateFrom->format('Ymd') <= $config->dateTo->format('Ymd'));
        $tables[0] .= '
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>
        </table>';

        foreach ($config->getTMetricUsers() as $userId => $username) {
            $tables[$userId] = '<table class="center left">';

            $response = $config->getTMetricClient()->get('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries?' . http_build_query([
                    'startDate' => $config->date_from . 'T00:00:00',
                    'endDate'   => $config->date_to . 'T23:59:59',
                    'userId'    => $userId,
                ]));
            $timeEntries = json_decode((string) $response->getBody(), true);

            $dateEntries = [];
            foreach ($timeEntries as $timeEntry) {
                $date = substr($timeEntry['startTime'], 0, 10);

                if (!isset ($timeEntry['project'])) {
                    echo '<h2 style="color:#f00;">Missing project for time entry of <strong>' . $username . '</strong> on ' . substr($timeEntry['startTime'], 0, 10) . '</h2>';
                }

                $project = $timeEntry['project']['id'];
                $dateEntries[$date][$project][] = $timeEntry;
            }

            $tables[$userId] .= '<tr><th bgcolor="#B4C7E7" colspan="' . count($projects) . '">' . $username . '</th></tr>';
            $tables[$userId] .= '<tr>';
            foreach (array_values($projects) as $key => $projectName) {
                $tables[$userId] .= '<th bgcolor="#B4C7E7" title="' . $projectName . '">P' . ($key + 1) . '</td>';
            }
            $tables[$userId] .= '</tr>';

            $dateFrom = DateTime::createFromFormat('Y-m-d', $config->date_from);
            $dateTo = DateTime::createFromFormat('Y-m-d', $config->date_to);
            $counter = array_combine(array_keys($projects), array_fill(0, count($projects), 0));
            do {
                $color = $config->backgroundColor($dateFrom);
                $cols = [];

                foreach ($projects as $projectId => $projectName) {
                    $totalDiff = (new DateTime())->setTime(0, 0, 0);
                    $startDiff = $totalDiff->getTimestamp();
                    $ongoing = '';

                    if (isset($dateEntries[$dateFrom->format('Y-m-d')][$projectId])) {
                        foreach ($dateEntries[$dateFrom->format('Y-m-d')][$projectId] as $timeEntry) {
                            $start = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['startTime'] . 'Z');
                            $end = DateTime::createFromFormat(DateTimeInterface::ISO8601, $timeEntry['endTime'] . 'Z');
                            if (!$end) {
                                $ongoing = ' style="color:#f90;" ';
                            }
                            $diff = $start->diff($end ?: new DateTime());
                            $totalDiff->add($diff);
                        }
                    }

                    if (isset($_ENV['ADD_SCRUM_HOURS']) && $_ENV['ADD_SCRUM_HOURS'] === 'true' && $projectId === 461355) {
                        if ($config->backgroundColor($dateFrom) === '') {
                            // Grooming
                            if (in_array($dateFrom->format('l'), ['Monday'])) {
                                $totalDiff->add(new DateInterval('PT1H'));
                            }
                            // Refinement
                            $refinementDays = ($dateFrom->format('W') % 2 === 0) ? ['Wednesday'] : ['Tuesday', 'Thursday'];
                            if (in_array($dateFrom->format('l'), $refinementDays)) {
                                $totalDiff->add(new DateInterval('PT1H'));
                            }
                        }
                    }

                    $endDiff = $totalDiff->getTimestamp();
                    $seconds = $endDiff - $startDiff;
                    $hours = $seconds / 60 / 60;
                    $hoursPer15m = ceil(($hours - 0.125) * 4) / 4;

                    $cols[] = $seconds ? '<span title="' . number_format($hours, 2, '.', '') . '"' . $ongoing . '>' . number_format($hoursPer15m, 2, '.', '') . '</span>' : '';
                    $counter[$projectId] += $hoursPer15m;
                }

                $tables[$userId] .= '<tr><td ' . $color . '>' . implode('</td><td ' . $color . '>', $cols) . '</td></tr>';

                $dateFrom->modify('+1 day');
            } while ($dateFrom->format('Ymd') <= $dateTo->format('Ymd'));

            $total = array_sum($counter);
            $tables[$userId] .= '<tr>' . array_reduce($counter, function ($carry, $count) use ($total) {
                    return $carry . '<th bgcolor="#B4C7E7">' . ($count ?: '') . '</th>';
                }) . '</tr>';
            $tables[$userId] .= '<tr><th bgcolor="#B4C7E7" colspan="' . count($counter) . '">' . ($total ?: '') . '</th></tr>';
            $tables[$userId] .= '</table>';
        }

        echo implode('', $tables);
        break;
}

echo '
  </body>
</html>
';
