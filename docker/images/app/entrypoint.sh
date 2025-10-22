#!/bin/bash

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

# Sleep 0.25s to avoid missing first couple of logs after container restarts
# Do not know why this happens
sleep 0.25

applicationName="SEALMAN EMS"
applicationDir="/var/www/application"
applicationEnv=${APPLICATION_ENV:-"prod"}
envDevFile="$applicationDir/.env.dev.local"
envTestFile="$applicationDir/.env.test.local"
envProdFile="$applicationDir/.env.prod.local"
filestorageDir="$applicationDir/filestorage"
appVersionDir="$filestorageDir/internal"
appVersionFile="$appVersionDir/application.version"
archiveDir="$applicationDir/archive"
console="$applicationDir/bin/console"
php="php"

phpConsole="$php $console"

# $1 - Text to echo as error (i.e. "Invalid parameter")
function echoError {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $applicationName: $1"
}

# $1 - Text to echo as warning (i.e. "Processing took very long")
function echoWarning {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [Warning] $applicationName: $1"
}

# $1 - Text to echo as info (i.e. "Processing parameter")
function echoInfo {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [Info] $applicationName: $1"
}

# $1 - Text to echo as debug (i.e. "Processing skipped")
function echoDebug {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [Debug] $applicationName: $1"
}

# Initialize file to ensure that empty file exists
# $1 - File path
function initializeFile {
    if [[ -z "$1" ]]; then
        echoError "(initializeFile) Missing file path" 
        exit 1;
    fi

    if [[ -w "$1" ]]; then
        echoInfo "File $1 exists. Removing"
        rm "$1"
    fi
        
    echoInfo "Creating empty $1 file"
    touch "$1"
}

# Fill file with destination variable that has the value of source variable. Optionally pass default value when source variable is empty
# $1 - File path that should be filled with variable
# $2 - Source variable name (i.e. "PHP_post_max_size")
# $3 - Destination variable name (i.e. "post_max_size")
# $4 - [optional] Default value when source variable is empty (i.e. "20M")
# $5 - [optional] Should value be enclosed in double quotes (i.e. 0 or 1). Default is 0
# $6 - [optional] Should variable value be echoed (i.e. 0 or 1). Default is 1
function fillVariable {
    if [[ -z "$1" ]]; then
        echoError "(fillVariable) Missing file path" 
        exit 1;
    fi

    if [[ -z "$2" ]]; then
        echoError "(fillVariable) Missing source variable name" 
        exit 1;
    fi

    if [[ -z "$3" ]]; then
        echoError "(fillVariable) Missing destination variable name" 
        exit 1;
    fi

    localVariableValue=`printenv $2`
    if [[ -z "$localVariableValue" ]]; then
        if [[ -z "$4" ]]; then
            echoInfo "Variable $2 is empty. There is no default value. Adding to $1 skipped"
            return
        fi

        if [[ ${6-"1"} = "1" ]]; then
            echoInfo "Variable $2 is empty. Using default value $4"
        else
            echoInfo "Variable $2 is empty. Using default value"
        fi

        localVariableValue=$4
    fi

    if [[ ${5-"0"} = "1" ]]; then
        localVariableValue='"'$localVariableValue'"'
    fi

    localVariableDefinition="$3=$localVariableValue"
    if [[ ${6-"1"} = "1" ]]; then
        echoInfo "Adding $localVariableDefinition to $1 file"
    else
        echoInfo "Adding $3 (hidden for security) = ****** to $1 file"
    fi

    echo $localVariableDefinition >> $1
}

