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

for package in `apk info -aq` 
do
    version=`apk info --license $package | grep license | awk -F " " '{print $1}'`
    type=`apk info --license -v $package  | awk -F ": " '{print $2}'`
    description=`apk info -d -v $package  | awk -F ": " '{print $2}'`
    echo \"$package@$version\",\"$package\",\"$version\",\"$type\",\"$description\" >> /var/www/application/licenses/apk-licenses.csv
done