# Repository Guidelines

## Project Structure & Module Organization
- `src/` contains the WordPress plugin shipped to production. Entry point: `src/woo-retailcrm.php`.
- Core PHP logic lives in `src/include/` (API client, domain models, validators, components, WooCommerce adapters).
- Frontend/admin assets are in `src/assets/{js,css}`.
- Translations are split between source POT files in `resources/pot/` and compiled language files in `src/languages/`.
- Automated tests live in `tests/` with subfolders mirroring source domains (`order/`, `customer/`, `models/`, `validators/`, etc.).
- Supplemental docs are in `doc/`; CI workflow is in `.github/workflows/woo.yml`.

## Build, Test, and Development Commands
- `composer install` installs dev dependencies (PHPUnit and polyfills).
- `make test` runs PHPUnit using `phpunit.xml.dist`.
- `make local_test` installs a WP/Woo test environment, then runs tests.
- `make run_tests` starts MySQL via Docker Compose and executes `make local_test` in the app container.
- `make compile_pot` compiles `.pot` files into `.mo` language binaries.
- `make build_archive` creates a release ZIP from `src/` (output in `/tmp`).

## Coding Style & Naming Conventions
- PHP style in this repo uses 4-space indentation and WordPress-compatible patterns.
- Class names follow `WC_Retailcrm_*`; file names follow `class-wc-retailcrm-*.php`.
- Test files use `test-*.php` naming and mirror source concepts.
- Prefer existing WordPress/WooCommerce APIs and hooks over custom abstractions where possible.

## Testing Guidelines
- Framework: PHPUnit (`phpunit/phpunit` 8.x, with polyfills for WP compatibility).
- Bootstrap: `tests/bootstrap.php`; suite config in `phpunit.xml.dist`.
- Place tests near related domain folders (example: `tests/order/test-wc-retailcrm-order.php`).
- Run `make test` before opening a PR; use `make run_tests` when validating in a clean containerized environment.

## Commit & Pull Request Guidelines
- Follow the existing commit style: short imperative subject, e.g. `Fix ICML directory on WP hosting (#386)`.
- Keep commits focused by concern (API, loyalty, ICML, validators, etc.).
- PRs should include: problem statement, scope of changes, test evidence (command run + result), and linked issue/PR when applicable.
- If UI/admin behavior changes, include screenshots or a short screencast.

## Version Bump Workflow
- Trigger phrase: `сделай бамп до (новая версия) с описанием (описание)`.
- Parse inputs directly from the phrase:
  - `(новая версия)` -> target module version.
  - `(описание)` -> changelog line text.
- Update `src/readme.txt`:
  - set `Stable tag` to the new version;
  - add in `== Changelog ==`:
    - `= новая версия =`
    - `* описание`
- Update `src/uninstall.php`: set `@version` to the new version.
- Update `src/woo-retailcrm.php`:
  - `Version` -> new version;
  - `const MODULE_VERSION` -> new version.
- Update `CHANGELOG.md` at the top:
  - `## YYYY-MM-DD новая версия`
  - `* описание`
- Update `VERSION`: replace file contents with the new version.