# Fill file with destination variable that has the value of source variable. Optionally pass default value when source variable is empty
# $1 - File path that should be filled with variable
# $2 - Source variable name (i.e. "APPLICATION_TRUSTED_HOSTS")
# $3 - Destination parameter name (i.e. "PARAMETER_TRUSTED_HOSTS")
# $4 - [optional] Default value when source variable is empty (i.e. "20M")
# $5 - [optional] Should value be enclosed in double quotes (i.e. 0 or 1). Default is 0
# $6 - [optional] Should value be enclosed in brackets [] (i.e. 0 or 1). Default is 0
function fillYamlDockerParameter {
    if [[ -z "$1" ]]; then
        echoError "(fillYamlDockerParameter) Missing file path" 
        exit 1;
    fi

    if [[ -z "$2" ]]; then
        echoError "(fillYamlDockerParameter) Missing source variable name" 
        exit 1;
    fi

    if [[ -z "$3" ]]; then
        echoError "(fillYamlDockerParameter) Missing destination variable name" 
        exit 1;
    fi

    localVariableValue=`printenv $2`
    if [[ -z "$localVariableValue" ]]; then
        if [[ -z "$4" ]]; then
            echoInfo "Variable $2 is empty. There is no default value. Adding to $1 skipped"
            return
        fi

        echoInfo "Variable $2 is empty. Using default value $4"
        localVariableValue=$4
    fi

    if [[ ${5-"0"} = "1" ]]; then
        localVariableValue='"'$localVariableValue'"'
    fi

    if [[ ${6-"0"} = "1" ]]; then
        localVariableValue='['$localVariableValue']'
    fi

    localVariableDefinition="  $3: $localVariableValue"
    echoInfo "Adding $localVariableDefinition to $1 file"
    sed -i "s#.*$3.*#$localVariableDefinition#g" $1
}

# Fill env files with variable
# $1 - Source variable name (i.e. "APPLICATION_ENV")
# $2 - Destination variable name (i.e. "APP_ENV")
# $3 - [optional] Should variable value be echoed (i.e. 0 or 1). Default is 1
function fillEnvVariable {
    # $1 and $2 will be checked by fillVariable
    fillVariable "$envDevFile" $1 $2 "" "0" ${3-"1"}
    fillVariable "$envTestFile" $1 $2 "" "0" ${3-"1"}
    fillVariable "$envProdFile" $1 $2 "" "0" ${3-"1"}
}

# Fill /usr/local/etc/php/conf.d/application.ini variable
# $1 - Source variable name (i.e. "PHP_post_max_size")
# $2 - Destination variable name (i.e. "post_max_size")
# $3 - Default value when source variable is empty (i.e. "20M")
# $4 - [optional] Should value be enclosed in double quotes (i.e. 0 or 1). Default is 0
function fillPhpVariable {
    # $1 and $2 will be checked by fillVariable

    if [[ -z "$3" ]]; then
        echoError "(fillPhpVariable) Missing default value" 
        exit 1;
    fi

    fillVariable "/usr/local/etc/php/conf.d/application.ini" $1 $2 $3 ${4-"0"}
}

# Fill /usr/local/etc/php-fpm.d/zzz_application_php-fpm.conf variable
# $1 - Source variable name (i.e. "APPLICATION_PHPFPM_pm_max_children")
# $2 - Destination variable name (i.e. "pm.max_children")
# $3 - Default value when source variable is empty (i.e. "5")
function fillPhpFpmVariable {
    # $1 and $2 will be checked by fillVariable

    if [[ -z "$3" ]]; then
        echoError "(fillPhpFpmVariable) Missing default value" 
        exit 1;
    fi

    fillVariable "/usr/local/etc/php-fpm.d/zzz_application_php-fpm.conf" $1 $2 $3
}

# Safely link source folder to destination folder
# $1 - Source folder (i.e. "filestorage/public/uploads")
# $2 - Destination folder (i.e. "public/uploads")
function linkFolder {
    if [[ -z "$1" ]]; then
        echoError "(linkFolder) Missing source folder" 
        exit 1;
    fi

    if [[ -z "$2" ]]; then
        echoError "(linkFolder) Missing destination folder" 
        exit 1;
    fi

    if [[ ! -d "$1" ]]; then
        echoDebug "Source folder missing. Creating $1"
        mkdir -p $1
    fi

    echoDebug "Linking source folder $1 to destination folder $2"
    # -f is used to avoid File exists feedback
    ln -f -s $1 $2
}

# Add custom HTTP headers to nginx configuration
# $1 - HTTP header name (i.e. "Access-Control-Allow-Origin")
# $2 - Env Variables name (i.e. "APPLICATION_HEADER_ACCESS_CONTROL_ALLOW_ORIGIN")
function addCustomHttpHeader {
    headerValue=`printenv $2`
    # If header value is empty, skip adding add_header directive
    if [[ -z "$headerValue" ]]; then
        return
    fi

    echoDebug "Adding custom HTTP header '$1' with value '$headerValue' to nginx configuration"
    echo "add_header '$1' '$headerValue' always;" >> /etc/nginx/snippets/application-custom-headers.conf
}

