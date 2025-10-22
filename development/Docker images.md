# Docker images

Docker images are divided into `core`, `backend`, `frontend` and `app` to achieve quick and slim builds. The `app` image is the one that should be deployed and published when ready.

Images are divided in following way:

-   `core` image holds os packages needed to run the application
-   `composer` image is responsible for installing composer
-   `backend` image is build on top of `core`. It uses `composer` image to copy installed composer package. It prepares Symfony application which includes: copying necessary files, using `composer` to install packages and setting up permissions
-   `frontend` image is responsible for building frontend application that is used as WebUI
-   `scep` image is responsible for compiling scep binary
-   `app` image is build on top of `backend`. It uses `frontend` and `scep` image to copy compiled frontend application and scep binary. It configures and sets up used packages like `nginx`, `crontab` and `supervisor`. It is also responsible for database initialization or migration and Symfony initialization (i.e. generating JWT and secret keys).
-   `test` image is build on top of `app`. It includes source files needed for running tests (i.e. `tests/` folder)

Docker images are divided using seperate folders in `dockers/images/`.

Dockerfiles are prefixed with name of image (i.e. `frontend.Dockerfile`) to allow specific dockerignore file (i.e. `frontend.Dockerfile.dockerignore`) being taken into account. We cannot use one shared dockerignore file due to different requirements from each of our images. Additionally well defined dockerignore file allows to achieve a slim build (lower number of layers).

## Image versioning

We are using seperate versioning for each image to achieve quick builds and avoid unnecessary builds.

Please remember that some images depend on each other which means you will need to increment version for all of depending images. Guide below:

-   Incrementing version of `core` image should also increment version of `backend` and `app`
-   Incrementing version of `composer` image should also increment version of `backend` and `app`
-   Incrementing version of `backend` image should also increment version of `app`
-   Incrementing version of `frontend` image should also increment version of `app`
-   Incrementing version of `scep` image should also increment version of `app`
-   Incrementing version of `app` image should also increment version of `test`

Please keep in mind that `app` image version **WILL** be used as application version. This means it will be compared by `entrypoint.sh` to decide whether a migration should be performed and will be presented in WebUI. Please follow recommendations from `Versioning lifecycle` section.

Versioning of images other then `app` is just for internal development and automation purposes. They can be versioned separately from each other.

## Filestorage volume

`app` image is using `filestorage` volume mounted in `/var/www/application/filestorage`. Folder is used to store following data:

-   `/letsencrypt` - certbot directory
-   `/logs` - logs directory
-   `/internal` - directory used internally by application (i.e. `application.version` file)
-   `/public` - directory used to store uploaded files that should be publicly visible via `nginx` (i.e. branding uploads)
-   `/private` - directory used to store uploaded files that should NOT be publicly visible via `nginx` (i.e. firmware uploads)

## Logging

Application handles logs in two ways:

-   By storing them in `filestorage` volume in `/logs` directory. Logs from different applications are separated using directories. They are also rotated daily, kept for 30 days and compressed (except for 3 newest logs which are kept uncompressed for easy usage).
-   By redirecting them to appropriate I/O streams: STDOUT (`/dev/stdout`) or STDERR (`/dev/stderr`) which are then handled by docker and can be further redirected to i.e. `syslog`.

We are handling following application logs:

-   Symfony logs are stored in `filestorage` volume in `/logs/symfony` directory
-   supervisor are stored in `filestorage` volume in `/logs/supervisor` directory. Additionally supervisor logs are redirected to STDOUT (`/dev/stdout`)
-   crontab execution logs are stored in `filestorage` volume in `/logs/crontab` directory
-   nginx access and error logs are stored in `filestorage` volume in `/logs/nginx` directory. Additionally error logs are redirected to STDERR (`/dev/stderr`)
-   php-fpm (WWW) logs are stored in `filestorage` volume in `/logs/php-fpm` directory
-   php (CLI) logs are stored in `filestorage` volume in `/logs/php` directory

Note! supervisor is reponsible for running `nginx` and `php-fpm` and it redirects their STDOUT to `/dev/stdout` and STDERR to `/dev/stderr`. When `nginx` and `php-fpm` redirects anything to STDOUT or STDERR it goes through supervisor. This means `nginx` and `php-fpm` are responsible for logging to `filestorage`.

## Building and publishing images

File `Makefile` provides an easy way of building and pushing docker image. It has following capabilities:

You can see possible commands by running `make help`.

Each of docker images uses it's own `.env` file (i.e. `app` uses `docker/images/app/.env`). This file contains version and registry image that will be used to build and push.

## Production `compose.yaml` and `.env`

Those files are located in `docker/deployments/production/` folder and their purpose is to hold default `compose.yaml` file and variables to be used further by clients while deploying the application.
