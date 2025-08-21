# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony 6.4 + Vue.js web application for managing French Alpine Clubs (Club Alpin Français). It handles member management, event/outing organization, content management, and expense reports.

## Tech Stack

- **Backend**: PHP 8.3, Symfony 6.4, Doctrine ORM
- **Frontend**: Twig templates, Vue.js 3 (for expense reports), TailwindCSS
- **Database**: MySQL 8.0
- **Build Tools**: Vite, NPM, Composer
- **Testing**: PHPUnit, Playwright
- **Code Quality**: PHP-CS-Fixer, PHPStan
- **Infrastructure**: Docker, Clever Cloud

## Common Commands

### Development Setup
```bash
# Initialize project with Docker
make init
make database-init

# Start containers
make docker-start

# Stop containers  
make docker-stop
```

### Build & Development
```bash
# Install dependencies
make composer-install
make npm-install

# Build frontend assets
make npm-build

# Watch frontend changes
make npm-watch

# Development server
make npm-dev
npm run dev
```

### Code Quality & Linting
```bash
# PHP linting (dry-run)
make php-cs

# PHP linting (auto-fix)
make php-cs-fix

# Static analysis
make phpstan

# Format specific files
make php-cs-fix args="src/Controller/SomeController.php"
```

### Testing
```bash
# Run all tests
make tests

# Run specific test
make tests path=tests/Controller/AdminControllerTest.php

# Run tests with fresh database
make tests clear=true

# E2E tests
npx playwright test
```

### Database Operations
```bash
# Create migration
make database-migration

# Apply migrations
make database-migrate

# Load fixtures
make database-fixtures-load

# Reset test database
make database-init-test
```

### Message Queue
```bash
# Consume mail queue
make consume-mails

# Consume alerts queue  
make consume-alertes
```

## Architecture

### Directory Structure

- **src/**: Symfony application code
  - **Controller/**: HTTP request handlers
  - **Entity/**: Doctrine entities (database models)
  - **Repository/**: Database query logic
  - **Service/**: Business logic services
  - **Form/**: Symfony form types
  - **ApiResource/**: API Platform resources
  - **Command/**: Console commands
  - **EventListener/**: Event listeners

- **templates/**: Twig templates
  - **components/**: Reusable UI components
  - **email/**: Email templates
  - **content_html/**: Static content templates

- **assets/**: Frontend assets
  - **styles/**: CSS/SCSS files
  - **js/**: Legacy JavaScript
  - **expense-report-form/**: Vue.js expense report app

- **legacy/**: Legacy PHP code (being progressively migrated)
  - Contains old procedural PHP code
  - Do not add new features here

- **migrations/**: Database migrations
- **config/**: Symfony configuration
- **public/**: Web root with assets

### Key Entities

- **User**: System users with roles and permissions
- **Evt**: Events/outings organized by the club
- **Article**: News articles and content
- **Commission**: Club sections/commissions
- **ExpenseReport**: Expense report management
- **EventParticipation**: User participation in events

### Authentication & Security

- JWT authentication for API (Lexik JWT Bundle)
- Session-based auth for web interface
- Role-based access control (ROLE_USER, ROLE_ADMIN, etc.)
- Recaptcha validation for forms

### API

- REST API built with API Platform
- JWT authentication required
- Endpoints under `/api/`
- OpenAPI documentation available

### Frontend Patterns

- Twig for server-side rendering
- Vue.js for interactive components (expense reports)
- TailwindCSS for styling (new components)
- Legacy jQuery code (being migrated)

## Development Guidelines

### Working with Legacy Code

The codebase contains legacy PHP code in the `legacy/` directory. When modifying features:
1. Check if the feature exists in legacy code
2. Consider migrating to Symfony if making significant changes
3. Never add new features to legacy code

### Database Changes

Always use migrations for database changes:
1. Make entity changes
2. Generate migration: `make database-migration`
3. Review the generated migration
4. Apply: `make database-migrate`

### Frontend Development

For new interactive features:
1. Use Vue.js components in `assets/`
2. Use TailwindCSS for styling
3. Build with Vite: `npm run build`

### Testing Requirements

Before committing:
1. Run PHP linting: `make php-cs`
2. Run static analysis: `make phpstan`
3. Run tests: `make tests`
4. Ensure no TypeScript errors: `npm run build`

### Environment Variables

Key environment variables (set in `.env.local`):
- `DATABASE_URL`: MySQL connection string
- `JWT_SECRET_KEY`: JWT signing key
- `MAILER_DSN`: Email configuration
- `APP_ENV`: Environment (dev/prod/test)

## Important Notes

- The application uses European/Paris timezone
- Email sending uses Symfony Messenger queue
- Image uploads require proper file permissions
- Legacy routes are progressively being migrated to Symfony
- The codebase follows PSR-4 autoloading standard
- Vue.js is used specifically for complex forms (expense reports)
- Always check existing patterns before implementing new features