# Function adds custom HTTP headers to nginx configuration
# add_header directives are added to /etc/nginx/snippets/application-custom-headers.conf only if env variable value is set
function addCustomHttpHeaders {
    # Clearing configuration file
    echo "# Custom HTTP headers configuration" > /etc/nginx/snippets/application-custom-headers.conf

    addCustomHttpHeader "Access-Control-Allow-Credentials" "APPLICATION_HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS"
    addCustomHttpHeader "Access-Control-Allow-Headers" "APPLICATION_HEADER_ACCESS_CONTROL_ALLOW_HEADERS"
    addCustomHttpHeader "Access-Control-Allow-Methods" "APPLICATION_HEADER_ACCESS_CONTROL_ALLOW_METHODS"
    addCustomHttpHeader "Access-Control-Allow-Origin" "APPLICATION_HEADER_ACCESS_CONTROL_ALLOW_ORIGIN"
    addCustomHttpHeader "Access-Control-Expose-Headers" "APPLICATION_HEADER_ACCESS_CONTROL_EXPOSE_HEADERS"
    addCustomHttpHeader "Access-Control-Max-Age" "APPLICATION_HEADER_ACCESS_CONTROL_MAX_AGE"
    addCustomHttpHeader "Content-Security-Policy" "APPLICATION_HEADER_CONTENT_SECURITY_POLICY"
    addCustomHttpHeader "Cross-Origin-Embedder-Policy" "APPLICATION_HEADER_CROSS_ORIGIN_EMBEDDER_POLICY"
    addCustomHttpHeader "Cross-Origin-Opener-Policy" "APPLICATION_HEADER_CROSS_ORIGIN_OPENER_POLICY"
    addCustomHttpHeader "Cross-Origin-Resource-Policy" "APPLICATION_HEADER_CROSS_ORIGIN_RESOURCE_POLICY"
    addCustomHttpHeader "X-Content-Type-Options" "APPLICATION_HEADER_X_CONTENT_TYPE_OPTIONS"
    addCustomHttpHeader "X-Frame-Options" "APPLICATION_HEADER_X_FRAME_OPTIONS"
    addCustomHttpHeader "Strict-Transport-Security" "APPLICATION_HEADER_STRICT_TRANSPORT_SECURITY"
}

