# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Club Management Platform** for French Alpine Clubs (Club Alpin Français). It manages member registrations, mountain activities, events, expense reports, and club administration. The platform serves multiple Alpine Club chapters with commission-based organization.

## Essential Commands

### Development Setup
```bash
# Initialize the project (Docker, dependencies, database)
make init

# Start Docker containers
make docker-start

# Access the application
# http://127.0.0.1:8000/
```

### Daily Development Commands
```bash
# Install/update dependencies
make composer-install
make npm-install

# Build frontend assets
npm run dev    # Start Vite dev server
npm run build  # Production build
npm run watch  # Watch mode

# Clear Symfony cache
make cache-clear

# Access container shell
make exec  # defaults to www-data user in cafsite container
```

### Code Quality & Testing
```bash
# PHP code style (MUST run before committing)
make php-cs-fix     # Fix code style issues
make php-cs         # Check without fixing

# Static analysis (MUST pass)
make phpstan        # Level 1 analysis

# Run tests
make tests          # Run all PHPUnit tests
make tests path=tests/YourTest.php  # Run specific test
make tests clear=1  # Reset test database before running

# JavaScript/TypeScript
# No linter configured - use Prettier for formatting
```

### Database Operations
```bash
# Full database reset with fixtures
make database-init

# Create a new migration
make database-diff

# Run migrations
make database-migrate

# Load fixtures
make database-fixtures-load
```

## Architecture & Code Structure

### Technology Stack
- **Backend**: PHP 8.3, Symfony 6.4 LTS
- **API**: API Platform 3.2 with JWT authentication
- **Database**: MySQL 8.0 with Doctrine ORM
- **Frontend**: Twig templates + Vue.js 3 (expense reports) + TailwindCSS
- **Build**: Vite 5.4 with Symfony integration
- **Infrastructure**: Docker for development, Clever Cloud for production

### Key Architectural Patterns

1. **Repository Pattern**: All entities have dedicated repositories in `src/Repository/`
2. **Service Layer**: Business logic in `src/Service/` (e.g., `ExpenseReportService`, `UserService`)
3. **Event-Driven**: Event listeners in `src/EventListener/` and subscribers in `src/EventSubscriber/`
4. **API Resources**: API Platform resources in `src/ApiResource/`
5. **Legacy Bridge**: Legacy code integration via `src/Bridge/` and `LegacyController`

### Domain Structure

```
src/
├── Controller/        # MVC controllers
├── Entity/           # Doctrine entities
├── Repository/       # Data access layer
├── Service/          # Business logic
├── Security/         # Voters and authentication
├── ApiResource/      # API Platform resources
├── Bridge/           # Legacy system adapters
├── Command/          # Console commands
├── EventListener/    # Event handlers
└── Form/             # Symfony forms

legacy/               # Legacy PHP code (being migrated)
assets/              # Frontend assets
├── controllers/     # Stimulus controllers
├── styles/         # SCSS/CSS files
└── vue/            # Vue.js components (expense reports)
```

### Key Entities & Relationships

- **User** → UserRight (permissions) → Commission (organizational units)
- **Commission** → Evt (events/activities) → EventParticipation
- **Evt** → ExpenseReport → ExpenseAttachment
- **Article** → Commission (content ownership)

### Security & Permissions

- JWT-based API authentication
- Voter system for fine-grained authorization
- Commission-scoped permissions
- User rights matrix (`UserRight`, `UserNiveau`, `UserAttr`)

## Development Guidelines

### Before Making Changes

1. Check existing code patterns in similar files
2. Use repository pattern for data access
3. Put business logic in services, not controllers
4. Follow Symfony best practices
5. Maintain backward compatibility with legacy URLs

### Frontend Development

- Server-side rendering: Use Twig templates
- Interactive features: Vue.js components in `assets/vue/`
- Styling: TailwindCSS for new components, existing SCSS for legacy
- Build assets after changes: `npm run build`

### API Development

- Use API Platform attributes for resource configuration
- Implement custom state providers/processors for complex logic
- Add proper security voters for authorization
- Document endpoints with OpenAPI attributes

### Testing Requirements

- Write PHPUnit tests for new features
- Test files go in `tests/` matching `src/` structure
- Use fixtures for test data
- Run `make tests` before committing

### Common Tasks

**Adding a new entity:**
1. Create entity class in `src/Entity/`
2. Create repository in `src/Repository/`
3. Generate migration: `make database-diff`
4. Run migration: `make database-migrate`

**Adding an API endpoint:**
1. Create/update ApiResource in `src/ApiResource/`
2. Add security voter if needed
3. Implement state provider/processor if complex logic required
4. Test with JWT authentication

**Working with legacy code:**
- Legacy routes are handled by `LegacyController`
- Use `LegacyBridge` service for data access
- Gradually migrate functionality to Symfony

## Important Notes

- **Multi-tenant**: Data is scoped by commission - always filter queries appropriately
- **Async operations**: Use Symfony Messenger for emails and heavy tasks
- **File uploads**: Use VichUploaderBundle for file handling
- **Caching**: Symfony cache is cleared on deploy, use it liberally
- **Monitoring**: Errors are tracked in Sentry
- **Emails**: Sent via Brevo in production, Mailpit in development

## Environment-Specific Information

- **Development**: Docker environment with MySQL, PHPMyAdmin, Mailpit
- **Test**: Separate test database, fixtures loaded automatically
- **Production**: Clever Cloud with GitHub Actions CI/CD

## Useful Resources

- [Project Documentation](/docs/)
- [Contribution Guide](/docs/contribution.md)
- [Infrastructure Details](/docs/infrastructure.md)
- [User Roles Documentation](/docs/user-roles.md)