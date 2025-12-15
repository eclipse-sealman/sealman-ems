# Doctrine migrations

## Design assumptions

-   Doctrine migration file should be created for each issue when database change or files change is required
-   `preUp` and `postUp` methods should be used to handle database content updates and files changes
-   `up` method is used to change database schema
-   Migration file MUST **NOT** BE dependent on application code
    -   because if application code will change it might influence migration file execution and this will mean additional maintenance work
    -   needed code should be copied into migration file
    -   general packages like `Arr`, `Symfony Filesystem`, etc. can be used, but keep in mind that using them will require additional maintenance when upgrading packages
    -   usage of container should be limited to `Symfony parameters`, and used with caution

## General comments

Default migration template is overridden by `config/migrations/DoctrineMigrationTemplate.php.template`. This template includes additional reminder in comments and information about which task is connected to this migration. Please fill `#TODO_ISSUE_NUMBER Issue title` with a task number and a brief functionality name (i.e. `#13 Migration produces an error due to lack of migrations`) and fill `version vTODO` with a version number. We do not support nor implement `down()` function.

## Generating migration

Use following command:

```
php bin/console doctrine:migrations:diff
```

## Doctrine migrations limitations

-   Doctrine migrations are executed in sequence (preUp(), up(), postUp() -> next migration file) until exception occurs
-   When exception occurs changes in database are to be rollback, however
    -   **!IMPORTANT!** when any of schema change SQL is executed previous changes are automatically committed, eg. CREATE TABLE, ALTER TABLE, DROP TABLE, etc.
-   Doctrine migration code is executed as written in methods, except in up() method each addSql() method is just collecting SQL to be executed when up method is finished
    -   **!IMPORTANT!** $this->addSql() line DOES NOT EXECUTE SQL during up() method execution
-   Any $this->connection-> sql execution, executes immediately

## Example SQL statements

For details see vendor/doctrine/dbal/src/Connection.php

Many ways to use UPDATE (INSERT is similar)

```
$this->connection->update('user', ['extra_field' => 'SecretValue'], ['username' => 'system']);
$this->connection->executeStatement('UPDATE user SET `extra_field`= "SecretValue" WHERE `username` = "system"');
$this->connection->executeStatement('UPDATE user SET `extra_field`= ? WHERE `username` = ?', ['SecretValue', 'system']);
```

Getting lastInsertId

```
$deviceTypeId = $this->connection->fetchOne('SELECT id FROM device_type WHERE `name`= ?', ['TK800']);
if (!$deviceTypeId) {
    throw new InvalidDatabaseStateException('deviceType TK800 not found');
}

$this->connection->executeStatement('INSERT INTO template (`device_type_id`, `name`) VALUES (?,?)', [$deviceTypeId, 'template1']);
$templateId = $this->connection->lastInsertId();
if (!$templateId) {
    throw new InvalidDatabaseStateException('Last insert ID not found');
}

$this->connection->insert('template_version', ['template_id' => $templateId, 'device_type_id' => $deviceTypeId, 'name' => 'templateVersion1', 'type' => 'staging']);
```

Safely inserting records (with check if already exists)

```
$tk500v3Count = $this->connection->fetchOne(
    'SELECT count(*) FROM device_type WHERE `name` = ? OR `slug` = ? OR `route_prefix` = ?',
    ['TK500v3', 'tk500v3', '/router/tk500-v3']
);

if ($tk500v3Count > 0) {
    // TK500v3 already exists do nothing
    return;
}

//This code is just example (not valid amount of properties)
$this->connection->insert('device_type', [
    'name' => 'TK500v3',
    'icon' => 'router',
    'device_name' => 'Router',
    'color' => '#AF0AE1',
    'slug' => 'tk500v3',
    'certificate_common_name_prefix' => 'tk5003',
    'enabled' => 1,
]);
```