# Verify SSL certificate variables
# Returns:
# 0 - invalid certificate configuration
# 1 - correct empty certificate configuration (fallback to application.crt and application.key)
# 2 - correct certbot certificate configuration
# 3 - correct file certificate configuration
function verifySslCertificateVariables {
    sslCertbotDomains=`printenv APPLICATION_SSL_CERTIFICATE_CERTBOT_DOMAINS`
    sslCertbotEmail=`printenv APPLICATION_SSL_CERTIFICATE_CERTBOT_EMAIL`
    sslFileChain=`printenv APPLICATION_SSL_CERTIFICATE_FILE_CHAIN`
    sslFileKey=`printenv APPLICATION_SSL_CERTIFICATE_FILE_KEY`

    if [[ -z "$sslCertbotDomains" ]] && [[ -z "$sslCertbotEmail" ]] && [[ -z "$sslFileChain" ]] && [[ -z "$sslFileKey" ]]; then
        echoDebug "SSL certificate variables are empty"
        return 1
    fi

    if [[ -n "$sslCertbotDomains" ]] && [[ -n "$sslCertbotEmail" ]] && [[ -z "$sslFileChain" ]] && [[ -z "$sslFileKey" ]]; then
        echoDebug "SSL certificate variables are configured to use certbot"
        echoDebug "Validating certbot domains"

        regexFqdn="^([a-zA-Z0-9](([a-zA-Z0-9-]){0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$"
        IFS=', ' read -r -a sslCertbotDomainsArray <<< "$sslCertbotDomains"
        for sslCertbotDomain in "${sslCertbotDomainsArray[@]}"
        do
            if [[ "$sslCertbotDomain" =~ $regexFqdn ]]; then
                echoDebug "Domain ${sslCertbotDomain} is valid"
            else
                echoError "Domain ${sslCertbotDomain} is not valid"
                return 0
            fi
        done

        regexEmail="^[a-z0-9!#\$%&'*+/=?^_\`{|}~-]+(\.[a-z0-9!#$%&'*+/=?^_\`{|}~-]+)*@([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z0-9]([a-z0-9-]*[a-z0-9])?\$"
        if [[ "$sslCertbotEmail" =~ $regexEmail ]]; then
            echoDebug "Email ${sslCertbotEmail} is valid"
        else
            echoError "Email ${sslCertbotEmail} is not valid"
            return 0
        fi

        return 2
    fi

    if [[ -z "$sslCertbotDomains" ]] && [[ -z "$sslCertbotEmail" ]] && [[ -n "$sslFileChain" ]] && [[ -n "$sslFileKey" ]]; then
        echoDebug "SSL certificate variables are configured to use provided SSL certificates"
        echoDebug "Validating provided SSL certificates"

        if [ ! -f "$filestorageDir/$sslFileChain" ] ; then
            echoError "Provided SSL certificate ${sslFileChain} does not exist"
            return 0
        else
            echoDebug "Provided SSL certificate ${sslFileChain} exists"
        fi

        if [ ! -f "$filestorageDir/$sslFileKey" ] ; then
            echoError "Provided SSL certificate ${sslFileKey} does not exist"
            return 0
        else
            echoDebug "Provided SSL certificate ${sslFileKey} exists"
        fi

        sslFileChainMd5=`openssl x509 -noout -modulus -in "$filestorageDir/$sslFileChain" | openssl md5`
        sslFileKeyMd5=`openssl rsa -noout -modulus -in "$filestorageDir/$sslFileKey" | openssl md5`
        if [ "$sslFileChainMd5" != "$sslFileKeyMd5" ]; then
            echoError "Provided SSL certificates keys does not match"
            return 0
        else
            echoDebug "Provided SSL certificates keys match"
        fi

        return 3
    fi

    return 0
}

# Remove SSL certificates
function removeSslCertificates {
    echoDebug "Removing SSL certificates"

    rm -f /etc/nginx/application.crt
    rm -f /etc/nginx/application.key
}

# Remove certbot domains file
function removeSslCertbotDomainsFile {
    echoDebug "Removing SSL /etc/letsencrypt/ssl_certbot_domains file"

    rm -f /etc/letsencrypt/ssl_certbot_domains
}

# Install predefined SSL certificates
function installPredefinedSslCertificates {
    echoInfo "Using predefined SSL certificates"

    removeSslCertificates
    removeSslCertbotDomainsFile

    cp /etc/nginx/predefined.key /etc/nginx/application.key
    cp /etc/nginx/predefined.crt /etc/nginx/application.crt
}

# Install provided SSL certificates
function installSslFileCertificates {
    sslFileChain=`printenv APPLICATION_SSL_CERTIFICATE_FILE_CHAIN`
    sslFileKey=`printenv APPLICATION_SSL_CERTIFICATE_FILE_KEY`

    echoInfo "Installing provided SSL certificates"

    removeSslCertificates
    removeSslCertbotDomainsFile

    cp "$filestorageDir/$sslFileChain" /etc/nginx/application.crt
    cp "$filestorageDir/$sslFileKey" /etc/nginx/application.key
}

