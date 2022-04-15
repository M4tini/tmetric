<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

class Config
{
    public string $action;
    public array $actions;
    public string $date_from;
    public string $date_to;
    public DateTime $dateFrom;
    public DateTime $dateTo;
    public string $github_token;
    public string $github_organization;
    public string $github_user;
    public string $tmetric_token;
    public string $tmetric_user_id;
    public string $tmetric_workspace_id;

    public function __construct()
    {
        $this->action = $_POST['action'] ?? $_GET['action'] ?? 'sync';
        $this->actions = explode(',', $_ENV['ALLOWED_ACTIONS']);
        $this->date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? date('Y-m-d');
        $this->date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? date('Y-m-d');

        $this->dateFrom = DateTime::createFromFormat('Y-m-d', $this->date_from);
        $this->dateTo = DateTime::createFromFormat('Y-m-d', $this->date_to);

        $this->github_token = $_ENV['GITHUB_TOKEN'];
        $this->github_organization = $_ENV['GITHUB_ORGANIZATION'];
        $this->github_user = $_POST['github_user'] ?? $_GET['github_user'] ?? $_ENV['GITHUB_USER'];

        $this->tmetric_token = $_ENV['TMETRIC_TOKEN'];
        $this->tmetric_user_id = $_ENV['TMETRIC_USER_ID'];
        $this->tmetric_workspace_id = $_ENV['TMETRIC_WORKSPACE_ID'];
    }

    public function getContributionsQuery(): string
    {
        $organizations = new Github\Api\Organization($this->getGithubClient());
        $organization = $organizations->show($this->github_organization);

        return <<<GRAPHQL
{
  user(
    login: "{$this->github_user}"
  ) {
    contributionsCollection(
      from: "{$this->date_from}T00:00:00",
      to: "{$this->date_to}T23:59:59",
      organizationID: "{$organization['node_id']}"
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
}
GRAPHQL;
    }

    public function getGithubClient(): Github\Client
    {
        $client = new Github\Client();
        $client->authenticate($this->github_token, null, Github\Client::AUTH_ACCESS_TOKEN);

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

    public function getTMetricProjects(): array
    {
        return [
            271216 => 'API',
            271222 => 'Microservices',
            271224 => 'Admin / V2',
            271225 => 'Automation Rules',
            271226 => 'Performance',
            461354 => 'Shipment collections',
            461355 => 'Rate management',
            461356 => 'Analytics',
        ];
    }

    public function getTMetricUsers(): array
    {
        return [
            128921 => 'Martin Boer',
            128919 => 'Nick de Vries',
            128000 => 'Yoan-Alexander Grigorov',
            217331 => 'Nick Zwaans',
        ];
    }

    /**
     * Use bgcolor to make copy to Excel retain background colors.
     */
    public function backgroundColor(DateTime $dateTime): string
    {
        return match (true) {
            $this->isWeekend($dateTime) => 'bgcolor="#FFC7CE"',
            $this->isHoliday($dateTime) => 'bgcolor="#F4B183"',
            $this->isFuture($dateTime) => 'bgcolor="#999"',
            default => '',
        };
    }

    public function isFuture(DateTime $dateTime): bool
    {
        return $dateTime->format('Ymd') > (new DateTime())->format('Ymd');
    }

    public function isWeekend(DateTime $dateTime): bool
    {
        return in_array($dateTime->format('l'), ['Saturday', 'Sunday']);
    }

    public function isHoliday(DateTime $dateTime): bool
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
        ]);
    }
}
