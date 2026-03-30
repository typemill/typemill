---
name: architecture Typemill
description: Code rules for Vue components and templates in Typemill
---

# Typemill Architecture

## 1. Overview

Typemill is a PHP-based flat-file content management system.

Core characteristics:

* Flat-file architecture (no database for content)
* Markdown as primary content format
* YAML files for configuration and metadata
* Hierarchical content structure using folders and subfolders
* Focus on simplicity, lightweight technology, and intuitive user experience
* Strong emphasis on extensibility through themes and plugins

The filesystem is the single source of truth for content and structure.

## 2. Technology Stack

### Backend

* PHP 8.1 – 8.4
* Slim 4
* PHP-DI
* Twig
* Symfony YAML
* Symfony Event Dispatcher
* Parsedown + Parsedown Extra
* Valitron (validation)
* Laminas ACL
* Slim CSRF
* Slim Flash

### Admin UI

* Vue.js (Options API only)
* Tailwind CSS
* Axios
* Sortable / vuedraggable
* highlight.js
* autosize.js
* Included via script tags (no module system, no bundler)

### Frontend (Website/Themes)

* Twig
* HTML
* Tachyons CSS (preferred)

## 3. Architectural Style

Typemill follows a lightweight MVC-inspired architecture built on Slim 4.

Main characteristics:

* Slim 4 as HTTP kernel and router
* PSR-7 request/response handling
* PSR-15 middleware stack
* PHP-DI as dependency injection container
* Event-driven extensibility via Symfony Event Dispatcher
* Twig as rendering layer
* Parsedown for Markdown parsing

### Responsibilities

Controllers:

* Handle request/response flow.
* Delegate business logic to models/services.
* Return JSON or rendered responses.
* Must remain thin.

Models:

* Contain business logic.
* Handle filesystem interaction.
* Build navigation.
* Manage content parsing.
* Manage users and permissions.
* Controllers must not access filesystem directly.

Twig:

* Presentation only.
* No business logic.

## 4. Directory Structure

```
/cache              Temporary public cache files (twig cache, sitemap)
/content            Markdown content and folder hierarchy
/data               Derived navigation cache and plugin data
/media              Uploaded media files
/plugins            Feature extensions
/settings           Configuration files
/system/vendor      Third-party libraries
/system/typemill    Core application
/themes             Themes (templates and assets)
```

Each directory has a clearly defined responsibility and must not mix concerns.

`/data` and `/cache` are always derived and may be deleted safely.

## 5. Typemill Core (`/system/typemill`)

Core application structure:

* `/system.php` – Boot logic and lifecycle
* `/Plugin.php` – Base plugin class
* `/Assets.php` – Frontend asset management
* `/author` – Admin interface (Vue.js)
* `/Controllers` – HTTP controller layer
* `/Events` – System events
* `/Extensions` – Twig and Parsedown extensions
* `/Middleware` – Authentication, authorization, sessions, security, CORS
* `/Models` – Business logic (filesystem, content, users, navigation)
* `/routes` – Route definitions
* `/settings` – Default YAML settings
* `/Static` – Static helpers (session, slugs, plugins, translations)

The core defines system behavior and lifecycle.

## 6. API Design

Typemill provides internal and external REST APIs.

* All endpoints are defined in `/system/typemill/routes`.
* All API responses return JSON.
* Controllers must not return raw arrays.

### Internal API

* Used by Vue admin interface.
* Session-based authentication.
* Authorization via Laminas ACL.
* No JWT.

### External API

* Used for external data access.
* HTTP Basic Authentication.
* Authorization via Laminas ACL.
* No JWT unless explicitly specified.

### Validation

* All input validation must use Valitron.
* Authorization must use Laminas ACL.

## 7. Request Lifecycle

### Admin Request (`/tm/*`)

1. Slim receives request.
2. Authentication middleware validates session.
3. Controller executes logic via models.
4. JSON or HTML frame returned.
5. Vue handles UI.
6. Axios performs API requests.

