# Snippets

## Database

### Drop and create clean production database with synced migrations

```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all -n
```

## Prod fixtures

Same as in `entrypoint.sh`.

```bash
php bin/console doctrine:fixtures:load --group prod
```

## Run tests

Group `smoke`.

```bash
vendor/bin/phpunit --stop-on-defect --group smoke
```

Test suite `WebApi:Endpoint`.

```bash
vendor/bin/phpunit --stop-on-defect --testsuite WebApi:Endpoint
```

Display all issues for group `smoke`.

```bash
vendor/bin/phpunit --stop-on-defect --display-deprecations --group smoke
```

## Migrations

Use following command to run migrations the same as in `entrypoint.sh`.

```bash
php bin/console doctrine:migrations:migrate --query-time -v -n
```
