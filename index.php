<?php

declare(strict_types=1);

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
          <input type="date" name="date_from" value="' . $config->dateFrom->format('Y-m-d') . '"/>
        </td>
        <td ' . $config->backgroundColor($config->dateTo) . '>
          Date to<br>
          <input type="date" name="date_to" value="' . $config->dateTo->format('Y-m-d') . '"/>
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
            'since'    => $config->dateFrom->clone()->setTime(0, 0, 0)->format(DateTimeInterface::ATOM),
            'until'    => $config->dateTo->clone()->setTime(23, 59, 59)->format(DateTimeInterface::ATOM),
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
                'startDate' => $config->dateFrom->clone()->setTime(0, 0, 0)->format('Y-m-d\TH:i:s'),
                'endDate'   => $config->dateTo->clone()->setTime(23, 59, 59)->format('Y-m-d\TH:i:s'),
                'userId'    => $config->tmetric_user_id,
            ]));
        $timeEntries = json_decode((string) $response->getBody(), true);
        var_dump($timeEntries);
        break;

    case 'sync':
        if ($config->dateFrom->diffInDays($config->dateTo) > 7) {
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
                    'since'    => $config->dateFrom->clone()->setTime(0, 0, 0)->format(DateTimeInterface::ATOM),
                    'until'    => $config->dateTo->clone()->setTime(23, 59, 59)->format(DateTimeInterface::ATOM),
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
                $dateAuth = $config->createCarbon($commit['commit']['author']['date']);
                $dateCommit = $config->createCarbon($commit['commit']['committer']['date']);
                $sameDates = $dateAuth->diffInSeconds($dateCommit) === 0;
                $projectOptions = [];
                foreach ($projects as $projectId => $projectName) {
                    $selected = '';
                    if (
                        (str_contains($repository, 'microservice-') || str_contains($repository, 'integration-') || str_contains($repository, 'marketplace-') || str_contains($repository, '-plugin'))
                        && $projectName === 'Microservices'
                    ) {
                        $selected = 'selected="selected"';
                    }
                    if (
                        (str_contains($repository, 'app') || str_contains($repository, 'admin') || str_contains($repository, 'backoffice'))
                        && $projectName === 'Admin / V2'
                    ) {
                        $selected = 'selected="selected"';
                    }
                    if (
                        (str_contains($repository, 'infrastructure') || str_contains(strtolower($message), 'queue'))
                        && $projectName === 'Performance'
                    ) {
                        $selected = 'selected="selected"';
                    }
                    if (
                        (str_contains(strtolower($message), 'return') || str_contains(strtolower($message), 'order'))
                        && $projectName === 'Returns'
                    ) {
                        $selected = 'selected="selected"';
                    }
                    $projectOptions[] = '<option value="' . $projectId . '" ' . $selected . '>' . $projectName . '</option>';
                }

                if (str_contains($message, 'Merge pull request') || str_contains($message, 'Merge branch')) {
                    continue;
                }
                $sortKey = $dateCommit->getTimestamp() . $repository . $message;
                $res[$sortKey] = '
                        <form action="/tmetric.php" method="post" target="_blank">
                            <td>' . implode('</td><td>', [
                        $commit['commit']['committer']['name'],
                        implode('<br>', array_filter([
                            $sameDates ? '' : '<span class="gray-text">' . $dateAuth->format('D, d M Y') . '</span>',
                            $dateCommit->format('D, d M Y'),
                        ])),
                        implode('<br>', array_filter([
                            $sameDates ? '' : '<span class="gray-text">' . $dateAuth->format('H:i') . '</span>',
                            $dateCommit->format('H:i'),
                        ])),
                        $repository,
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
                'startDate' => $config->dateFrom->clone()->setTime(0, 0, 0)->format('Y-m-d\TH:i:s'),
                'endDate'   => $config->dateTo->clone()->setTime(23, 59, 59)->format('Y-m-d\TH:i:s'),
                'userId'    => $config->tmetric_user_id,
            ]));
        $timeEntries = json_decode((string) $response->getBody(), true);

        $res = [];
        $totalDiff = $config->now->clone()->setTime(0, 0, 0);

        foreach ($timeEntries as $timeEntry) {
            if (!isset ($timeEntry['project'])) {
                echo '<h2 style="color:#f00;">Missing project for time entry on ' . substr($timeEntry['startTime'], 0, 10) . '</h2>';
                continue;
            }
            $start = $config->createCarbon($timeEntry['startTime'] . $config->offset);
            $end = $timeEntry['endTime'] ? $config->createCarbon($timeEntry['endTime'] . $config->offset) : $config->now;
            $diff = $start->diff($end);
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
                    $start->format('D, d M Y'),
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
                        <button type="submit" onclick="this.disabled = true">edit</button>',
                ]) . '</td>
                    </form>
                    <form action="/tmetric.php" method="post" target="_blank">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="' . $timeEntry['id'] . '">
                        <input type="hidden" name="date" value="' . $start->format('Y-m-d') . '">
                        <input type="hidden" name="start" value="' . $start->format('H:i') . '">
                        <input type="hidden" name="end" value="' . $end->format('H:i') . '">
                        <td><button type="submit" onclick="return window.confirm(\'okok?\') ? this.disabled = true : false">x</button></td>
                    </form>';
        }
        ksort($res);

        echo '<h2>TMetric (' . $totalDiff->format('G \h i \m') . ')</h2><table><tr>' . implode('</tr><tr>', $res) . '</tr></table>';
        break;

    case 'report':
        $projects = $config->getTMetricProjects($config->addScrumHours);

        $tables[0] = '<table class="left">
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>';
        $rowDate = $config->dateFrom->clone();
        do {
            $color = $config->backgroundColor($rowDate);
            $col1 = $rowDate->format('d/m');
            $col2 = $rowDate->format('D');

            $tables[0] .= '<tr><td ' . $color . '>' . $col1 . '</td><td ' . $color . '>' . $col2 . '</td></tr>';

            $rowDate->modify('+1 day');
        } while (!$rowDate->isAfter($config->dateTo));
        $tables[0] .= '
            <tr><td bgcolor="#B4C7E7"></td><td bgcolor="#B4C7E7"></td></tr>
        </table>';

        foreach ($config->getTMetricUsers() as $userId => $username) {
            $tables[$userId] = '<table class="center left">';

            $response = $config->getTMetricClient()->get('v3/accounts/' . $config->tmetric_workspace_id . '/timeentries?' . http_build_query([
                    'startDate' => $config->dateFrom->clone()->setTime(0, 0, 0)->format('Y-m-d\TH:i:s'),
                    'endDate'   => $config->dateTo->clone()->setTime(23, 59, 59)->format('Y-m-d\TH:i:s'),
                    'userId'    => $userId,
                ]));
            $timeEntries = json_decode((string) $response->getBody(), true);

            $dateEntries = [];
            foreach ($timeEntries as $timeEntry) {
                if (!isset ($timeEntry['project'])) {
                    echo '<h2 style="color:#f00;">Missing project for time entry of <strong>' . $username . '</strong> on ' . substr($timeEntry['startTime'], 0, 10) . '</h2>';
                    continue;
                }

                $date = substr($timeEntry['startTime'], 0, 10);
                $project = $timeEntry['project']['id'];
                $dateEntries[$date][$project][] = $timeEntry;
            }

            $tables[$userId] .= '<tr><th bgcolor="#B4C7E7" colspan="' . count($projects) . '">' . $username . '</th></tr>';
            $tables[$userId] .= '<tr>';
            foreach (array_values($projects) as $index => $projectName) {
                $tables[$userId] .= '<th bgcolor="#B4C7E7" title="' . $projectName . '">P' . ($index + 1) . '</td>';
            }
            $tables[$userId] .= '</tr>';

            $dateFrom = $config->dateFrom->clone();
            $dateTo = $config->dateTo->clone();
            $timePerProject = array_combine(array_keys($projects), array_fill(0, count($projects), 0));
            do {
                $color = $config->backgroundColor($dateFrom);
                $cols = [];

                foreach ($projects as $projectId => $projectName) {
                    $totalDiff = $config->now->clone()->setTime(0, 0, 0);
                    $startDiff = $totalDiff->getTimestamp();
                    $ongoing = '';

                    if (isset($dateEntries[$dateFrom->format('Y-m-d')][$projectId])) {
                        foreach ($dateEntries[$dateFrom->format('Y-m-d')][$projectId] as $timeEntry) {
                            $start = $config->createCarbon($timeEntry['startTime'] . $config->offset);
                            $end = $timeEntry['endTime'] ? $config->createCarbon($timeEntry['endTime'] . $config->offset) : $config->now;
                            if (!$timeEntry['endTime']) {
                                $ongoing = ' style="color:#f90;" ';
                            }
                            $diff = $start->diff($end);
                            $totalDiff->add($diff);
                        }
                    }

                    if ($config->addScrumHours && $projectId === 0) {
                        if ($config->backgroundColor($dateFrom) === '') {
                            // Grooming
                            if ($dateFrom->isMonday()) {
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
                    $timePerProject[$projectId] += $hoursPer15m;
                }

                $tables[$userId] .= '<tr><td ' . $color . '>' . implode('</td><td ' . $color . '>', $cols) . '</td></tr>';

                $dateFrom->modify('+1 day');
            } while ($dateFrom->format('Ymd') <= $dateTo->format('Ymd'));

            $tables[$userId] .= '<tr>' . array_reduce($timePerProject, function ($carry, $count) {
                    return $carry . '<th bgcolor="#B4C7E7">' . ($count ?: '') . '</th>';
                }) . '</tr>';
            $tables[$userId] .= '<tr><th bgcolor="#B4C7E7" colspan="' . count($timePerProject) . '">' . (array_sum($timePerProject) ?: '') . '</th></tr>';
            $tables[$userId] .= '</table>';
        }

        echo implode('', $tables);
        break;
}

echo '
  </body>
</html>
';