### Frontend Request

1. Slim receives HTTP request.
2. Middleware stack executes.
3. Route resolves to controller.
4. Navigation model resolves path.
5. Content file located via filesystem.
6. Markdown parsed to HTML.
7. Event dispatcher triggers hooks.
8. Twig renders active theme.
9. Response returned.

## 8. Content Model

### Content Storage

* Stored in `/content`.
* Folder hierarchy defines structure.
* URLs derived from filesystem.
* `filename.md` – Markdown content.
* `filename.txt` – Block-based internal representation.
* `filename.yaml` – Metadata.
* `index.*` – Folder index content.

Filesystem defines hierarchy and URL structure.

### Navigation

* Built recursively from `/content`.
* Cached in `/data/navigation`.
* Extended page index cached for lookup.
* Cache is derived and never authoritative.
* Navigation must always reflect filesystem state.

Primary models:

* `/Models/folder.php`
* `/Models/navigation.php`

### Media

* Stored in `/media`.
* Image renditions in `/original`, `/live`, `/thumb`, `/custom`.
* Files in `/media/files`.

### State Rules

* No database for content.
* Navigation cache must be rebuildable.
* `/data` and `/cache` are disposable.
* No persistent runtime state outside filesystem and session.

## 9. Settings (`/settings`)

* All configuration stored in YAML.
* Default system configuration: `/system/typemill/settings`
* User-modified config: `/settings/system.yaml`
* Secrets: `/settings/secrets.yaml`
* Users: `/settings/user/*.yaml`

## 10. Forms

* Forms defined via YAML.
* Used in content meta tabs, system settings, plugins, themes, users.

Frontend (themes):

* Rendered with PHP form builder in `/Models/Fields.php` and `Field.php`.

Admin:

* Rendered with Vue form builder in `/author/vue/vue-forms.js`.

## 11. Vue.js Rules

* Vue Options API only.
* No Composition API.
* No ES modules.
* No bundler.
* Included via script tags.
* Components defined inside global Vue apps.
* Templates, props, computed, methods inside component definitions.
* Custom event dispatcher for component communication.

All JS must run without build tooling.

## 12. Themes (`/themes`)

* Responsible for presentation only.
* Must not contain business logic.
* Must not modify core behavior.
* Follow rules in `@ai/docs/theme-development.md`.

## 13. Plugins (`/plugins`)

Plugins extend functionality.

Plugins may:

* Register event listeners.
* Add routes.
* Add middleware.
* Extend Twig.
* Add admin UI tabs.
* Define forms.
* Store derived data in `/data`.

Plugins must not:

* Modify or override core classes.
* Replace navigation model.
* Modify Slim bootstrap.
* Introduce global state.
* Introduce alternative authentication systems.

Follow rules in `@ai/docs/plugin-development.md`.

## 14. Extension Invariants

When extending Typemill:

* Never modify core for new features.
* Use plugins for feature extensions.
* Themes are presentation-only.
* Filesystem is the single source of truth.
* Navigation must reflect folder hierarchy.
* Avoid global state.
* Respect Slim middleware architecture.
* Keep controllers thin and logic inside models/services.

These invariants preserve long-term maintainability and architectural consistency.


## 0. Hard Constraints (Non-Negotiable)

* Do not introduce a database for content storage.
* Do not introduce JWT unless explicitly requested.
* Do not refactor the core architectural style (Slim 4 + PHP-DI).
* Do not modify `/system/typemill` for new features (only for bug fixes).
* Do not override core classes from plugins.
* Do not convert Vue to Composition API.
* Do not introduce ES modules, bundlers, or build steps in the admin UI.
* Do not bypass models to access filesystem directly in controllers.
* Do not introduce global state.
* Cache and `/data` contents must always be disposable.

These constraints preserve system integrity and prevent architectural drift.
