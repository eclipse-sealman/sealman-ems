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

applicationName="SEALMAN"
applicationDir="/var/www/application"
filestorageDir="$applicationDir/filestorage"
appVersionDir="$filestorageDir/internal"
appVersionFile="$appVersionDir/application.version"
archiveDir="$applicationDir/archive"
archiveBackupDir="$archiveDir/backup"
console="$applicationDir/bin/console"
envFile="$applicationDir/.env.local"
php="php"

# Variable used to skip creating maintenance logs. Used via restoreDatabase
skipMaintenanceLog="0"

phpConsole="$php $console"

# $1 - Text to echo as critical (i.e. "No space")
function echoCritical {
    dateTime=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[CRITICAL] $applicationName: ($dateTime maintenance.sh) $1"
}

# $1 - Text to echo as error (i.e. "Invalid parameter")
function echoError {
    dateTime=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[Error] $applicationName: ($dateTime maintenance.sh) $1"
}

# $1 - Text to echo as warning (i.e. "File should exist. Creating anyway")
function echoWarning {
    dateTime=$(date '+%Y-%m-%d %H:%M:%S')
    # Double space here is used to keep all messages from echoX in the same vertical position
    echo "[Warning] $applicationName: ($dateTime maintenance.sh) $1"
}

# $1 - Text to echo as info (i.e. "Processing parameter")
function echoInfo {
    dateTime=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[Info] $applicationName: ($dateTime maintenance.sh) $1"
}

# $1 - Text to echo as debug (i.e. "Processing skipped")
function echoDebug {
    dateTime=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[Debug] $applicationName: ($dateTime maintenance.sh) $1"
}

# $1 - Maintenance job ID
# $2 - Log message
# $3 - Log level
function log {
    if [[ "$skipMaintenanceLog" = "0" ]]; then
        $phpConsole app:maintenance:log "$1" "$2" "$3"
    fi
}

function logDebug {
    echoDebug "$2"
    log "$1" "$2" "debug"
}

function logInfo {
    echoInfo "$2"
    log "$1" "$2" "info"
}

function logWarning {
    echoWarning "$2"
    log "$1" "$2" "warning"
}

function logError {
    echoError "$2"
    log "$1" "$2" "error"
}

function logCritical {
    echoCritical "$2"
    log "$1" "$2" "critical"
}

# $1 - Maintenance job ID
# $2 - Backup file path (optional) 
function finishSuccess {
    echoInfo "Marking maintenance job as finished with success status. Backup file path \"$2\""
    $phpConsole app:maintenance:finish $1 success $2
}

# $1 - Maintenance job ID
function finishFailed {
    echoInfo "Marking maintenance job as finished with failed status"
    $phpConsole app:maintenance:finish $1 failed
}

# $1 - Maintenance job ID
# $2 - Directory to dump contents of MySQL database (i.e. /var/www/application/archive)
# $3 - Filename to dump contents of MySQL database (i.e. database.sql)
# Returns 0 on success and 1 on error
function backupDatabase {
    maintenanceId=$1

    logInfo $maintenanceId "Database backup started"

    backupDir=$2
    backupFilename=$3
    backupPath="$backupDir/$backupFilename"

    logDebug $maintenanceId "Reading database connection parameters"

    dbName=`$phpConsole app:maintenance:database-name`
    if [ $? -ne 0 ];
    then
        logCritical $maintenanceId "Command app:maintenance:database-name has failed"
        return 1
    fi

    dbConnectionString=`$phpConsole app:maintenance:database-connection-parameters`
    if [ $? -ne 0 ];
    then
        logCritical $maintenanceId "Command app:maintenance:database-connection-parameters has failed"
        return 1
    fi

    if [[ ! -d "$backupDir" ]]; then
        mkdir --parents "$backupDir"
        logDebug $maintenanceId "Creating $backupDir"
    fi

    if [[ ! -d "$backupDir" ]]; then
        logCritical $maintenanceId "Backup dir $backupDir does not exist"

        return 1
    fi

    if [[ -f "$backupPath" ]]; then
        logDebug $maintenanceId "Previously created backup exists. Removing $backupPath"
        rm -f "$backupPath"
        rmResult=$?

        if [[ $rmResult -ne 0 ]]; then
            logError $maintenanceId "Could not remove previously created backup. rm command exited with status $rmResult"

            return 1
        fi
    fi

    # Dump everything except `maintenance` and `maintenance_log` table
    mariadb-dump -R $dbConnectionString --max-allowed-packet=32M --single-transaction --skip-add-locks $dbName --ignore-table="$dbName.maintenance" --ignore-table="$dbName.maintenance_log" > "$backupPath"
    mariadbDumpResult=$?
    # Found one case of invalid mariadb-dump exit result
    # mariadb-dump: Error: 'Access denied; you need (at least one of) the PROCESS privilege(s) for this operation' when trying to dump tablespaces
    # In this case mariadbDumpResult = 0 (do not know why). Some data has been dumped in this case. I leave it as it is since do not see a way to validate it
    if [[ mariadbDumpResult -ne 0 ]]; then
        logError $maintenanceId "Could not dump contents of MySQL database. First mariadb-dump command exited with status $mariadbDumpResult"

        return 1
    fi

    # Add `maintenance` table without current maintenance job
    mariadb-dump -R $dbConnectionString --max-allowed-packet=32M --single-transaction --skip-add-locks $dbName maintenance --where "id != $maintenanceId" >> "$backupPath"
    mariadbDumpResult=$?
    if [[ $mariadbDumpResult -ne 0 ]]; then
        logError $maintenanceId "Could not dump contents of MySQL database. Second mariadb-dump command exited with status $mariadbDumpResult"

        return 1
    fi

    # Add `maintenance_log` table without current maintenance job
    mariadb-dump -R $dbConnectionString --max-allowed-packet=32M --single-transaction --skip-add-locks $dbName maintenance_log --where "maintenance_id != $maintenanceId" >> "$backupPath"
    mariadbDumpResult=$?
    if [[ $mariadbDumpResult -ne 0 ]]; then
        logError $maintenanceId "Could not dump contents of MySQL database. Third mariadb-dump command exited with status $mariadbDumpResult"

        return 1
    fi

    if [[ -f "$backupPath" ]]; then
        logDebug $maintenanceId "Contents of MySQL database dumped to $backupPath"
        logInfo $maintenanceId "Database backup finished"

        return 0
    else
        logError $maintenanceId "Could not dump contents of MySQL database"

        return 1
    fi
}

