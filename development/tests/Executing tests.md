# Executing tests

Use `vendor/bin/phpunit --group full` when running full tests.

Use `vendor/bin/phpunit --group smoke` when running smoke tests.

`vendor/bin/phpunit --testsuite Smoke` will NOT work as it depends on other classes being loaded.