# Set up certbot to obtain and renew SSL certificates
function installCertbotCertificates {
    sslCertbotDomains=`printenv APPLICATION_SSL_CERTIFICATE_CERTBOT_DOMAINS`
    sslCertbotEmail=`printenv APPLICATION_SSL_CERTIFICATE_CERTBOT_EMAIL`

    echoInfo "Using certbot to obtain and renew SSL certificates"

    echoDebug "Starting nginx stump"
    /usr/sbin/nginx -c /etc/nginx/nginx-certbot-stump.conf

    if [ ! -d "$filestorageDir/letsencrypt" ] ; then
        echoDebug "Creating $filestorageDir/letsencrypt folder"
        mkdir -p $filestorageDir/letsencrypt
    fi

    # Letsencrypt should save data in filestorage volume
    ln -s $filestorageDir/letsencrypt /etc/letsencrypt

    if [ -f "/etc/letsencrypt/ssl_certbot_domains" ] && [[ "$(cat /etc/letsencrypt/ssl_certbot_domains)" == "$sslCertbotDomains" ]] ; then
        echoDebug "Certbot domains did not change since last run"

        echoDebug "Running certbot renew script"
        certbot renew --cert-name=application --webroot --webroot-path "$applicationDir/public" --post-hook "/usr/sbin/nginx -s reload"

        removeSslCertificates

        echoDebug "Linking SSL certificates obtained by certbot"
        ln -s /etc/letsencrypt/live/application/fullchain.pem /etc/nginx/application.crt
        ln -s /etc/letsencrypt/live/application/privkey.pem /etc/nginx/application.key

        echoDebug "Adding certbot renew script to /crontab.conf"
        echo "35 1 * * * certbot renew --cert-name=application --webroot --webroot-path $applicationDir/public --post-hook \"/usr/sbin/nginx -s reload\" >> /var/www/application/filestorage/logs/crontab/crontab.log 2>&1" >> /crontab.conf
    else
        echoDebug "Certbot domains did change since last run"

        removeSslCertbotDomainsFile
        if [ -d "/etc/letsencrypt/live/application" ]; then
            echoDebug "Removing previously generated certificates using certbot"
            certbot delete -n --cert-name=application
        fi

        echoDebug "Filling /etc/letsencrypt/ssl_certbot_domains file"
        touch /etc/letsencrypt/ssl_certbot_domains
        echo "$sslCertbotDomains" > /etc/letsencrypt/ssl_certbot_domains

        echoDebug "Running certbot script to obtain SSL certificates"
        # Build certbot domain parameters string
        IFS=', ' read -r -a array <<< "$sslCertbotDomains"
        sslCertbotDomainsParameter=""
        for sslCertbotDomain in "${array[@]}"
        do
            sslCertbotDomainsParameter="${sslCertbotDomainsParameter} -d ${sslCertbotDomain}"
        done

        certbot certonly --cert-name=application ${sslCertbotDomainsParameter} --webroot --webroot-path "$applicationDir/public" --email "$sslCertbotEmail" -n --rsa-key-size 4096 --agree-tos

        if [ -f "/etc/letsencrypt/live/application/fullchain.pem" -a -f "/etc/letsencrypt/live/application/privkey.pem" ]; then
            removeSslCertificates

            echoDebug "Linking SSL certificates obtained by certbot"
            ln -s /etc/letsencrypt/live/application/fullchain.pem /etc/nginx/application.crt
            ln -s /etc/letsencrypt/live/application/privkey.pem /etc/nginx/application.key

            echoDebug "Adding certbot renew script to /crontab.conf"
            echo "35 1 * * * certbot renew --cert-name=application --webroot --webroot-path /var/www/application/public --post-hook \"/usr/sbin/nginx -s reload\" >> /var/www/application/filestorage/logs/crontab/crontab.log 2>&1" >> /crontab.conf
        else
            echoError "Certbot could not obtain certificates"
            installPredefinedSslCertificates
        fi
    fi

    echoDebug "Stopping nginx stump"
    /usr/sbin/nginx -s stop
}

function createDatabaseSchema {
    echoInfo "Creating database schema"
    $phpConsole doctrine:schema:create
    echoInfo "Importing default fixtures"
    $phpConsole doctrine:fixtures:load -n --group=prod
    echoInfo "Adding doctrine migration versions"
    $phpConsole doctrine:migrations:sync-metadata-storage
    $phpConsole doctrine:migrations:version --add --all -n
}

function ensureEmptyDatabase {
    echoInfo "Ensuring database is empty"

    $phpConsole app:maintenance:database-verify-empty
    verifyResult=$?

    if [[ $verifyResult -ne 0 ]] ; then
        echoError "Database is not empty. Command app:maintenance:database-verify-empty exited with status $verifyResult. Shutting down."
        exit 1
    fi

    echoDebug "Database is empty. Command app:maintenance:database-verify-empty exited with status $verifyResult."
}

