---
prev: false
next: false
---

# Introduction

Workbench provides a set of configurable actions and commands to allow previewing, interacting, and serving your Laravel packages during development. 

This component is accessible via `orchestra/testbench` without any further requirements.

## Installing

You can install **Workbench** using the following command:

```bash
composer require --dev "orchestra/testbench"
```

Next, you can run the following command to scaffold your package with the recommended setup:

```bash
vendor/bin/testbench workbench:install
```

## Version Compatibility

 Laravel  | Workbench   
:---------|:-----------
 9.x      | 7.x
 10.x     | 8.x
 11.x     | 9.x
 12.x     | 10.x

## Configuration

Please refer to [Configuration](/getting-started/configuration#workbench-configuration) documentation for further details.

## Getting Started

Workbench provides helpers on top of Testbench to allow package developers to interacts and preview Laravel packages without having the need to require it in a Laravel application. 

### `serve` Command

Similar to `composer run dev` available in latest Laravel application, you can preview the configured package using the following command:

```bash
composer run serve
```
