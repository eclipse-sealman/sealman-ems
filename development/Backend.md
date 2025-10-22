# Backend

Backend is using [Symfony](https://symfony.com/).

## Prerequisites

Symfony is a PHP framework and it uses MySQL compatible database (i.e. MySQL, MariaDB). Please install PHP 8.4 and necessary extensions (`composer install` will prompt you when some are missing). You also need WWW server (i.e. `nginx` or `apache`). You can follow production configuration used in `docker/images/app/assets/etc/nginx/application.conf`.

## Installation and configuration

Please install necessary PHP packages using [Composer](https://getcomposer.org/).

```bash
composer install
```

Copy `.env` file as `.env.local` and adjust it according to your environment (especially `DATABASE_URL`).

Setup file permissions [Setting up or Fixing File Permissions](https://symfony.com/doc/6.4/setup/file_permissions.html).

Generate public/private JWT keys for use in the application.

```bash
php bin/console lexik:jwt:generate-keypair
```

Clear cache.

```bash
php bin/console cache:clear
```

Create and initialize production database with synced migrations.

```bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all -n
php bin/console doctrine:fixtures:load -n --group=prod
```

You should be able to open the application.
