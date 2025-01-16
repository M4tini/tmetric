<?php

declare(strict_types=1);

use Carbon\Carbon;

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

ini_set('xdebug.var_display_max_depth', 10);

class Config
{
    private DateTimeZone $timezone;
    public Carbon $now;
    public string $offset;
    public string $view;
    public array $views;
    public array $buttons;
    public Carbon $dateFrom;
    public Carbon $dateTo;
    private string $github_token;
    public string $github_organization;
    public string $github_user;
    private string $tmetric_token;
    public string $tmetric_user_id;
    public string $tmetric_workspace_id;
    public bool $addScrumHours;

    public function __construct()
    {
        $this->timezone = new DateTimeZone($_ENV['LOG_TIMEZONE']);
        $this->now = Carbon::now($this->timezone);
        $this->offset = $this->now->format('P');

        $this->view = $_POST['view'] ?? $_GET['view'] ?? 'report';
        $this->views = explode(',', $_ENV['ALLOWED_ACTIONS']);
        $this->buttons = explode(',', $_ENV['ALLOWED_BUTTONS'] ?? 'day');

        $dateFrom = $_POST['date_from'] ?? $_GET['date_from'] ?? $this->now->format('Y-m-d');
        $dateTo = $_POST['date_to'] ?? $_GET['date_to'] ?? $this->now->format('Y-m-d');
        $this->dateFrom = $this->createCarbon($dateFrom, 'Y-m-d');
        $this->dateTo = $this->createCarbon($dateTo, 'Y-m-d');

        $this->github_token = $_ENV['GITHUB_TOKEN'];
        $this->github_organization = $_ENV['GITHUB_ORGANIZATION'];
        $this->github_user = $_POST['github_user'] ?? $_GET['github_user'] ?? $_ENV['GITHUB_USER'];

        $this->tmetric_token = $_ENV['TMETRIC_TOKEN'];
        $this->tmetric_user_id = $_ENV['TMETRIC_USER_ID'];
        $this->tmetric_workspace_id = $_ENV['TMETRIC_WORKSPACE_ID'];

        $this->addScrumHours = $_ENV['ADD_SCRUM_HOURS'] === 'true';
    }

    public function getContributionsQuery(): string
    {
        $organizations = new Github\Api\Organization($this->getGithubClient());
        $organization = $organizations->show($this->github_organization);

        return <<<GRAPHQL
{
  user (
    login: "{$this->github_user}"
  ) {
    contributionsCollection(
      organizationID: "{$organization['node_id']}",
      from: "{$this->dateFrom->clone()->setTime(0, 0, 0)->format(DateTimeInterface::ATOM)}",
      to: "{$this->dateTo->clone()->setTime(23, 59, 59)->format(DateTimeInterface::ATOM)}"
    ) {
      commitContributionsByRepository (
        maxRepositories: 100
      ) {
        contributions {
          totalCount
        },
        repository {
          name,
          url
        }
      }
    },
    pullRequests (
      first: 100,
      states: OPEN
    ) {
      nodes {
        number,
        commits (
          first: 100
        ) {
          nodes {
            url,
            commit {
              authoredDate,
              author {
                user {
                  login,
                  name,
                  url
                }
              }
              committedDate,
              committer {
                user {
                  login,
                  name,
                  url
                }
              }
              message
            }
          }
        }
        repository {
          name,
          url
        },
        url
      }
    }
  }
}
GRAPHQL;
    }

    public function getGithubClient(): Github\Client
    {
        $client = new Github\Client();
        $client->authenticate($this->github_token, null, Github\AuthMethod::ACCESS_TOKEN);

        return $client;
    }

    public function getTMetricClient(): GuzzleHttp\Client
    {
        return new GuzzleHttp\Client([
            'base_uri' => 'https://app.tmetric.com/api/',
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->tmetric_token,
            ],
        ]);
    }

    public function getTMetricProjects(bool $includeScrumHours = false): array
    {
        $projects = [
            0      => 'Scrum', // Scrum events e.g. refinement sessions
            271216 => 'API',
            271222 => 'Microservices',
            271224 => 'Admin / V2',
//            271225 => 'Automation Rules', // Done
            271226 => 'Performance',
//            461354 => 'Collections', // Done
            461355 => 'Rate management',
            461356 => 'Analytics',
            779162 => 'Returns',
            808008 => 'DevOps',
        ];
        asort($projects);

        if (!$includeScrumHours) {
            unset($projects[0]);
        }

        return $projects;
    }

    public function getTMetricUsers(): array
    {
        return [
            128921 => 'Martin Boer',
            128919 => 'Nick de Vries',
            128000 => 'Yoan-Alexander Grigorov',
            217331 => 'Nick Zwaans',
            372173 => 'Yoeri Walstra',
        ];
    }

    public function createCarbon(string $time, string $format = DateTimeInterface::ATOM): Carbon
    {
        // When the supplied time is in Y-m-d format, we should assume it's targeting the current timezone.
        $timeZone = ($format === 'Y-m-d') ? $this->timezone : null;

        return Carbon::createFromFormat($format, $time, $timeZone)->setTimezone($this->timezone);
    }

    /**
     * Use bgcolor to make copy to Excel retain background colors.
     */
    public function backgroundColor(Carbon $dateTime): string
    {
        return match (true) {
            $this->isHoliday($dateTime) => 'bgcolor="#F4B183"',
            $dateTime->isWeekend()      => 'bgcolor="#FFC7CE"',
            $dateTime->isFuture()       => 'bgcolor="#999"',
            default                     => '',
        };
    }

    public function isHoliday(Carbon $dateTime): bool
    {
        return in_array($dateTime->format('Y-m-d'), [
            '2021-01-01', // New year
            '2021-04-04', // Easter
            '2021-04-05', // Easter
            '2021-04-27', // King
//            '2021-05-05', // Liberation (every 5 years)
            '2021-05-13', // Ascension
            '2021-05-23', // Pentecost
            '2021-05-24', // Pentecost
            '2021-12-25', // Christmas
            '2021-12-26', // Christmas
            '2022-01-01', // New year
            '2022-04-17', // Easter
            '2022-04-18', // Easter
            '2022-04-27', // King
//            '2022-05-05', // Liberation (every 5 years)
            '2022-05-26', // Ascension
            '2022-06-05', // Pentecost
            '2022-06-06', // Pentecost
            '2022-12-25', // Christmas
            '2022-12-26', // Christmas
            '2023-01-01', // New year
            '2023-04-09', // Easter
            '2023-04-10', // Easter
            '2023-04-27', // King
//            '2023-05-05', // Liberation (every 5 years)
            '2023-05-18', // Ascension
            '2023-05-28', // Pentecost
            '2023-05-29', // Pentecost
            '2023-12-25', // Christmas
            '2023-12-26', // Christmas
            '2024-01-01', // New year
            '2024-03-31', // Easter
            '2024-04-01', // Easter
            '2024-04-27', // King
//            '2024-05-05', // Liberation (every 5 years)
            '2024-05-09', // Ascension
            '2024-05-19', // Pentecost
            '2024-05-20', // Pentecost
            '2024-12-25', // Christmas
            '2024-12-26', // Christmas
            '2025-01-01', // New year
            '2025-04-20', // Easter
            '2025-04-21', // Easter
            '2025-04-26', // King
            '2025-05-05', // Liberation (every 5 years)
            '2025-05-29', // Ascension
            '2025-06-08', // Pentecost
            '2025-06-09', // Pentecost
            '2025-12-25', // Christmas
            '2025-12-26', // Christmas
        ]);
    }
}
