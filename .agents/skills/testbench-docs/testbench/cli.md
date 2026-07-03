# Command-line Interface

Testbench provides support for a command line tool equivalent to `artisan` command where you can interact directly with the stub Laravel application. 

For example instead of refreshing the database on every test you may now seed the database before running `phpunit`, using:

```bash
vendor/bin/testbench migrate
```

or, you can setup the application such as installing Passport:

```bash
vendor/bin/testbench passport:install
```

Both commands allow you to setup the testing environment where before you were only able to use the `TestCase::setUp()` method. 

While the `testbench` CLI was originally designed to help with testing, you might be able to run other features to help package development such as running `ide-helper:models` etc.

::: warning NOTES & CONSIDERATIONS

The command wouldn't work for file stubbing as the generated file will be based on the booted Laravel application and not your package directories.
:::

[[toc]]

## Available Commands

`testbench` CLI also introduces a few commands to help your package's development.

### Create SQLite Database

This command creates `database/database.sqlite` on your package's Laravel skeleton. You can invoke it by running the following command:

```bash
vendor/bin/testbench package:create-sqlite-db
``` 

### Drop SQLite Database

This command drop `database/database.sqlite` on your package's Laravel skeleton. You can invoke it by running the following command:

```bash
vendor/bin/testbench package:drop-sqlite-db
``` 

### Purge Skeleton

This command will purge the skeleton and reset everything to default. You can also configure `purge` section under the `testbench.yaml` configuration files such as:

```yaml
purge:
  directories:
    - public/vendor/*
  files:
    - lang/*.json
```

You can invoke it by running the following command:

```bash
vendor/bin/testbench package:purge-skeleton
```