###> Prepare SSL certificate
verifySslCertificateVariables
verifyResult=$?

if [[ $verifyResult -eq 0 ]] ; then
    echoError "Invalid APPLICATION_SSL_CERTIFICATE_* configuration"
    installPredefinedSslCertificates
fi

if [[ $verifyResult -eq 1 ]] ; then
    installPredefinedSslCertificates
fi

if [[ $verifyResult -eq 2 ]] ; then
    installCertbotCertificates
fi

if [[ $verifyResult -eq 3 ]] ; then
    installSslFileCertificates
fi
###< Prepare SSL certificate

###> Configuring application, php and php-fpm
initializeFile "$envDevFile"
initializeFile "$envTestFile"
initializeFile "$envProdFile"
fillEnvVariable "APPLICATION_SESSION_TIMEOUT" "SESSION_TIMEOUT"
fillEnvVariable "APPLICATION_FEATURE_SCEP_ENABLED" "FEATURE_SCEP_ENABLED"
fillEnvVariable "APPLICATION_FEATURE_VPN_ENABLED" "FEATURE_VPN_ENABLED"
fillEnvVariable "APPLICATION_UPPERCASE_FIRMWARE_VERSIONS" "UPPERCASE_FIRMWARE_VERSIONS"
fillEnvVariable "APPLICATION_DATABASE_URL" "DATABASE_URL" "0"
fillEnvVariable "APPLICATION_TRUSTED_PROXIES" "TRUSTED_PROXIES"
fillEnvVariable "APP_VERSION" "APP_VERSION"
fillYamlDockerParameter docker_parameters.yml "APPLICATION_TRUSTED_HEADERS" "PARAMETER_TRUSTED_HEADERS" "forwarded,x-forwarded-for,x-forwarded-host,x-forwarded-proto,x-forwarded-port,x-forwarded-prefix" "1"
fillYamlDockerParameter docker_parameters.yml "APPLICATION_TRUSTED_HOSTS" "PARAMETER_TRUSTED_HOSTS" " " "0" "1"

fillYamlDockerParameter docker_parameters.yml "FILESTORAGE_DIR_NON_EXISTENT_VARIABLE" "FILESTORAGE_DIR" "$filestorageDir"
fillYamlDockerParameter docker_parameters.yml "APPLICATION_MYSQL_SSL_CA" "MYSQL_SSL_CA" ""
fillYamlDockerParameter docker_parameters.yml "APPLICATION_MYSQL_SERVER_VALIDATION" "MYSQL_SERVER_VALIDATION" ""
fillYamlDockerParameter docker_parameters.yml "APPLICATION_MYSQL_SSL_KEY" "MYSQL_SSL_KEY" ""
fillYamlDockerParameter docker_parameters.yml "APPLICATION_MYSQL_SSL_CERT" "MYSQL_SSL_CERT" ""

# Use recommended production php.ini configuration
cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
fillPhpVariable "APPLICATION_PHP_MAX_FILE_UPLOADS" "max_file_uploads" "20"
fillPhpVariable "APPLICATION_PHP_UPLOAD_MAX_FILESIZE" "upload_max_filesize" "20M"
fillPhpVariable "APPLICATION_PHP_MEMORY_LIMIT" "memory_limit" "256M"
fillPhpVariable "APPLICATION_PHP_POST_MAX_SIZE" "post_max_size" "20M"
fillPhpVariable "APPLICATION_PHP_MAX_INPUT_TIME" "max_input_time" "60"
fillPhpVariable "APPLICATION_PHP_MAX_EXECUTION_TIME" "max_execution_time" "60"
# php.ini date.timezone should be eclosed in quotes
fillPhpVariable "APPLICATION_PHP_TIMEZONE" "date.timezone" "$TZ" "1"

fillPhpFpmVariable "APPLICATION_PHPFPM_PM_MAX_CHILDREN" "pm.max_children" "5"
###< Configuring application, php and php-fpm


###> Regenerate JWT passphrase
echoInfo "Regenerating JWT passphrase"
jwtPassphrase=`openssl rand -hex 32`
echo "JWT_PASSPHRASE=$jwtPassphrase" >> "$envDevFile"
echo "JWT_PASSPHRASE=$jwtPassphrase" >> "$envTestFile"
echo "JWT_PASSPHRASE=$jwtPassphrase" >> "$envProdFile"
###< Regenerate JWT passphrase


