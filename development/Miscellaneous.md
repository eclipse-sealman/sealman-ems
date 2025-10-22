# Miscellaneous

## Open source clearance

Gathering licenses for used packages is done by executing following steps:

-   Licenses from `apk` packages are dumped into `/var/www/application/licenses/apk-licenses.csv` by `licenses-apk-dump.sh` script executed while building `core` image.
-   Manual licenses that cannot be automatically gathered should placed manually in `/var/www/application/licenses/manual-licenses.csv`. This file is also copied while building `core` image. At this time it is just PHP license.
-   Licenses for PHP libraries defined in `composer.json` and installed by `composer` are dumped into `/var/www/application/licenses/composer-licenses.csv` by `app:licenses:composer-dump` while building `backend` image.
-   Licenses for JavaScript libraries defined in `app/package.json` and installed by `npm` are copied into `/var/www/application/licenses/javascript-licenses.csv` when building `app` image from `frontend` image. `frontend` image is using `license-checker` library to dump mentioned licenses.
-   All 4 dumped CSV files are combined into one `/var/www/application/licenses/licenses.csv` when running `entrypoint.sh` in `app` container.
-   `/var/www/application/licenses/licenses.csv` is loaded using `app:licenses:load` when running `entrypoint.sh` in `app` container. File is only loaded when its' md5 hash is changed (currently loaded hash is stored on `Configuration` entity).

All dumped CSV files consist of 5 columns in following order:

1. Package name@version
2. Package name
3. Package version
4. Package license name
5. Package description

PHP extensions are part of PHP source code and have the same license as PHP. https://github.com/php/php-src (folder `/ext`).

In SCEP image we are compiling `scep` binary that is used by application. Based on GCC license (https://www.gnu.org/licenses/gcc-exception-3.1.en.html) we do not need to add `g++` and `gcc` licenses to Open Source Clearance.

We are NOT adding any licenses to Open Source Clearance that are connected to VPN Container Client, Edge gateway, TK800 Router etc. as they are seperate software.

## Auditability of commands (cli)

Commands that are run by `cron` in background should be blamed by system user (as in system user has run them).

Example of such commands are:

-   `bin/maintenance.sh` which runs i.e. `php bin/console app:maintenance:start`
-   `php bin/console app:maintenance:auto-remove-backups`
-   `php bin/console app:close-expired-vpn-connection`

Commands that are not run by `cron` in background are not blamed. Those can be run by anyone who has access to the host system.

Example of such commands are:

-   `php bin/console app:user:disable`
-   `php bin/console app:maintenance-mode:enable`
