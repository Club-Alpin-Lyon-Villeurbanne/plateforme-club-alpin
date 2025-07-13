# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a comprehensive management platform for French Alpine Clubs (Club Alpin Français), built with Symfony 6.4 and PHP 8.3. The application manages events, members, content, and financial operations for multiple Alpine clubs across France.

## Development Environment

### Quick Start
```bash
# Initialize the complete development environment
make init

# Access the application
# - Website: http://127.0.0.1:8000/
# - PHPMyAdmin: http://127.0.0.1:8080/ (root/test)
# - Mailcatcher: http://127.0.0.1:8025/

# Login credentials
# - Admin: test@clubalpinlyon.fr / test
# - Content Manager: admin_contenu / contenu
```

### Essential Commands

**Docker Management:**
```bash
make docker-start          # Start all services
make docker-stop           # Stop all services
make docker-build          # Rebuild images
```

**Application Development:**
```bash
make cache-clear           # Clear Symfony cache
make composer-install      # Install PHP dependencies
make npm-install           # Install Node.js dependencies
make npm-build             # Build frontend assets
make npm-watch             # Watch and rebuild assets
```

**Testing & Quality:**
```bash
make tests                 # Run all PHPUnit tests
make tests path=tests/Unit/UserTest.php  # Run specific test
make tests clear=1         # Run tests with fresh database

make php-cs               # Check code style (dry-run)
make php-cs-fix           # Fix code style issues
make phpstan              # Run static analysis
```

**Database Operations:**
```bash
make database-init        # Full database setup (dev)
make database-init-test   # Setup test database
make database-migrate     # Run migrations
make database-migration   # Generate new migration
make database-fixtures-load  # Load fixture data
```

**Container Access:**
```bash
make exec                 # Access web container (default)
make exec container=cafdb cmd=bash  # Access database container
make logs                 # View all container logs
make logs services=cafdb  # View specific service logs
```

## Architecture Overview

### Core Business Domains

1. **Event Management** (`Evt` entity)
   - Mountain outings and activities
   - Dual validation system (publication + legal approval)
   - Participant registration and management

2. **User Management** (`User` entity)
   - Member profiles with complex permission system
   - Commission-based access control
   - Parent/child account relationships

3. **Content Management** (`Article` entity)
   - News articles and announcements
   - Multi-stage approval workflow
   - Commission-specific content

4. **Expense Management** (Modern Vue.js module)
   - Located in `assets/vue/` directory
   - TypeScript + TailwindCSS implementation
   - Complex validation and approval rules

5. **Material Management**
   - Integration with external Loxya API
   - Equipment tracking and reservations

### Technology Stack

**Backend:**
- Symfony 6.4 with PHP 8.3
- MySQL 8.0 database
- Doctrine ORM with extensive migrations
- Symfony Messenger for async processing

**Frontend:**
- Twig templates for traditional UI
- Vue.js 3 + TypeScript for expense reports
- TailwindCSS for modern components
- Vite for asset bundling

**Infrastructure:**
- Docker development environment (containers: www_pca, db_pca, phpmyadmin_pca, mail_pca)
- Clever Cloud hosting platform
- GitGuardian security monitoring

### Key Directories

- `src/` - Modern Symfony application code
- `legacy/` - Legacy PHP code and database schemas
- `assets/vue/` - Vue.js expense management module
- `templates/` - Twig templates
- `templates/content_html/` - Commission-specific page templates
- `config/` - Symfony configuration
- `tests/` - PHPUnit test suite
- `docs/` - Project documentation

### Database Structure

The application uses a hybrid approach combining:
- **Legacy tables** (prefixed with `caf_`) for core functionality
- **Modern Doctrine entities** for new features
- **Migration system** bridging old and new schemas

Key entities:
- `caf_user` - User management with flexible attributes
- `caf_evt` - Events with validation workflows
- `caf_commission` - Activity sections/departments
- `caf_article` - Content with approval system
- `expense_report` - Modern expense management

### External Integrations

- **FFCAM API** - French Alpine Federation member synchronization
- **Google Groups API** - Mailing list management
- **Loxya API** - Material/equipment management platform
- **Mailgun** - Email delivery service
- **Sentry** - Error monitoring
- **Google Drive** - Commission file storage

## Development Workflows

### Working with Tests
```bash
# Run specific test class
make tests path=tests/Unit/Service/UserServiceTest.php

# Run tests with coverage (if configured)
make tests args="--coverage-html coverage/"

# Run functional tests only
make tests path=tests/Functional/

# Reset test database before running
make tests clear=1
```

### Frontend Development
```bash
# Start asset watching for development
make npm-watch

# Build production assets
make npm-build

# Work with Vue.js expense module
cd assets/vue/
# Vue components are in assets/vue/components/
# TypeScript configuration in tsconfig.json
```

### Database Migrations
```bash
# Create new migration after entity changes
make database-migration

# Apply pending migrations
make database-migrate

# Generate migration based on entity differences
make database-diff
```

### Code Quality
Always run before committing:
```bash
make php-cs-fix    # Fix code style
make phpstan       # Check static analysis
make tests         # Ensure tests pass
```

## Important Notes

### Container Names
The Docker containers use "pca" prefix (plateforme-club-alpin):
- `www_pca` - Web server
- `db_pca` - MySQL database
- `phpmyadmin_pca` - Database admin interface
- `mail_pca` - Mail catcher

### Legacy Integration
- Legacy code in `/legacy/` directory is preserved for compatibility
- Some URLs and routes bridge legacy and modern code
- Database schemas are imported from `legacy/data/` SQL files

### Permission System
- Complex role-based access control via `UserRights` class
- Commission-based permissions for different activity sections
- Multiple user types with varying capabilities

### Multi-Club Support
This platform serves multiple Alpine clubs:
- Configuration overrides in Clever Cloud console
- Club-specific branding and settings
- Shared codebase with customizable features

### Email System
- Transactional emails via Mailgun in production
- Local development uses Mailcatcher (port 8025)
- Template system for various notification types

### Expense Report Module
Modern Vue.js application with:
- Vee-validate for form validation
- Zod schemas for type safety
- Axios for API communication
- TailwindCSS for styling