###> Regenerate Symfony secret
echoInfo "Regenerating Symfony secret"
appSecret=`openssl rand -hex 32`
echo "APP_SECRET=$appSecret" >> "$envDevFile"
echo "APP_SECRET=$appSecret" >> "$envTestFile"
echo "APP_SECRET=$appSecret" >> "$envProdFile"
###< Regenerate Symfony secret


###> Starting and configuring crontab
echoInfo "Starting and configuring crontab"
crond
crontab /crontab.conf
###< Starting and configuring crontab


###> Optimizing
echoInfo "Optimizing"
$phpConsole dotenv:dump "$applicationEnv"
###< Optimizing


###> Configuring nginx
addCustomHttpHeaders

envVar=`printenv APPLICATION_DISABLE_HTTPS_REDIRECT`
if [[ "true" == "$envVar" || true == "$envVar" ]] ; then
    echoInfo "Using nginx configuration with disabled HTTPS redirect"
    cp /etc/nginx/application-disable-https-redirect.conf /etc/nginx/conf.d/application.conf
else
    echoInfo "Using default nginx configuration with enabled HTTPS redirect"
    cp /etc/nginx/application.conf /etc/nginx/conf.d/application.conf
fi
###< Configuring nginx

###> Testing database connection
echoInfo "Testing database connection"
testDatabaseConnectionCount=1
$phpConsole app:test:database-connection &> /dev/null
testDatabaseConnectionResult=$?

while [[ $testDatabaseConnectionResult -ne "0" ]]; do
    echoDebug "Could not connect to database. Command app:test:database-connection exited with status $testDatabaseConnectionResult. Retrying in 3 seconds ($testDatabaseConnectionCount/120 retries)"
    testDatabaseConnectionCount=$((testDatabaseConnectionCount + 1))

    if [ $testDatabaseConnectionCount -gt 120 ]; then
        echoError "Could not connect to database. Number of retries exceeded (120). Shutting down"
        exit 1
    fi

    sleep 3
    $phpConsole app:test:database-connection &> /dev/null
    testDatabaseConnectionResult=$?
done

echoInfo "Database connection is valid"
###< Testing database connection

###> Preparing database
echoInfo "Preparing database"

appVersion=`printenv APP_VERSION`
echoDebug "Detected current application version $appVersion"

if [[ -f "$appVersionFile" ]]; then
    echoDebug "Application version file $appVersionFile exists"
    previousAppVersion=`cat $appVersionFile`
    echoDebug "Detected previous application version $previousAppVersion"

    if [[ "$previousAppVersion" != "$appVersion" ]]; then
        echoDebug "Previous and current application versions are different"
        ensureEmptyDatabase

        backupForUpdateFile="$archiveDir/backup-for-update-$previousAppVersion.sql"

        if [[ -f "$backupForUpdateFile" ]]; then
            echoDebug "Backup for update file $backupForUpdateFile exists"
            backupForUpdateFileUpdatedAt=`date -r "$backupForUpdateFile" "+%Y-%m-%d %H:%M:%S"`
            # Line below calculates age of the file in days
            backupForUpdateFileAge=$((($(date +%s -r "$backupForUpdateFile")  - $(date +%s)) / 86400))

            if [[ $backupForUpdateFileAge -gt 1 ]]; then
                echoWarning "Backup for update file $backupForUpdateFile is $backupForUpdateFileAge days old (last updated on $backupForUpdateFileUpdatedAt)"
            else 
                echoDebug "Backup for update file $backupForUpdateFile last updated on $backupForUpdateFileUpdatedAt"
            fi

            dbName=`$phpConsole app:maintenance:database-name`
            if [ $? -ne 0 ];
            then
                echoError "Command app:maintenance:database-name has failed"
                exit 1
            fi

            dbConnectionString=`$phpConsole app:maintenance:database-connection-parameters`
            if [ $? -ne 0 ];
            then
                echoError "Command app:maintenance:database-connection-parameters has failed"
                exit 1
            fi

            echoDebug "Loading contents of MySQL database from $backupForUpdateFile"
            # Set connection timeout to 30 minutes
            mariadb --connect-timeout 1800 $dbConnectionString "$dbName" < "$backupForUpdateFile"
            mariadbResult=$?
            if [[ $mariadbResult -ne 0 ]]; then
                echoError $maintenanceId "Could not load contents of MySQL database from $backupForUpdateFile. mariadb command exited with status $mariadbResult"

                exit 1
            fi

            echoDebug "Updating database using migrations"

            $phpConsole doctrine:migrations:migrate --query-time -v -n

            migrationResult=$?
            if [[ $migrationResult -ne 0 ]]; then
                echoError "Migrations has failed."

                exit 1
            fi
        else
            echoWarning "Backup for update file $backupForUpdateFile does not exist"
            createDatabaseSchema
        fi
    else
        echoDebug "Previous and current application versions are the same"
    fi
