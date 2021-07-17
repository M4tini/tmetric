<?php

require_once __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

class Config
{
    public string $action;
    public string $date_from;
    public string $date_to;
    public string $github_organization;
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
        $this->github_organization = $_POST['github_organization'] ?? $_GET['github_organization'] ?? $_ENV['GITHUB_ORGANIZATION'];
        $this->github_user = $_POST['user'] ?? $_GET['user'] ?? $_ENV['GITHUB_USER'];

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
      to: "{$this->date_to}T23:59:59"
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
}
