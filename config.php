<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

class Config
{
    public string $action;
    public string $date_from;
    public string $date_to;
    public DateTime $dateFrom;
    public DateTime $dateTo;
    public string $github_token;
    public string $github_organization;
    public string $github_organization_node_id;
    public string $github_user;
    public string $tmetric_token;
    public string $tmetric_user_id;
    public string $tmetric_workspace_id;

    public function __construct()
    {
        $this->action = $_POST['action'] ?? $_GET['action'] ?? 'sync';
        $this->date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? date('Y-m-d');
        $this->date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? date('Y-m-d');

        $this->dateFrom = DateTime::createFromFormat('Y-m-d', $this->date_from);
        $this->dateTo = DateTime::createFromFormat('Y-m-d', $this->date_to);

        $this->github_token = $_ENV['GITHUB_TOKEN'];
        $this->github_organization = $_ENV['GITHUB_ORGANIZATION'];
        $this->github_organization_node_id = $_ENV['GITHUB_ORGANIZATION_NODE_ID']; // TODO: cache from GitHub response.
        $this->github_user = $_POST['github_user'] ?? $_GET['github_user'] ?? $_ENV['GITHUB_USER'];

        $this->tmetric_token = $_ENV['TMETRIC_TOKEN'];
        $this->tmetric_user_id = $_ENV['TMETRIC_USER_ID'];
        $this->tmetric_workspace_id = $_ENV['TMETRIC_WORKSPACE_ID'];
    }

    public function getContributionsQuery(): string
    {
        return <<<GRAPHQL
{
  user(
    login: "{$this->github_user}"
  ) {
    contributionsCollection(
      from: "{$this->date_from}T00:00:00",
      to: "{$this->date_to}T23:59:59",
      organizationID: "{$this->github_organization_node_id}"
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
            271224 => 'Contract Module',
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

    public function isWeekend(DateTime $dateTime): bool
    {
        return in_array($dateTime->format('l'), ['Saturday', 'Sunday']);
    }
}
