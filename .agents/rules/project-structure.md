---
trigger: always_on
description: High-level map of this Laravel package, key entry points, and where things live
---

## Repository Overview

- **Package type**: Laravel package with a `workbench` app for local development.
- **Autoload & package metadata**: see [`composer.json`](md:composer.json).
- **Main package namespace**: `Coderstm\\...` under [`src/`](md:src/).

## Key Locations

- **Service Providers and Bootstrap**
  - Providers live in [`src/Providers/`](md:src/Providers). The primary registration points for routes, views, config, migrations, and events are here.
  - Package surface/root helper: [`src/Coderstm.php`](md:src/Coderstm.php)

- **HTTP Layer**
  - Controllers: [`src/Http/Controllers/`](md:src/Http/Controllers)
  - Middleware: [`src/Http/Middleware/`](md:src/Http/Middleware)
  - Resources/Transformers: [`src/Http/Resources/`](md:src/Http/Resources)
  - Routing helpers: [`src/Http/Routing/`](md:src/Http/Routing)

- **Domain / Models**
  - Eloquent models: [`src/Models/`](md:src/Models)
  - Policies: [`src/Policies/`](md:src/Policies)
  - Events/Listeners/Jobs: [`src/Events/`](md:src/Events), [`src/Listeners/`](md:src/Listeners), [`src/Jobs/`](md:src/Jobs)
  - Services and business logic: [`src/Services/`](md:src/Services)
  - Traits, Enums, Contracts: [`src/Traits/`](md:src/Traits), [`src/Enum/`](md:src/Enum), [`src/Contracts/`](md:src/Contracts)

- **Database & Migrations**
  - Migrations: [`database/migrations/`](md:database/migrations)
  - Factories: [`database/factories/`](md:database/factories)
  - Seed/test app schema for workbench: [`workbench/database/`](md:workbench/database)

- **Config, Views, Translations**
  - Package config: [`config/coderstm.php`](md:config/coderstm.php)
  - Views (blade + html): [`resources/views/`](md:resources/views)
  - Translations: [`resources/lang/`](md:resources/lang)

- **Console & Commands**
  - Artisan commands: [`src/Commands/`](md:src/Commands)

- **Testing**
  - Test setup: [`tests/`](md:tests), [`phpunit.xml.dist`](md:phpunit.xml.dist), [`tests/BaseTestCase.php`](md:tests/BaseTestCase.php)
  - Testbench configuration: [`testbench.yaml`](md:testbench.yaml)

- **Workbench (local app for manual testing)**
  - Entry points under [`workbench/`](md:workbench) including routes, config, and a demo app structure.

## Frontend Assets

- Public assets for demo/workbench: [`public/`](md:public)
- Build tooling presets: [`webpack.mix.js`](md:webpack.mix.js), [`webpack.quasar.mix.js`](md:webpack.quasar.mix.js)

## Stubs & Scaffolding

- Package stubs for generators: [`stubs/`](md:stubs)

## Quick Orientation

1. Start at [`src/Providers/`](md:src/Providers) to see how the package registers itself.
2. Inspect domain models in [`src/Models/`](md:src/Models) and their migrations in [`database/migrations/`](md:database/migrations).
3. Explore HTTP entry points in [`src/Http/Controllers/`](md:src/Http/Controllers) and views in [`resources/views/`](md:resources/views).
4. For CLI flows, check [`src/Commands/`](md:src/Commands).
