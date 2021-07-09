# TMetric

UI to browse your GitHub repository commits filtered by a date range and save them as time entries in TMetric.

## Installation

```shell
docker-compose run --rm tmetric composer install
```

## Usage

```shell
docker-compose up -d
```

Visit: http://localhost:8080

## Configuration

The `.env` file needs some values to work correctly.

#### GitHub token

Click `Generate new token` on your token settings page: https://github.com/settings/tokens and give permissions on:
- `repo` (all)
- `read:user`

#### GitHub user and organization

Use the names of your user and organization, no need for ID's.

#### TMetric token

Click `Get new API token` on your profile page: https://app.tmetric.com/#/profile (their tokens are valid for 1 year)

#### TMetric workspace ID and user ID

Generate a Team Summary report, click on your user and copy the values from the browser address bar:
`https://app.tmetric.com/#/reports/{workspace_id}/detailed?user={user_id}`

## References

- https://docs.github.com/en/rest
- https://tmetric.com/help/data-integrations/how-to-use-tmetric-rest-api
- https://app.tmetric.com/help/index.html