# $1 - Maintenance job ID
# $2 - Path and filename to loads contents of MySQL database (i.e. /var/www/application/archive/backup/example/database.sql)
# Returns 0 on success and 1 on error
function restoreDatabase {
    maintenanceId=$1

    logInfo $maintenanceId "Restore database started"

    sqlFile=$2

    if [[ ! -f "$sqlFile" ]]; then
        logError $maintenanceId "SQL file $sqlFile does not exist"

        return 1
    fi

    logDebug $maintenanceId "Reading database connection parameters"

    dbName=`$phpConsole app:maintenance:database-name`
    if [ $? -ne 0 ];
    then
        logCritical $maintenanceId "Command app:maintenance:database-name has failed"
        return 1
    fi

    dbConnectionString=`$phpConsole app:maintenance:database-connection-parameters`
    if [ $? -ne 0 ];
    then
        logCritical $maintenanceId "Command app:maintenance:database-connection-parameters has failed"
        return 1
    fi

    mariadb --max-allowed-packet=32M $dbConnectionString $dbName < $sqlFile
    mariadbResult=$?
    if [[ $mariadbResult -ne 0 ]]; then
        logError $maintenanceId "Could not load contents of SQL file. mariadb command exited with status $mariadbResult"

        return 1
    fi

    # Database has been restored. Skip creating further maintenance logs 
    skipMaintenanceLog="1"

    logInfo $maintenanceId "Database restore finished"

    return 0
}

# $1 - Maintenance job ID
# $2 - Directory to store backup contents (i.e. /var/www/application/archive/backup/example)
# $3 - Filename to store backup contents (i.e. filesystem.tar)
# Returns 0 on success and 1 on error
function backupFilestorage {
    maintenanceId=$1

    logInfo $maintenanceId "Filesystem backup started"

    backupDir=$2
    backupFilename=$3
    backupPath="$backupDir/$backupFilename"

    if [[ ! -d "$backupDir" ]]; then
        mkdir --parents "$backupDir"
        logDebug $maintenanceId "Creating $backupDir directory"
    fi

    if [[ ! -d "$backupDir" ]]; then
        logCritical $maintenanceId "Backup dir $backupDir does not exist"

        return 1
    fi

    logDebug $maintenanceId "Creating tar archive from $filestorageDir saved in $backupPath"

    tar -cf "$backupPath" -C "$filestorageDir" . 2>&1
    tarResult=$?

    if [[ $tarResult -ne 0 ]]; then
        logError $maintenanceId "Could create tar archive. tar command exited with status $tarResult"

        return 1
    fi

    if [[ ! -f $backupPath ]]; then
        logError $maintenanceId "Tar archive has not been created"

        return 1
    fi

    logInfo $maintenanceId "Filesystem backup finished"

    return 0
}

