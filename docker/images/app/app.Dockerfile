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

ARG BACKEND_REGISTRY_IMAGE
ARG BACKEND_VERSION
ARG FRONTEND_REGISTRY_IMAGE
ARG FRONTEND_VERSION
ARG SCEP_REGISTRY_IMAGE
ARG SCEP_VERSION

FROM ${FRONTEND_REGISTRY_IMAGE}:${FRONTEND_VERSION} AS frontend
FROM ${SCEP_REGISTRY_IMAGE}:${SCEP_VERSION} AS scep

FROM ${BACKEND_REGISTRY_IMAGE}:${BACKEND_VERSION}

# An ARG instruction goes out of scope at the end of the build stage where it was defined. FROM defines new build stage
ARG APP_VERSION

LABEL version=${APP_VERSION}
LABEL description="SEALMAN EMS v${APP_VERSION}"

# Copy compiled frontend into current container
COPY --from=frontend "/var/www/application/public/app" "/var/www/application/public/app"
# Copy generated javascript-licenses.csv file into license folder
COPY --from=frontend "/var/www/application/licenses/javascript-licenses.csv" "/var/www/application/licenses/javascript-licenses.csv"
# Copy compiled scep into current container
COPY --from=scep "/var/www/application/bin/scep" "/var/www/application/bin/scep"

COPY /docker/images/app/assets /

RUN chmod 644 /etc/logrotate.d/logrotate.conf
# Default logrotate for nginx is invalid for alpine. It has fixed and included in our logrotate.conf
RUN rm /etc/logrotate.d/nginx

COPY /docker/images/app/entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

ENV APP_VERSION=$APP_VERSION
WORKDIR /var/www/application
EXPOSE 80 443 18443