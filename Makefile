# Variables
DOCKER = docker
ifneq ($(shell docker compose version 2>/dev/null),)
  DOCKER_COMPOSE=docker compose
else
  DOCKER_COMPOSE=docker-compose
endif
EXEC = $(DOCKER) exec -w /var/www www_pca
PHP = $(EXEC) php
COMPOSER = $(EXEC) composer
NPM = $(EXEC) npm
SYMFONY_CONSOLE = $(PHP) bin/console
MYSQL = $(DOCKER_COMPOSE) -f docker-compose.yml exec -T cafdb mysql

# Colors
GREEN = echo "\x1b[32m\#\# $1\x1b[0m"
RED = echo "\x1b[31m\#\# $1\x1b[0m"

## —— 🔥 App ——
init: ## Init the project
	$(eval profile ?= dev)

	$(MAKE) docker-start profile=$(profile)
	$(MAKE) composer-install
	$(MAKE) npm-install
	$(MAKE) npm-build
	@$(call GREEN,"Le site du Club est lancé : http://127.0.0.1:8000/ 🚀")
.PHONY: init

cache-clear: ## Clear cache
	$(SYMFONY_CONSOLE) cache:clear
.PHONY: cache-clear

## —— ✅ Linting ——
php-cs: bin/tools/php-cs-fixer ## Just analyze PHP code with php-cs-fixer
	$(eval args ?= )
	@$(PHP) -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run $(args)
.PHONY: php-cs

php-cs-fix: bin/tools/php-cs-fixer ## Analyze and fix PHP code with php-cs-fixer
	$(eval args ?= )
	@$(PHP) -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php $(args)
.PHONY: php-cs-fix

php-cs-changed: bin/tools/php-cs-fixer ## Check only changed PHP files (for PRs)
	@echo "Checking changed PHP files..."
	@git diff --name-only --cached --diff-filter=ACMRTUXB | grep -E '\.php$$' | xargs -r $(PHP) -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff || echo "No PHP files to check"
.PHONY: php-cs-changed

php-cs-fix-changed: bin/tools/php-cs-fixer ## Fix only changed PHP files
	@echo "Fixing changed PHP files..."
	@git diff --name-only --cached --diff-filter=ACMRTUXB | grep -E '\.php$$' | xargs -r $(PHP) -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php || echo "No PHP files to fix"
.PHONY: php-cs-fix-changed

phpstan: bin/tools/phpstan ## Analyze PHP code with phpstan
	$(PHP) -dmemory_limit=-1 ./bin/tools/phpstan analyse legacy public src tests -c phpstan.neon -l 1
.PHONY: phpstan

phpstan-files: bin/tools/phpstan ## Analyze specific PHP files with phpstan
	$(PHP) -dmemory_limit=-1 ./bin/tools/phpstan analyse $(FILES) -c phpstan.neon
.PHONY: phpstan-files


## —— ✅ Test ——
.PHONY: tests
tests: ## Run all tests
	$(eval args ?= )
ifdef clear
	$(MAKE) database-init-test
endif
	$(PHP) bin/phpunit ${path} $(args)
.PHONY: tests

phpunit-setup: ## Setup phpunit
	@$(PHP) bin/phpunit --version
.PHONY: phpunit-setup

test-e2e: ## Run Playwright E2E tests
	npx playwright test
.PHONY: test-e2e

test-e2e-headed: ## Run Playwright E2E tests (headed)
	npx playwright test --headed
.PHONY: test-e2e-headed

test-e2e-report: ## Open Playwright HTML report
	npx playwright show-report
.PHONY: test-e2e-report

database-init-test: ## Init database for test

	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists --env=test
	$(SYMFONY_CONSOLE) doctrine:database:create --env=test
	$(SYMFONY_CONSOLE) messenger:setup-transports --env=test
	$(MYSQL) -Dcaf_test -uroot -ptest < ./legacy/data/schema_caf.sql
	$(MYSQL) -Dcaf_test -uroot -ptest < ./legacy/data/data_caf.sql
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction --env=test
	$(MAKE) args="--env=test --no-interaction" database-fixtures-load
.PHONY: database-init-test


## —— 🐳 Docker ——
docker-start: 
	$(eval profile ?= dev)
	@mkdir -p ~/.phive ~/.composer ~/.ssh
	$(DOCKER_COMPOSE) --profile $(profile) up -d