# $1 - Maintenance job ID
# $2 - Path and filename to tar archive with contents of filestorage backup (i.e. /var/www/application/archive/backup/example/docker-filestorage.tar)
# Returns 0 on success and 1 on error
function restoreFilestorage {
    maintenanceId=$1

    logInfo $maintenanceId "Filesystem restore started"

    restorePath="$2"

    if [[ ! -f "$restorePath" ]]; then
        logError $maintenanceId "Tar archive $restorePath does not exist"

        return 1
    fi

    logDebug $maintenanceId "Extracting tar archive from $restorePath to $filestorageDir"
    tar -C "$filestorageDir" --overwrite -xf "$restorePath" 2>&1
    tarResult=$?

    if [[ $tarResult -ne 0 ]]; then
        logError $maintenanceId "Could extract tar archive. tar command exited with status $tarResult"

        return 1
    fi

    logInfo $maintenanceId "Filesystem restore finished"

    return 0
}

# $1 - Maintenance job ID
# $2 - Directory to create archive from (i.e. /var/www/application/archive/backup/example)
# $3 - Zip archive path and filename (i.e. /var/www/application/archive/backup/example.zip)
# $4 - Password (optional)
# Returns 0 on success and 1 on error
function createZipArchive {
    maintenanceId=$1

    logInfo $maintenanceId "Creating zip archive started"

    archiveDir="$2"
    archiveFilename="$3"
    archivePassword="$4"

    if [ -n "$archivePassword" ]; then
        logDebug $maintenanceId "Creating encrypted zip archive from $archiveDir saved in $archiveFilename"
        zip -j -r -e -P "$archivePassword" "$archiveFilename" "$archiveDir" 2>&1
    else
        logDebug $maintenanceId "Creating zip archive from $archiveDir saved in $archiveFilename"
        zip -j -r "$archiveFilename" "$archiveDir" 2>&1
    fi

    zipResult=$?
    if [[ $zipResult -ne 0 ]]; then
        logError $maintenanceId "Could not create zip archive. zip command exited with status $zipResult"

        return 1
    fi

    if [[ ! -f $archiveFilename ]]; then
        logError $maintenanceId "Zip archive has not been created"

        return 1
    fi

    logInfo $maintenanceId "Creating zip archive finished"

    return 0
}

# $1 - Maintenance job ID
# $2 - Zip archive path and filename (i.e. /var/www/application/archive/backup/example.zip)
# $3 - Directory to extract archive (i.e. /var/www/application/archive/backup/example)
# $4 - Password (optional)
# Returns 0 on success and 1 on error
function extractZipArchive {
    maintenanceId=$1

    logInfo $maintenanceId "Extracting zip archive started"

    zipArchive="$2"
    extractDir="$3"
    archivePassword="$4"

    mkdir --parents "$extractDir" 2>&1
    mkdirResult=$?
    if [[ $mkdirResult -ne 0 ]]; then
        logError $maintenanceId "Could not extract directory $extractDir. mkdir command exited with status $mkdirResult"

        return 1
    fi

    if [[ ! -d $extractDir ]]; then
        logError $maintenanceId "Extract directory $extractDir not been created"

        return 1
    fi

    if [ -n "$archivePassword" ]; then
        logDebug $maintenanceId "Extracting encrypted zip archive from $zipArchive to $extractDir"
        unzip -o -P "$archivePassword" "$zipArchive" -d "$extractDir" 2>&1
    else
        logDebug $maintenanceId "Extracting zip archive from $zipArchive to $extractDir"
        # -P "\n" is used in case of encrypted zip to avoid getting stuck on password prompt
        unzip -o -P "\n" "$zipArchive" -d "$extractDir" 2>&1
    fi

    unzipResult=$?
    if [[ $unzipResult -ne 0 ]]; then
        logError $maintenanceId "Could not extract zip archive. unzip command exited with status $unzipResult"

        return 1
    fi

    extractedFilesCount=`ls -1 $extractDir | wc -l`
    if [ $extractedFilesCount -eq 0 ]; then
        logError $maintenanceId "Directory used for extracting zip archive is empty"

        return 1
    fi

    return 0
}

# $1 - Maintenance job ID
function backupForUpdate {
    maintenanceId=$1

    logInfo $maintenanceId "Backup for update started"

    if [[ ! -f "$appVersionFile" ]]; then
        echoError "Application version file $appVersionFile does not exist. Please restart container to fix this issue"
        finishFailed $maintenanceId

        return
    fi

    appVersion=`cat $appVersionFile`
    echoDebug "Detected application version $appVersion"

    backupForUpdateFile="backup-for-update-$appVersion.sql"
    backupDatabase $maintenanceId "$archiveDir" "$backupForUpdateFile"
    backupDatabaseResult=$?

    if [[ $backupDatabaseResult -ne 0 ]]; then
        finishFailed $maintenanceId

        return
    fi

    logInfo $maintenanceId "Backup for update finished"
    finishSuccess $maintenanceId "$backupForUpdateFile"
}

