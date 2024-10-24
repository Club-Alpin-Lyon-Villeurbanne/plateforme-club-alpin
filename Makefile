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

## —— ✅ Linting ——
php-cs: ## Just analyze PHP code with php-cs-fixer
	$(eval args ?= )
	$(PHP) -dmemory_limit=-1 vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run $(args)

php-cs-fix: ## Analyze and fix PHP code with php-cs-fixer
	$(eval args ?= )
	$(PHP) -dmemory_limit=-1 vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php $(args)

phpstan:
	$(PHP) -dmemory_limit=-1 vendor/bin/phpstan analyse legacy public src tests resources -c phpstan.neon -l 1

## —— ✅ Test ——
.PHONY: tests
tests: ## Run all tests
ifdef clear
	$(MAKE) database-init-test
endif
	$(PHP) bin/phpunit ${path}

database-init-test: ## Init database for test

	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists --env=test
	$(SYMFONY_CONSOLE) doctrine:database:create --env=test
	$(MYSQL) -Dcaf_test -uroot -ptest < ./legacy/config/schema_caf.sql
	$(MYSQL) -Dcaf_test -uroot -ptest < ./legacy/config/data_caf.sql
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction --env=test
	$(MAKE) args="--env=test --no-interaction" database-fixtures-load


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
	$(MAKE) args="--env=dev --no-interaction" database-fixtures-load

database-drop: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:drop --force --if-exists

database-create: ## Create database
	$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/schema_caf.sql


database-import: ## Make import
	$(MYSQL) -Dcaf -uroot -ptest < ./legacy/config/data_caf.sql

database-migration: ## Make migration
	$(SYMFONY_CONSOLE) make:migration

database-migrate: ## Migrate migrations
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction

database-diff: ## Create doctrine migrations
	$(SYMFONY_CONSOLE) doctrine:migrations:diff --no-interaction

database-fixtures-load: ## Load fixtures
ifeq ($(args),)
	$(eval args="--env=dev")
endif
	$(SYMFONY_CONSOLE) $(args) caf:fixtures:load

exec: ## Execute a command in a container (container="cafsite", cmd="bash", user="www-data")
	$(eval container ?= cafsite)
	$(eval cmd ?= bash)
	$(eval user ?= www-data)
	@$(DOCKER_COMPOSE) exec --user=$(user) $(container) $(cmd)
.PHONY: exec

## —— 🛠️  Others ——
help: ## List of commands
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