.PHONY: docker-start


docker-build: ## Build images
	@$(DOCKER_COMPOSE) pull --parallel
	@$(DOCKER_COMPOSE) build --pull --parallel
.PHONY: docker-build

docker-stop:
	$(eval profile ?= dev)

	$(DOCKER_COMPOSE) --profile $(profile) stop
	@$(call RED,"The containers are now stopped.")
.PHONY: docker-stop

## —— 🎻 Composer ——
composer-install: ## Install dependencies
	$(COMPOSER) install
.PHONY: composer-install

composer-update: ## Update dependencies
	$(COMPOSER) update
.PHONY: composer-update

## —— 🐈 NPM —————————————————————————————————————————————————————————————————
npm-install: ## Install all npm dependencies
	$(NPM) install
.PHONY: npm-install

npm-build: ## Build the frontend files
	$(NPM) run build
.PHONY: npm-build

npm-watch: ## Watch the frontend files
	$(NPM) run watch
.PHONY: npm-watch

## —— 📊 Database ——
database-init: ## Init database
	$(MAKE) database-drop
	$(MAKE) database-create
	$(MAKE) database-import
	$(MAKE) database-migrate
	$(MAKE) args="--env=dev --no-interaction" database-fixtures-load
.PHONY: database-init

database-drop: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists
.PHONY: database-drop

database-create: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/data/schema_caf.sql
	$(SYMFONY_CONSOLE) messenger:setup-transports
	$(SYMFONY_CONSOLE) doctrine:migrations:sync-metadata-storage
.PHONY: database-create

database-import: ## Make import
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/data/data_caf.sql
.PHONY: database-import

database-migration: ## Make migration
	$(SYMFONY_CONSOLE) make:migration
.PHONY: database-migration

database-migrate: ## Migrate migrations
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction
	$(SYMFONY_CONSOLE) messenger:setup-transports
	$(SYMFONY_CONSOLE) doctrine:migrations:sync-metadata-storage
.PHONY: database-migrate

database-diff: ## Create doctrine migrations
	$(SYMFONY_CONSOLE) doctrine:migrations:diff --no-interaction
.PHONY: database-diff

database-migration-down: ## Make migration
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate prev --no-interaction
.PHONY: database-migration-down

database-fixtures-load: ## Load fixtures
ifeq ($(args),)
	$(eval args="--env=dev")
endif
	$(SYMFONY_CONSOLE) $(args) caf:fixtures:load
.PHONY: database-fixtures-load

exec: ## Execute a command in a container (container="cafsite", cmd="bash", user="www-data")
	$(eval container ?= cafsite)
	$(eval cmd ?= bash)
	$(eval user ?= www-data)
	@$(DOCKER_COMPOSE) exec --user=$(user) $(container) $(cmd)
.PHONY: exec

logs: ## View output from containers (services="")
	@$(DOCKER_COMPOSE) logs -f $(services)
.PHONY: logs

phive: bin/tools/phpstan bin/tools/php-cs-fixer
bin/tools/phpstan bin/tools/php-cs-fixer: phive.xml
	@$(PHP) -d memory_limit=1G /usr/local/bin/phive install --copy --trust-gpg-keys 8E730BA25823D8B5,CF1A108D0E7AE720,E82B2FB314E9906E,CA7C2C7A30C8E8E1274A847651C67305FFC2E5C0

phive-update:
	$(PHP) -d memory_limit=1G /usr/local/bin/phive update
.PHONY: phive-update

## —— 🛠️  Others ——
help: ## List of commands
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

consume-mails: ## consume mails
	$(SYMFONY_CONSOLE) messenger:consume mails --limit=50 --quiet --no-interaction
.PHONY: consume-mails

consume-alertes: ## consume alertes
	$(SYMFONY_CONSOLE) messenger:consume alertes --limit=50 --quiet --no-interaction
.PHONY: consume-alertes

api-swagger: ## Run API Swagger UI
	$(DOCKER) run -p 8001:8080 -e SWAGGER_JSON_URL=http://localhost:8000/api/docs.jsonopenapi docker.swagger.io/swaggerapi/swagger-ui
.PHONY: api-swagger