# SEALMAN EMS

SEALMAN EMS is a solution for centralized management of all your distributed devices in the field, including setup with zero-touch provisioning and OTA configuration and firmware updates. It is optimized to manage large fleets of devices in the field with minimal effort.

Template-based approach is designed for easy scaling while ensuring secure connectivity and device registration. The API first approach allows you to easily integrate SEALMAN EMS with your ERP, monitoring or cloud, enabling a high level of automation without user intervention. Of course, you can also use the user-friendly web interface for easy operation.

## Prerequisites

SEALMAN EMS is available as a docker image. Please install [Docker](https://www.docker.com/) to build and run the application.

## Build

You can build is using `Makefile`. Clone this repository simply run:

```bash
make build
```

This will build all images. Images are split into smaller ones to achieve quick and slim builds. The `app` image is the one that should be run as a main application.

## Run

You can run application using [Docker Compose](https://docs.docker.com/compose/). Pre-made `compose.yaml` with available configuration using `.env` file is located in `docker/deployments/production/`.

You can run application with configured database service by navigating to `docker/deployments/production/` directory and running:

```bash
docker compose up
```

After it has finished booting up you can visit [http://localhost](http://localhost) to view the application.

## REST API documentation

REST API is described using OpenAPI Specification (formerly Swagger Specification). It is available for each user (Admin, User with device management permissions, User with VPN permissions).

You can access them as follows:

-   Human friendly documentation visualized using Swagger UI
    -   Admin: `/web/doc/admin`
    -   User with device management permissions: `/web/doc/smartems`
    -   User with VPN permissions: `/web/doc/vpnsecuritysuite`
-   Download OAS 3.0 as `yaml` file
    -   Admin: `/web/doc/admin.yaml`
    -   User with device management permissions: `/web/doc/smartems.yaml`
    -   User with VPN permissions: `/web/doc/vpnsecuritysuite.yaml`

Please be aware that documentation for each user can be disabled in application settings.

## Development

Please navigate to `development/README.md` directory to read more about our development process.
