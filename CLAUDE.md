# CLAUDE.md — Plateforme Club Alpin

This file provides AI assistants with a comprehensive guide to the codebase structure, conventions, and workflows for this project.

---

## Project Overview

**Plateforme Club Alpin** is a multi-club management platform for French Alpine clubs (CAF — Club Alpin Français). It is shared across three clubs:

| Club | URL |
|------|-----|
| Lyon-Villeurbanne | [clubalpinlyon.fr](https://www.clubalpinlyon.fr) |
| Chambéry | (separate instance) |
| Clermont-Ferrand | (separate instance) |

**Core features:**
- Outing (sortie) management: creation, registration, validation, ICS export
- Member (adhérent) synchronisation from FFCAM via nightly CSV files
- Expense reports (notes de frais) for event leaders
- Article/news publishing with commission-scoped visibility
- Commission (activity group) management
- CMS pages and content blocks
- JWT-authenticated REST API (consumed by a mobile app)
- Async email/alert queuing via Symfony Messenger

---

## Tech Stack

### Backend
| Component | Technology |
|-----------|------------|
| Language | PHP 8.3 |
| Framework | Symfony 6.4 (LTS) |
| ORM | Doctrine ORM 2.x with MySQL 8.0 |
| API | API Platform 3.2 |
| Auth | Lexik JWT Bundle + Gesdinet JWT Refresh |
| Async queue | Symfony Messenger (Doctrine transport) |
| PDF generation | DomPDF |
| Spreadsheet | PhpSpreadsheet |
| Image processing | Liip Imagine Bundle |
| Monitoring | Sentry |
| Email | Symfony Mailer + Mailgun |

### Frontend
| Component | Technology |
|-----------|------------|
| Templating | Twig 3 |
| CSS framework | TailwindCSS 3 |
| Build tool | Vite 5 (via pentatrion/vite-bundle) |
| Reactive components | Vue.js 3 (expense report form) |
| Form validation | VeeValidate + Zod |
| HTTP client (JS) | Axios |
| Legacy data tables | DataTables (pre-bundled in `public/tools/`) |
| Rich text editor | CKEditor 5 (pre-bundled in `public/js/`) |

### DevOps & Tooling
| Component | Technology |
|-----------|------------|
| Containerisation | Docker + docker-compose |
| Hosting | Clever Cloud (staging + production) |
| CI/CD | GitHub Actions |
| Static analysis | PHPStan level 1 |
| Code style | PHP-CS-Fixer (`@Symfony` ruleset) |
| E2E tests | Playwright 1.57 |
| Unit tests | PHPUnit 9.6 |
| Fixtures | Nelmio Alice |

---

## Repository Structure

```
plateforme-club-alpin/
├── assets/                    # Frontend source files (compiled by Vite)
│   ├── expense-report-form/   # Vue.js 3 expense report SPA
│   ├── styles/                # Global SCSS/CSS
│   ├── js/                    # Vanilla JS modules
│   └── tailwind.js            # Tailwind configuration entry
├── bin/
│   ├── console                # Symfony CLI entry point
│   ├── phpunit                # PHPUnit runner
│   └── tools/                 # phive-managed tools (phpstan, php-cs-fixer)
├── clevercloud/               # Clever Cloud deployment hooks + cron config
├── config/
│   ├── packages/              # Symfony bundle configuration (per-env overrides in dev/, prod/, test/)
│   ├── routes/                # Route loaders
│   ├── security.yaml          # Firewall, voters, access control
│   └── services.yaml          # DI service definitions
├── docs/                      # Project documentation (in French)
├── e2e/                       # Playwright end-to-end test specs
├── legacy/                    # Pre-2019 legacy PHP code (DO NOT add features here)
│   ├── data/
│   │   ├── schema_caf.sql     # Legacy DB schema (imported at DB init)
│   │   └── data_caf.sql       # Seed data
│   ├── includes/              # Legacy shared includes
│   └── pages/                 # Legacy PHP page controllers
├── migrations/                # 149 Doctrine migration files
├── public/                    # Web root (Apache document root)
│   ├── build/                 # Vite compiled assets (gitignored)
│   ├── js/                    # Pre-bundled CKEditor 5
│   └── tools/                 # Pre-bundled DataTables
├── src/                       # Main application (PHP)
│   ├── ApiResource/           # API Platform resource definitions
│   ├── Bridge/                # External service adapters (FFCAM, Firebase…)
│   ├── Command/               # Symfony console commands (cron jobs, sync)
│   ├── Controller/            # HTTP controllers
│   │   └── Api/               # API-specific controllers
│   ├── DataFixtures/          # Nelmio Alice fixture loaders
│   ├── Doctrine/              # Custom Doctrine types and extensions
│   ├── Dto/                   # Data Transfer Objects (API I/O)
│   ├── Entity/                # 44 Doctrine entities
│   ├── EventListener/         # Doctrine & kernel event listeners
│   ├── EventSubscriber/       # Symfony event subscribers
│   ├── Form/                  # Symfony form types
│   ├── Ftp/                   # FFCAM FTP file sync helpers
│   ├── Helper/                # Stateless utility helpers
│   ├── HtmlSanitizer/         # Custom sanitizer configurations
│   ├── Legacy/                # Bridge between legacy and Symfony code
│   ├── Mailer/                # Email templates and senders
│   ├── Messenger/             # Async message handlers (mails, alertes)
│   ├── Repository/            # Doctrine repositories
│   ├── Security/
│   │   ├── Voter/             # 26 Symfony security voters
│   │   └── Authentication/    # Magic link / JWT auth handlers
│   ├── Serializer/            # API Platform serializer normalizers
│   ├── Service/               # Business logic services
│   ├── State/                 # API Platform state providers/processors
│   ├── Trait/                 # PHP traits shared across entities
│   ├── Twig/                  # Custom Twig extensions and runtime
│   ├── Utils/                 # Pure utility functions
│   ├── Validator/             # Custom Symfony constraint validators
│   ├── UserRights.php         # Central permission checking service
│   └── Notifications.php      # Push notification helpers
├── templates/                 # Twig templates
├── tests/                     # PHPUnit test suite
│   ├── Controller/            # Integration tests for controllers
│   ├── Service/               # Unit tests for services
│   ├── Security/              # Security voter tests
│   └── WebTestCase.php        # Base test case (extends Symfony's)
├── .github/
│   ├── workflows/             # CI/CD GitHub Actions
│   └── actions/               # Reusable composite actions
├── docker-compose.yml         # Docker environment definition
├── Makefile                   # All dev commands (use these!)
├── phpunit.xml.dist           # PHPUnit configuration
├── phpstan.neon               # PHPStan configuration
└── .php-cs-fixer.dist.php     # PHP-CS-Fixer rules
```

---

## Development Setup

### Prerequisites
- Docker and docker-compose
- `make` (pre-installed on macOS/Linux; via Chocolatey on Windows)
- Node.js (for E2E tests and `npm` commands outside Docker)

### First-time setup

```bash
make init             # Start Docker, install PHP + JS deps, build frontend
make database-init    # Create DB, import legacy schema, run migrations, load fixtures
```

### Local access

| Service | URL | Credentials |
|---------|-----|-------------|
| App | http://127.0.0.1:8000/ | — |
| Admin panel | http://127.0.0.1:8000/admin/ | `admin@test-clubalpinlyon.fr` / `test` |
| PHPMyAdmin | http://127.0.0.1:8080/ | `root` / `test` |
| Mailcatcher | http://127.0.0.1:8025/ | — |

> Note: image uploads do not work in the Dockerised environment.

---

## Key Make Commands

All commands that run PHP/Composer/NPM execute **inside the `www_pca` Docker container** automatically. Run them from your host machine.

### App lifecycle

```bash
make docker-start       # Start all containers
make docker-stop        # Stop all containers
make cache-clear        # Clear Symfony cache
make logs               # Tail Docker logs
make exec               # Open bash in the PHP container
```

### Dependencies

```bash
make composer-install   # Install PHP dependencies
make composer-update    # Update PHP dependencies
make npm-install        # Install JS dependencies
make npm-build          # Build frontend assets (production)
make npm-watch          # Watch and rebuild frontend assets (development)
```

### Database

```bash
make database-init          # Full reset: drop → create → import schema → migrate → fixtures
make database-drop          # Drop the database
make database-create        # Create fresh DB + import legacy schema
make database-import        # Re-import legacy seed data
make database-migrate       # Run pending migrations
make database-migration     # Generate a new migration from entity changes
make database-diff          # Show migration diff without generating file
make database-migration-down # Roll back last migration
make database-fixtures-load  # Load Alice fixtures (dev environment)
make database-init-test     # Full reset for the test database
```

### Code quality

```bash
make php-cs             # Dry-run PHP-CS-Fixer (analyse only)
make php-cs-fix         # Apply PHP-CS-Fixer fixes
make php-cs-changed     # Check only staged/changed PHP files
make php-cs-fix-changed # Fix only staged/changed PHP files
make phpstan            # Run PHPStan static analysis (level 1)
make phpstan-files FILES="src/Foo.php src/Bar.php"  # Analyse specific files
```

### Testing

```bash
make tests                          # Run all PHPUnit tests
make tests path=tests/Controller/   # Run a specific test directory
make tests path=tests/Foo/BarTest.php  # Run a specific test file
make database-init-test             # Recreate test database (run before tests if schema changed)
make test-e2e                       # Run Playwright E2E tests
make test-e2e-headed                # Run E2E tests with browser visible
make test-e2e-report                # Open the last E2E test HTML report
```

### Async workers

```bash
make consume-mails      # Process pending email messages (limit 50)
make consume-alertes    # Process pending alert messages (limit 50)
```

---

## Coding Conventions

### PHP

- **PHP version:** 8.3 — use modern syntax (enums, readonly properties, named arguments, fibers if needed)
- **Attributes over annotations:** use PHP 8 `#[Route(...)]`, `#[IsGranted(...)]`, `#[ORM\Entity]` etc., never Doctrine XML or old-style doc-comment annotations
- **Constructor injection only:** no setter injection, no property injection
- **Short array syntax:** always `[]`, never `array()`
- **Single quotes** for strings unless interpolation is needed
- **No trailing comma** in multiline arrays (disabled in PHP-CS-Fixer config)
- **No `\` prefix** for built-in functions or constants
- **No useless `return`/`else`/`elseif`** (enforced by PHP-CS-Fixer)
- **`strict_comparison`** (`===` not `==`) is enforced

PHP-CS-Fixer applies `@Symfony`, `@Symfony:risky`, and `@DoctrineAnnotation` rulesets to `src/` and `tests/` only. Always run `make php-cs-fix` before committing.

### Naming & Language

- Domain terminology follows **French Alpine club vocabulary** (see [Business Domain Terminology](#business-domain-terminology) below)
- Class and method names are in **English** (e.g., `SortieController`, `getEncadrant()`)
- Variable names and comments may be in French where domain context is clearer
- Template variable names follow French domain terms (e.g., `sortie`, `adherent`)

### Entities

- All database tables use the **`caf_` prefix** (from legacy schema)
- Entities live in `src/Entity/` and use `#[ORM\Entity]` + `#[ORM\Table(name: 'caf_...')]` attributes
- Timestamps use the `TimestampableEntity` trait from Gedmo (`createdAt`, `updatedAt`)
- Relations always use explicit `#[ORM\JoinColumn]` with `nullable` specified

### Controllers

- Extend `Symfony\Bundle\FrameworkBundle\Controller\AbstractController`
- Use `#[Route]` attribute on class and/or method
- Use `#[IsGranted]` or `$this->denyAccessUnlessGranted()` for security checks
- API controllers live in `src/Controller/Api/`

### Security

- Authorisation uses **Symfony Voters** (26 voters in `src/Security/Voter/`)
- The `UserRights` service (`src/UserRights.php`) handles complex permission checks with caching
- Voters follow the naming pattern `{Domain}{Action}Voter` (e.g., `SortieCreateVoter`, `ArticleDeleteVoter`)

### Messenger / Async

- Two transports: `mails` (email sending) and `alertes` (push notifications)
- Message handlers live in `src/Messenger/`
- Consume with `make consume-mails` and `make consume-alertes`

### Frontend

- **Twig** is used for full-page rendering; Vue.js only for the expense report SPA
- **TailwindCSS** for all new styling — no custom CSS unless unavoidable
- **Vite** manages asset bundling; entry points are declared in `vite.config.ts`
- Format JS/TS with **Prettier** + `prettier-plugin-tailwindcss`

---

## Database Conventions

| Convention | Detail |
|------------|--------|
| Table prefix | `caf_` (all tables) |
| DB name (dev) | `caf` |
| DB name (test) | `caf_test` |
| DB credentials (dev/test) | `root` / `test` |
| Migration tool | Doctrine Migrations (`make database-migration`) |
| Fixture format | Nelmio Alice YAML (loaded by custom `caf:fixtures:load` command) |

**Always create a migration** when changing entity schema. Never modify the database directly. The legacy schema (`legacy/data/schema_caf.sql`) is imported first, then Doctrine migrations are applied on top.

---

## API Structure

- Base path: `/api`
- Authentication: `POST /api/auth` → returns `token` + `refresh_token`
- Token refresh: `POST /api/token/refresh`
- All endpoints require `Authorization: Bearer <token>` header (except `/api/docs` and `/api/config`)
- Resources follow API Platform conventions with serialization groups
- Pagination: `?page=1&itemsPerPage=30` (max 30 items per page)
- Full OpenAPI docs: `GET /api/docs` or run `make api-swagger` for Swagger UI

---

## Testing

### PHPUnit (unit + integration)

Tests live in `tests/` and mirror the `src/` directory structure. The base test case is `tests/WebTestCase.php`.

**Before running tests for the first time or after a schema change:**
```bash
make database-init-test
```

**Running tests:**
```bash
make tests                          # All tests
make tests path=tests/Controller/   # Specific directory
make tests path=tests/Foo/BarTest.php  # Single file
make tests args="--filter=testMyMethod"  # PHPUnit filter
```

Test environment uses `APP_ENV=test` and a separate `caf_test` database. Fixtures are loaded automatically by `database-init-test`.

### Playwright (E2E)

E2E tests are in `e2e/` and run against the local Docker environment (port 8000).

```bash
make test-e2e            # Headless
make test-e2e-headed     # With browser visible
make test-e2e-report     # View HTML report
```

---

## Git Workflow

### Branch strategy

```
main              ← primary development branch
  ├── feat/*      ← new features
  ├── fix/*       ← bug fixes
  └── docs/*      ← documentation only
        │
        ▼
production        ← production deployment branch
  └── hotfix-prod-*  ← urgent production fixes
```

### Environments

| Branch | Staging (Clever Cloud) | Prod Lyon (GitHub Actions) | Prod Chambéry/Clermont |
|--------|------------------------|----------------------------|------------------------|
| `main` | Auto-deploy | — | — |
| `production` | — | Auto-deploy | Manual via workflow_dispatch |
| `hotfix-prod-*` | — | Manual | Manual |

### Standard workflow

1. Branch from `main`: `git checkout -b feat/my-feature`
2. Develop and commit
3. Open a PR targeting `main` (description **must be in French**)
4. All CI checks must pass (PHPUnit, PHPStan, PHP-CS-Fixer, GitGuardian)
5. Only the core dev team can merge PRs
6. After merge → auto-deploys to staging
7. Deploy to production: `git checkout production && git merge --ff-only main && git push`

### PR requirements

- Description in **French** using domain terminology
- Screenshots for visual changes (before/after)
- All GitHub Actions must be green
- PHPStan and PHP-CS must pass
- No secrets or credentials in code (GitGuardian validates every PR)

---

## CI/CD

Workflows run on every pull request:

| Workflow | What it checks |
|----------|---------------|
| `code-quality.yaml` | PHP-CS-Fixer (changed files only) + PHPStan level 1 (changed files only) |
| `tests.yaml` | PHPUnit full suite |
| `playwright.yml` | Playwright E2E tests |

PHPStan and PHP-CS only analyse files **changed in the PR** (detected via `get-changed-php-files.sh`). PHPStan excludes `legacy/pages/`, `legacy/includes/`, and other legacy subdirectories.

---

## Business Domain Terminology

Use these French terms in code, comments, and PR descriptions:

| French term | English equivalent | Context |
|-------------|-------------------|---------|
| **Sortie** | Outing / event | Organised alpine trip or activity |
| **Adhérent** | Member | Club member, synced nightly from FFCAM |
| **Encadrant** | Leader / instructor | Person responsible for leading a sortie |
| **Commission** | Activity group | Sub-group within the club (e.g., alpinisme, randonnée, escalade) |
| **Note de frais** | Expense report | Leader's reimbursement claim for a sortie |
| **FFCAM** | French federation | Fédération Française des Clubs Alpins et de Montagne — provides member data |
| **Brevet** | Certification | Leader qualification/certification |
| **Compétence** | Skill/competency | Tracked instructor skill from FFCAM training records |
| **CAF** | Club Alpin Français | Generic name for the club |
| **Nomad** | External participant | Non-member who joins a sortie |
| **AlertType** | Notification type | Category of push notifications sent to members |

---

## Multi-Club Configuration

The same codebase serves multiple clubs. Club-specific behaviour is controlled via environment variables:

| Variable | Purpose |
|----------|---------|
| `CAF_ID` | Internal club identifier |
| `SITENAME` | Club display name |
| `CLUB_CAFNUM_PREFIX` | FFCAM member number prefix for this club |
| `DISPLAY_BANNER` | Feature flag: show/hide a site-wide banner |
| `DISPLAY_NOTES_DE_FRAIS` | Feature flag: enable expense reports module |
| `AFFICHER_COMPETENCES` | Feature flag: show FFCAM skill/competency data |

Club-specific `.env` files are managed in Clever Cloud's environment console. Locally, copy `.env` and override in `.env.local`.

---

## Legacy Code

The `legacy/` directory contains pre-2019 PHP code that predates the Symfony rewrite.

**Rules:**
- **Do not add new features** to `legacy/` — all new development goes in `src/`
- The Symfony `LegacyController` proxies legacy page requests during the migration
- Legacy DB schema is in `legacy/data/schema_caf.sql`; Doctrine entities wrap these tables
- PHPStan **excludes** `legacy/pages/`, `legacy/includes/`, and most other legacy subdirectories
- PHP-CS-Fixer does **not** run on `legacy/`

---

## Key Services

| Service | File | Purpose |
|---------|------|---------|
| `UserRights` | `src/UserRights.php` | Central permission cache and checker |
| `FfcamSynchronizer` | `src/Service/FfcamSynchronizer.php` | Syncs member data from FFCAM CSV files |
| `HelloAssoService` | `src/Service/HelloAssoService.php` | Processes HelloAsso membership payments |
| `ParticipantService` | `src/Service/ParticipantService.php` | Manages sortie registrations |
| `ExpenseReportCalculator` | `src/Service/ExpenseReportCalculator.php` | Calculates expense report totals |
| `PushNotificationService` | `src/Service/PushNotificationService.php` | Firebase-based push notifications |
| `UserRightService` | `src/Service/UserRightService.php` | Manages user right assignments |
| `CmsContentService` | `src/Service/CmsContentService.php` | CMS page and block management |
| `EmailMarketingSyncService` | `src/Service/EmailMarketingSyncService.php` | MailerLite newsletter synchronisation |

---

## Useful References

- Full installation guide: [`docs/installation.md`](docs/installation.md)
- Contribution guide: [`docs/contribution.md`](docs/contribution.md)
- CI/CD details: [`docs/ci-cd.md`](docs/ci-cd.md)
- API documentation: [`docs/api.md`](docs/api.md)
- Domain glossary: [`docs/glossaire.md`](docs/glossaire.md)
- User roles: [`docs/user-roles.md`](docs/user-roles.md)
- Cron jobs: [`docs/cronjobs.md`](docs/cronjobs.md)
- FFCAM sync: [`docs/synchronization.md`](docs/synchronization.md)
- HelloAsso integration: [`docs/hello-asso.md`](docs/hello-asso.md)
- Environments: [`docs/environments.md`](docs/environments.md)
- Technical debt: [`docs/technical-debt.md`](docs/technical-debt.md)
- Troubleshooting: [`docs/troubleshooting.md`](docs/troubleshooting.md)
