# Variables
DOCKER = docker
ifneq ($(shell docker compose version 2>/dev/null),)
  DOCKER_COMPOSE=docker compose
else
  DOCKER_COMPOSE=docker-compose
endif
EXEC = $(DOCKER) exec -w /var/www www_caflyon
PHP = $(EXEC) php
COMPOSER = $(EXEC) composer
NPM = $(EXEC) npm
SYMFONY_CONSOLE = $(PHP) bin/console
MYSQL = $(DOCKER_COMPOSE) --project-directory . --project-name caflyon -f docker-compose.yml exec -T cafdb mysql

# Colors
GREEN = echo "\x1b[32m\#\# $1\x1b[0m"
RED = echo "\x1b[31m\#\# $1\x1b[0m"

## —— 🔥 App ——
init: ## Init the project
	$(MAKE) docker-start
	$(MAKE) composer-install
	$(MAKE) npm-install
	$(MAKE) npm-build
	@$(call GREEN,"Le site du Club est lancé : http://127.0.0.1:8000/ 🚀")

cache-clear: ## Clear cache
	$(SYMFONY_CONSOLE) cache:clear

## —— ✅ Test ——
.PHONY: tests
tests: ## Run all tests
	$(MAKE) database-init-test
	$(PHP) bin/phpunit tests

database-init-test: ## Init database for test

	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists --env=test
	$(SYMFONY_CONSOLE) doctrine:database:create --env=test
	$(MYSQL) -Dcaf_test -uroot -ptest < ./legacy/config/bdd_caf.sql
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction --env=test
	$(SYMFONY_CONSOLE) doctrine:fixtures:load --no-interaction --env=test


## —— 🐳 Docker ——
docker-start: 
	$(DOCKER_COMPOSE) up -d

docker-stop: 
	$(DOCKER_COMPOSE) stop
	@$(call RED,"The containers are now stopped.")

## —— 🎻 Composer ——
composer-install: ## Install dependencies
	$(COMPOSER) install

composer-update: ## Update dependencies
	$(COMPOSER) update

## —— 🐈 NPM —————————————————————————————————————————————————————————————————
npm-install: ## Install all npm dependencies
	$(NPM) install

npm-build: ## Build the frontend files
	$(NPM) run build

npm-watch: ## Watch the frontend files
	$(NPM) run watch

## —— 📊 Database ——
database-init: ## Init database
	$(MAKE) database-drop
	$(MAKE) database-create
	$(MAKE) database-import
	$(MAKE) database-migrate

database-drop: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists

database-create: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists

database-import: ## Make import
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/bdd_caf.sql
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/bdd_caf.1.x.sql
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/bdd_caf.1.1.sql
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/bdd_caf.1.1.1.sql
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/bdd_caf.partenaires.sql

database-migration: ## Make migration
	$(SYMFONY_CONSOLE) make:migration

database-migrate: ## Migrate migrations
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction

database-fixtures-load: ## Load fixtures
	$(SYMFONY_CONSOLE) --env=$(env) doctrine:fixtures:load $(email) resources/fixtures/$(env)/

## —— 🛠️  Others ——
help: ## List of commands
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'


