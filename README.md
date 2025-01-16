# EZ TMetric

UI to browse your GitHub repository commits filtered by a date range and save them as time entries in TMetric.

## Installation

```shell
sail up -d
sail composer install
sail npm install
```

## Usage

Run the below commands and visit: http://localhost:8080

```shell
sail up -d
sail npm run dev
```

## Configuration

Your public contributions are not visible through the GitHub GraphQL API, used to retrieve repository contributions.
You need to allow this by activating `Private contributions` in the `Contribution settings` on your GitHub profile.

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

#### Proposed TMetric time entries (optional)

You can specify the format of the proposed TMetric time entries. The default `H:i` can be changed to `H:00` to ignore the minutes of the GitHub commit.
The default length of a proposed time entry is 1 hour, defined by the interval `PT1H`. You can change this to `PT30M` or `PT15M` to decrease the default length.

## References

- https://docs.github.com/en/graphql
- https://docs.github.com/en/rest
- https://app.tmetric.com/api-docs/