# $1 - Maintenance job ID
# $2 - Maintenance start parameters
function backup {
    maintenanceId=$1

    logInfo $maintenanceId "Backup started"

    backupDatabase=$(echo "$2" | cut -f3 -d " ")
    backupFilestorage=$(echo "$2" | cut -f4 -d " ")
    backupPassword=$(echo "$2" | cut -f5 -d " " | base64 -d)

    dateTime=$(date '+%Y-%m-%d_%H-%M-%S_%Z')
    backupName="$dateTime-backup"

    if [[ "$backupDatabase" = "1" ]]; then
        logDebug $maintenanceId "Backup will include database"
        backupName="$backupName-database"
    fi

    if [[ "$backupFilestorage" = "1" ]]; then
        logDebug $maintenanceId "Backup will include filestorage"
        backupName="$backupName-filestorage"
    fi

    backupDir="$archiveBackupDir/$backupName"

    if [[ "$backupDatabase" = "1" ]]; then
        backupDatabase $maintenanceId "$backupDir" "database.sql"
        backupDatabaseResult=$?

        if [[ $backupDatabaseResult -ne 0 ]]; then
            finishFailed $maintenanceId

            return
        fi
    fi

    if [[ "$backupFilestorage" = "1" ]]; then
        backupFilestorage $maintenanceId "$backupDir" "docker-filestorage.tar"
        backupFilestorageResult=$?

        if [[ $backupFilestorageResult -ne 0 ]]; then
            finishFailed $maintenanceId

            return
        fi
    fi

    backupFilename="$backupName.zip"

    createZipArchive $maintenanceId "$backupDir" "$archiveBackupDir/$backupFilename" "$backupPassword"
    createZipArchiveResult=$?
    if [[ $createZipArchiveResult -ne 0 ]]; then
        finishFailed $maintenanceId

        return
    fi

    logDebug $maintenanceId "Removing backup directory $backupDir"
    rm -rf "$backupDir"

    logInfo $maintenanceId "Backup finished"
    finishSuccess $maintenanceId "$backupFilename"
}

# $1 - Maintenance job ID
# $2 - Maintenance start parameters
function restore {
    maintenanceId=$1

    logInfo $maintenanceId "Restore started"

    restoreDatabase=$(echo "$2" | cut -f3 -d " ")
    restoreFilestorage=$(echo "$2" | cut -f4 -d " ")
    restoreFilepath=$(echo "$2" | cut -f5 -d " ")
    restorePassword=$(echo "$2" | cut -f6 -d " " | base64 -d)

    if [[ ! -f "$restoreFilepath" ]]; then
        logError $maintenanceId "Restore file $restoreFilepath does not exist"
        finishFailed $maintenanceId

        return
    fi

    restoreDirectory=`echo $restoreFilepath | sed "s/\.zip//g"`

    extractZipArchive $maintenanceId "$restoreFilepath" "$restoreDirectory" "$restorePassword"
    extractZipArchiveResult=$?

    if [[ $extractZipArchiveResult -ne 0 ]]; then
        finishFailed $maintenanceId

        return
    fi

    if [[ "$restoreFilestorage" = "1" ]]; then
        restoreFilestorage $maintenanceId "$restoreDirectory/docker-filestorage.tar"
        restoreFilestorageResult=$?

        if [[ $restoreFilestorageResult -ne 0 ]]; then
            finishFailed $maintenanceId

            return
        fi
    fi

    if [[ "$restoreDatabase" = "1" ]]; then
        # Restore database is setting skipMaintenanceLog = "1"
        restoreDatabase $maintenanceId "$restoreDirectory/database.sql"
        restoreDatabaseResult=$?

        if [[ $restoreDatabaseResult -ne 0 ]]; then
            finishFailed $maintenanceId

            return
        fi
    fi

    logDebug $maintenanceId "Removing restore directory $restoreDirectory"
    rm -rf "$restoreDirectory"

    logInfo $maintenanceId "Restore finished"
    # We cannot always set this maintenace job as finished successfully because after restore database it does not exist
    if [[ "$skipMaintenanceLog" != "1" ]]; then
        finishSuccess $maintenanceId
    fi
}

echoDebug "Execution started"

startResult=`$phpConsole app:maintenance:start`
if [[ -n "$startResult" ]]; then
    echoInfo "Pending maintenance job found. Parameters \"$startResult\""
    maintenanceId=$(echo "$startResult" | cut -f1 -d " ")
    maintenanceType=$(echo "$startResult" | cut -f2 -d " ")

    case $maintenanceType in
        "backupForUpdate") backupForUpdate "$maintenanceId" ;;
        "backup") backup "$maintenanceId" "$startResult" ;;
        "restore") restore "$maintenanceId" "$startResult" ;;
        *) echoError "Unknown maintenance type \"$maintenanceType\""
    esac
else
    echoDebug "There is no pending maintenance job or one is already running"
fi

echoDebug "Execution finished"
