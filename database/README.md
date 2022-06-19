# Database

The SQL files in this directory will be used for plugin activation and migration.

## Filenames

The files must be named with the current timestamp in the format `%Y%m%dT%H%M%S.sql` (shell command:
`echo $(date +%Y%m%dT%H%M%S).sql`). They will be loaded automatically by the plugin.

## Schema & Table Names

The schema must be called `collectme` and every occurrence of the schema name as well as every table name **must be
enclosed in backticks**. Tables must always be referenced with the schema name (e.g. `` `collectme`.`tableName` ``).

## How the WordPress Plugin Uses these SQL Files 

The plugin automatically replaces schema and table names. On network activation the SQL statements are applied to every 
site.

### Plugin Activation

The plugin runs all *.sql files in this directory (after replacing schema and table names) in ascending order by 
filename (oldest first).

### Plugin Update

The plugin runs all *.sql files with greater filenames than the last one, the plugin has already run. 

### Plugin Deactivation

The database is not changed.

### Plugin Uninstallation

The plugin removes all tables with the `{wp_prefix}_collectme_` prefix.