else
    echoDebug "Application version file $appVersionFile does not exist"
    ensureEmptyDatabase
    createDatabaseSchema
fi

$phpConsole doctrine:schema:validate &> /dev/null
doctrineSchemaValidateResult=$?

if [[ $doctrineSchemaValidateResult -ne "0" ]]; then
    echoError "Database schema is invalid. Command doctrine:schema:validate exited with status $doctrineSchemaValidateResult. Shutting down"
    exit 1
fi

echoInfo "Database schema is valid"
###< Preparing database schema


###> Open source clearance
echoInfo "Preparing open source clearance"
cp $applicationDir/licenses/apk-licenses.csv $applicationDir/licenses/licenses.csv
cat $applicationDir/licenses/manual-licenses.csv >> $applicationDir/licenses/licenses.csv
cat $applicationDir/licenses/composer-licenses.csv >> $applicationDir/licenses/licenses.csv
cat $applicationDir/licenses/javascript-licenses.csv >> $applicationDir/licenses/licenses.csv
$phpConsole app:licenses:load
$phpConsole app:licenses:dump-txt
###< Open source clearance


###> Regenerate JWT public/private keys
echoInfo "Regenerating JWT public/private keys"
$phpConsole lexik:jwt:generate-keypair --overwrite --no-interaction
###< Regenerate JWT public/private keys


###> Clear symfony cache
echoInfo "Clearing Symfony cache"
$phpConsole cache:clear --env="$applicationEnv" &> /dev/null
###< Clear symfony cache


###> Folders and permissions
echoInfo "Setting up folders and permissions"
mkdir -p $applicationDir/archive/backup
chown www-data:www-data $applicationDir/archive/backup
linkFolder "$filestorageDir/public/uploads" "$applicationDir/public/"
linkFolder "$filestorageDir/private/firmware" "$applicationDir/private/"
mkdir -p $filestorageDir/logs/nginx
mkdir -p $filestorageDir/logs/crontab
mkdir -p $filestorageDir/logs/supervisor
mkdir -p $filestorageDir/logs/php-fpm
mkdir -p $filestorageDir/logs/php
mkdir -p $filestorageDir/logs/symfony
chown -R www-data:www-data $filestorageDir
chown -R www-data:www-data $applicationDir/var/cache
###< Folders and permissions

###> Logrotate configuration patch for azure folder permissions and ownership prevention
logrotateConfFile="/etc/logrotate.d/logrotate.conf"
# Check ownership of $filestorageDir/logs/symfony
# If it is not owned by www-data, patch logrotate configuration to workaround host system volume's folder's permissions and ownership prevention
folderOwner=$(stat -c '%U' "$filestorageDir/logs/symfony")
if [[ "$folderOwner" != "www-data" ]]; then
    echoInfo "Patching logrotate configuration to workaround host system volume's folder's permissions and ownership prevention"
    # Replace suPlaceholder with su root root
    sed -i "s/# suPlaceholder/su root root/g" "$logrotateConfFile"
fi
###< Logrotate configuration patch for azure folder permissions and ownership prevention


###> App version
echoInfo "Storing current version $appVersion in $appVersionFile"
mkdir -p "$appVersionDir"
echo "$appVersion" > "$appVersionFile"
###< App version


exec docker-php-entrypoint $@