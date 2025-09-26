# Repository Guidelines

## Project Structure & Module Organization
- `src/`: Symfony PHP code (Controller, Entity, Repository, Service, Form, Serializer, Command, EventListener).
- `templates/`: Twig views. `templates/<feature>/*.html.twig`.
- `assets/`: Frontend (Vite, Vue 3, Tailwind). Vue files in `assets/**/*.vue`.
- `public/`: Web root (built assets, entry points).
- `config/`, `migrations/`, `translations/`: Framework and DB config.
- `tests/`: PHPUnit tests. `e2e/`: Playwright tests.
- `legacy/`: Legacy SQL/data used during DB init.
- `docs/`: Installation, contribution, and ops docs.

## Build, Test, and Development Commands
- `make init`: Start Docker, install Composer/NPM deps, build assets. App on `http://127.0.0.1:8000/`.
- `make docker-start`: Start stack (profile=`dev` by default). `make docker-stop` to stop.
- `make npm-watch`: Rebuild frontend on change. `npm run dev` for Vite dev server if needed.
- `make tests [path=tests/FeatureTest.php] [clear=true]`: Run PHPUnit (optionally reset test DB).
- `make phpstan`: Static analysis. `make php-cs`/`make php-cs-fix`: Style check/fix.
- DB: `make database-init`, `make database-migrate`, `make database-diff`.
- Utility: `make exec container=cafsite cmd=bash user=www-data`.

## Coding Style & Naming Conventions
- PHP: Symfony presets via php-cs-fixer (`@Symfony`, risky allowed). 4-space indent, short arrays, single quotes, no closing tag, avoid useless returns/elses.
- Structure: `src/Controller/FooController.php`, `src/Service/FooService.php`, `src/Entity/Foo.php`, Twig in `templates/foo/*.html.twig`.
- Frontend: TypeScript/Vue/Tailwind. Use Prettier (with Tailwind plugin) for formatting. Keep component files small and feature-scoped under `assets/`.

## Testing Guidelines
- Unit/functional: PHPUnit in `tests/` using `*Test.php`. Bootstrap via `tests/bootstrap.php`; coverage includes `src/` (see `phpunit.xml.dist`).
- E2E: Playwright in `e2e/` (baseURL `http://localhost:8000`). Example: `npx playwright test` (ensure app is running).
- Databases: For clean state, use `make database-init-test` or `make tests clear=true`.

## Commit & Pull Request Guidelines
- Commits: Conventional style in French (e.g., `feat: …`, `fix: …`, `refactor: …`), emojis allowed, reference issues in titles (e.g., `(#1234)`).
- PRs: Clear description in French, link the ClickUp ticket, include before/after screenshots for UI, note DB changes/migrations, and tick off tests, PHPStan, and PHP-CS.

## Security & Configuration
- Do not commit secrets. Use `.env.local` for local overrides; see `docs/installation.md` and `docs/environments.md`.
- Validate inputs and follow existing security patterns; GitGuardian checks run on PRs.
