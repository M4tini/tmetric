# TMetric

UI to browse your GitHub repository commits for a date range and save them as time entries in TMetric.

## Installation

```shell
composer install
```

## Usage

```shell
docker-compose up -d
```

## Configuration

The `.env` file needs some values to work correctly.

### GitHub token

Click `Generate new token` on your personal access tokens page: https://github.com/settings/tokens and give permissions on:
- `repo` (all)
- `read:user`

### TMetric token

Click `Get new API token` on your profile page: https://app.tmetric.com/#/profile

### TMetric workspace ID and user ID

Generate a Team Summary report, click on your user, and copy the values from the browser address bar:
`https://app.tmetric.com/#/reports/{workspace_id}/detailed?user={user_id}`
