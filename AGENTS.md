# AGENTS.md

Guidance for coding agents working in this repository.

## Project overview

`xima/xima-typo3-frontend-edit` is a TYPO3 extension (`xima_typo3_frontend_edit`) that adds edit buttons to content elements in the frontend, so editors can jump into backend editing without leaving the frontend context.

- PHP: `~8.2 || ~8.3 || ~8.4 || ~8.5`
- TYPO3: `^13.4 || ^14.0`
- Namespace: `Xima\XimaTypo3FrontendEdit\` maps to `Classes/`

## Structure

- `Classes/Middleware/`: `ToolRendererMiddleware` injects JavaScript and CSS into HTML responses for logged-in backend users
- `Classes/Controller/`: `AjaxController` with the toggle, edit information and move (drag and drop) endpoints
- `Classes/Service/`: menu generators and button builders, `SettingsService`, `BackendUserService`, `UrlBuilderService`, `IconService`, `FlashMessageService`, `ResourceRendererService`, `Content/ContentMoveService`
- `Classes/Event/`: PSR-14 events `FrontendEditDropdownModifyEvent` and `FrontendEditPageDropdownModifyEvent`
- `Classes/EventListener/ContentElementMarkerEventListener.php`: wraps rendered `tt_content` records in HTML comment markers for the site setting `frontendEdit.markerBasedDetection`. The TypoScript condition in `Configuration/Sets/XimaTypo3FrontendEdit/setup.typoscript` gates it, so backend users get their own page cache entries. The listener itself must never check the backend user
- `Classes/EventListener/`, `Classes/Repository/`, `Classes/Enumerations/`, `Classes/Template/`, `Classes/Utility/`, `Classes/ViewHelpers/`
- `Configuration/`: `RequestMiddlewares.php`, `JavaScriptModules.php`, `Sets/XimaTypo3FrontendEdit/`
- `Resources/`: templates, language files, public assets
- `Tests/Unit/`, `Tests/Functional/`: PHPUnit suites
- `Tests/Playwright/`: end-to-end tests of the JavaScript integration chain
- `Tests/CGL/`: separate Composer project with code style and static analysis tools
- `Documentation/`: TYPO3 documentation source
- `.ddev/`: DDEV setup with TYPO3 13 and 14 instances

## Development commands

```bash
ddev start
ddev composer install
ddev install all               # or 13, 14
ddev 13 typo3 cache:flush      # TYPO3 commands per version
```

## Testing

```bash
composer test                  # unit + functional, no coverage driver needed
composer test:unit             # phpunit.xml, no TYPO3 bootstrap
composer test:functional       # phpunit.functional.xml, real TYPO3 on SQLite
composer test:coverage         # both suites, merged with phpcov into .Build/coverage
vendor/bin/phpunit --filter <name>
```

- Functional tests need `ext-intl`, so run them inside DDEV
- `test:coverage` sets `XDEBUG_MODE=coverage` and needs a coverage driver
- E2E tests need an installed TYPO3 instance (`ddev install 13` or `14`):

```bash
ddev exec -d /var/www/html/Tests/Playwright npm install
ddev exec -d /var/www/html/Tests/Playwright npx playwright install --with-deps chromium
ddev exec -d /var/www/html/Tests/Playwright npx playwright test
```

- CI (`.github/workflows/tests.yml`, reusable workflow) runs PHP 8.2 to 8.5 against TYPO3 13.4 and 14.3 with highest and lowest dependencies

## Code style and static analysis

Tools live in `Tests/CGL/` and run through `ddev cgl` (or `composer cgl`):

```bash
ddev cgl lint                  # composer, editorconfig, language, php, typoscript
ddev cgl fix
ddev cgl sca                   # PHPStan level 8
ddev cgl migration             # Rector
ddev cgl analyze               # composer-dependency-analyser
```

- Individual targets: `lint:php`, `lint:composer`, `lint:editorconfig`, `lint:language`, `lint:typoscript`
- Config files: `Tests/CGL/phpstan.neon`, `Tests/CGL/.php-cs-fixer.php`, `Tests/CGL/rector.php`
- Strict types are required
- CI runs the CGL workflow (`.github/workflows/cgl.yml`) on pushes to `main` and on pull requests

## Git workflow

- Commit format: `<type>: <description>` with type one of `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`
- Do not add co-author trailers
- Run lint, static analysis and tests before opening a pull request
