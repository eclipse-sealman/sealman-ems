# Deprecations

Deprecations are configured by default to be visible, but not detailed (only counted).

In order to show details of deprecations and make them actionable use `--display-deprecations`. Example:

```bash
vendor/bin/phpunit --group smoke --display-deprecations
```

## error_reporting in setUpBeforeClass()

It seems that when executing `setUpBeforeClass()` error_reporting is not yet configured as defined in php ini sections of `phpunit.xml`. This leads to "uncaught" deprecations of setup procedure. Decided to silence it by hard-coding error_reporting level as it is not that important and any issues here would be visible straight away.

## Configuration explanation

PHPUnit 12.x allows you to configure reporting of deprecations using `source` part of configuration and following properties:

-   `ignoreSelfDeprecations`
-   `ignoreDirectDeprecations`
-   `ignoreIndirectDeprecations`

Nice explanation about deprecation categories here:

https://github.com/sebastianbergmann/phpunit/issues/5689

-   `self`: your own code triggers an issue in your own code
-   `direct`: your own code triggers an issue in third-party code
-   `indirect`: third-party code triggers an issue either in your own code or in third-party code

We would like to have only `self` and `direct` deprecations reported and `indirect` deprecations hidden. Unfortunately detecting this is based on stack trace interpretation (by `vendor/phpunit/phpunit/src/Runner/ErrorHandler.php`) and in our case it reports all code as `indirect`. I digged deeper into that and the issue seems to be due to the fact that stack trace includes trace of autoloader from composer. Based on implementation in `ErrorHandler` I do not see a way to avoid it for now. I could not figure out how to configure it better then it is now.

Using `symfony/phpunit-bridge` does not solve the issue and for now (2025-11-24) is not viable as it does not support PHPUnit 12.x. Interesting discussion about it here:

https://github.com/symfony/symfony/discussions/58954
