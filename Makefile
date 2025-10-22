# Copyright (c) 2025 Contributors to the Eclipse Foundation.
#
# See the NOTICE file(s) distributed with this work for additional
# information regarding copyright ownership.
#
# This program and the accompanying materials are made available under the
# terms of the Apache License, Version 2.0 which is available at
# https://www.apache.org/licenses/LICENSE-2.0
#
# SPDX-License-Identifier: Apache-2.0

SHELL=/bin/sh

.SILENT:

include docker/images/test/.env
include docker/images/app/.env
include docker/images/frontend/.env
include docker/images/backend/.env
include docker/images/scep/.env
include docker/images/composer/.env
include docker/images/core/.env

startTime := $(shell date +%s)

define HELP_BODY
Images are divided in following way:

-   \033[0;33m`composer`\033[0m image is responsible for downloading and installing composer
-   \033[0;93m`scep`\033[0m image is responsible for compiling scep binary
-   \033[0;94m`frontend`\033[0m image is responsible for building frontend application that is used as WebUI
-   \033[0;35m`core`\033[0m image holds os packages needed to run the application
-   \033[0;32m`backend`\033[0m image is build on top of \033[0;35m`core`\033[0m. Uses \033[0;33m`composer`\033[0m to copy compiled composer binary. Prepares Symfony application which includes: copying necessary files, using composer to install packages and setting up permissions
-   \033[0;96m`app`\033[0m image is build on top of \033[0;32m`backend`\033[0m. Uses \033[0;94m`frontend`\033[0m and \033[0;93m`scep`\033[0m image to copy compiled frontend application and scep binary. It configures and sets up used packages like `nginx`, `crontab` and `supervisor`. It is also responsible for database initialization or migration and Symfony initialization (i.e. generating JWT and secret keys).
-   \033[0;37m`test`\033[0m image is build on top of \033[0;96m`app`\033[0m. Copying files necessary for testing purposes.
endef

export HELP_BODY

help:
	echo ""
	@echo "$$HELP_BODY"
	echo ""
	echo "\033[1;33mmake help\033[0m - shows this help"
	echo ""
	echo "\033[1;31mmake build\033[0m - builds all docker images [core, scep, composer, backend, frontend, app]"
	echo "\033[1;31mmake push\033[0m - pushes all docker images to the registry with current tag [core, scep, composer, backend, frontend, app]"
	echo "\033[1;31mmake all\033[0m - executes build and push for all docker images [core, scep, composer, backend, frontend, app]"
	echo ""
	echo "\033[0;31mmake bfa-build\033[0m - builds source code dependent docker images [backend, frontend, app]"
	echo "\033[0;31mmake bfa-push\033[0m - pushes source code dependent docker images to the registry with current tag [backend, frontend, app]"
	echo "\033[0;31mmake bfa-all\033[0m - executes build and push for source code dependent docker images [backend, frontend, app]"
	echo "\033[0;31mmake bfa-build-push-app\033[0m - build for source code dependent docker images [backend, frontend, app] and pushes only [app] to registry with current tag"
	echo ""
	echo "\033[0;33mmake composer-build\033[0m - builds docker image [composer]"
	echo "\033[0;33mmake composer-push\033[0m - pushes docker image to the registry with current tag [composer]"
	echo "\033[0;33mmake composer-all\033[0m - executes build and push [composer]"
	echo "\033[0;93mmake scep-build\033[0m - builds docker image [scep]"
	echo "\033[0;93mmake scep-push\033[0m - pushes docker image to the registry with current tag [scep]"
	echo "\033[0;93mmake scep-all\033[0m - executes build and push [scep]"
	echo "\033[0;94mmake frontend-build\033[0m - builds docker image [frontend]"
	echo "\033[0;94mmake frontend-push\033[0m - pushes docker image to the registry with current tag [frontend]"
	echo "\033[0;94mmake frontend-all\033[0m - executes build and push [frontend]"
	echo "\033[0;35mmake core-build\033[0m - builds docker image [core]"
	echo "\033[0;35mmake core-push\033[0m - pushes docker image to the registry with current tag [core]"
	echo "\033[0;35mmake core-all\033[0m - executes build and push [core]"
	echo "\033[0;32mmake backend-build\033[0m - builds docker image [backend]"
	echo "\033[0;32mmake backend-push\033[0m - pushes docker image to the registry with current tag [backend]"
	echo "\033[0;32mmake backend-all\033[0m - executes build and push [backend]"
	echo "\033[0;96mmake app-build\033[0m - builds docker image [app]"
	echo "\033[0;96mmake app-push\033[0m - pushes docker image to the registry with current tag [app]"
	echo "\033[0;96mmake app-all\033[0m - executes build and push [app]"
	echo "\033[0;37mmake test-build\033[0m - builds docker image [test]"
	echo "\033[0;37mmake test-push\033[0m - pushes docker image to the registry with current tag [test]"
	echo "\033[0;37mmake test-all\033[0m - executes build and push [test]"

show-execution-time: finishTime = $(shell date +%s)
show-execution-time:
	echo "\033[0;36mExecution time: $(shell expr ${finishTime} - ${startTime} ) seconds\033[0m"


build: .core-build .scep-build .composer-build .backend-build .frontend-build .app-build .test-build show-execution-time

push: .core-push .scep-push .composer-push .backend-push .frontend-push .app-push .test-push show-execution-time

all: .core-build .core-push .scep-build .scep-push .composer-build .composer-push .backend-build .backend-push .frontend-build .frontend-push .app-build .app-push .test-build .test-push show-execution-time

.app-build:
	docker build --no-cache -t ${APP_REGISTRY_IMAGE}:${APP_VERSION} --build-arg BACKEND_REGISTRY_IMAGE=${BACKEND_REGISTRY_IMAGE} --build-arg BACKEND_VERSION=${BACKEND_VERSION} --build-arg FRONTEND_REGISTRY_IMAGE=${FRONTEND_REGISTRY_IMAGE} --build-arg FRONTEND_VERSION=${FRONTEND_VERSION} --build-arg SCEP_REGISTRY_IMAGE=${SCEP_REGISTRY_IMAGE} --build-arg SCEP_VERSION=${SCEP_VERSION} --build-arg APP_VERSION=${APP_VERSION} -f docker/images/app/app.Dockerfile .

app-build: .app-build show-execution-time

.app-push:
	docker push ${APP_REGISTRY_IMAGE}:${APP_VERSION} 

app-push: .app-push show-execution-time

app-all: .app-build .app-push show-execution-time


.core-build:
	docker build --no-cache -t ${CORE_REGISTRY_IMAGE}:${CORE_VERSION} -f docker/images/core/core.Dockerfile .

core-build: .core-build show-execution-time

.core-push:
	docker push ${CORE_REGISTRY_IMAGE}:${CORE_VERSION} 

core-push: .core-push show-execution-time

core-all: .core-build .core-push show-execution-time

.scep-build:
	docker build --no-cache -t ${SCEP_REGISTRY_IMAGE}:${SCEP_VERSION} -f docker/images/scep/scep.Dockerfile .

scep-build: .scep-build show-execution-time

.scep-push:
	docker push ${SCEP_REGISTRY_IMAGE}:${SCEP_VERSION} 

scep-push: .scep-push show-execution-time

scep-all: .scep-build .scep-push show-execution-time


.composer-build:
	docker build --no-cache -t ${COMPOSER_REGISTRY_IMAGE}:${COMPOSER_VERSION} -f docker/images/composer/composer.Dockerfile .

composer-build: .composer-build show-execution-time

.composer-push:
	docker push ${COMPOSER_REGISTRY_IMAGE}:${COMPOSER_VERSION} 

composer-push: .composer-push show-execution-time

composer-all: .composer-build .composer-push show-execution-time


.frontend-build:
	docker build --no-cache -t ${FRONTEND_REGISTRY_IMAGE}:${FRONTEND_VERSION} -f docker/images/frontend/frontend.Dockerfile .

frontend-build: .frontend-build show-execution-time

.frontend-push:
	docker push ${FRONTEND_REGISTRY_IMAGE}:${FRONTEND_VERSION} 

frontend-push: .frontend-push show-execution-time

frontend-all: .frontend-build .frontend-push show-execution-time


.backend-build:
	docker build --no-cache -t ${BACKEND_REGISTRY_IMAGE}:${BACKEND_VERSION} --build-arg CORE_REGISTRY_IMAGE=${CORE_REGISTRY_IMAGE} --build-arg CORE_VERSION=${CORE_VERSION} --build-arg COMPOSER_REGISTRY_IMAGE=${COMPOSER_REGISTRY_IMAGE} --build-arg COMPOSER_VERSION=${COMPOSER_VERSION} -f docker/images/backend/backend.Dockerfile .

backend-build: .backend-build show-execution-time

.backend-push:
	docker push ${BACKEND_REGISTRY_IMAGE}:${BACKEND_VERSION} 

backend-push: .backend-push show-execution-time

backend-all: .backend-build .backend-push show-execution-time


.test-build:
	docker build --no-cache -t ${TEST_REGISTRY_IMAGE}:${TEST_VERSION} --build-arg COMPOSER_REGISTRY_IMAGE=${COMPOSER_REGISTRY_IMAGE} --build-arg COMPOSER_VERSION=${COMPOSER_VERSION} --build-arg APP_REGISTRY_IMAGE=${APP_REGISTRY_IMAGE} --build-arg APP_VERSION=${APP_VERSION} -f docker/images/test/test.Dockerfile .

test-build: .test-build show-execution-time

.test-push:
	docker push ${TEST_REGISTRY_IMAGE}:${TEST_VERSION} 

test-push: .test-push show-execution-time

test-all: .test-build .test-push show-execution-time


bfa-build: .backend-build .frontend-build .app-build show-execution-time

bfa-push: .backend-push .frontend-push .app-push show-execution-time

bfa-all: .backend-build .backend-push .frontend-build .frontend-push .app-build .app-push show-execution-time

bfa-build-push-app: .backend-build .frontend-build .app-build .app-push show-execution-time