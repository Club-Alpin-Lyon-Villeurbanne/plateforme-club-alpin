.DEFAULT_GOAL := help

# correctly support redirection
SHELL=/bin/bash

##
## Globals
##

env ?= dev
project ?= cafsite
services ?=

# Project name must be compatible with docker-compose
override project := $(shell echo $(project) | tr -d -c '[a-z0-9]' | cut -c 1-55)

##
## Config
##

COMPOSE=docker-compose --project-directory . --project-name $(project) $(COMPOSE_FILES)
COMPOSE_FILES = -f docker-compose.yml
ON_PHP=$(COMPOSE) run --rm --no-deps cafsite

##
## Phive
##

bin/tools/phpstan bin/tools/php-cs-fixer phive:
	@$(ON_PHP) php -d memory_limit=1G /usr/local/bin/phive install --copy --trust-gpg-keys 8E730BA25823D8B5,CF1A108D0E7AE720,E82B2FB314E9906E

phive-update:
	@$(ON_PHP) php -d memory_limit=1G /usr/local/bin/phive update

php-cs: bin/tools/php-cs-fixer ## Just analyze PHP code with php-cs-fixer
	$(eval args ?= )
	@$(ON_PHP) php -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run $(args)
.PHONY: php-cs

php-cs-fix: bin/tools/php-cs-fixer ## Analyze and fix PHP code with php-cs-fixer
	$(eval args ?= )
	@$(ON_PHP) php -dmemory_limit=-1 ./bin/tools/php-cs-fixer fix --config=.php-cs-fixer.dist.php $(args)
.PHONY: php-cs-fix

phpstan: ## Analyze PHP code with phpstan
phpstan: bin/tools/phpstan $(AUTOLOAD_FILES) vendor/bin/.phpunit/phpunit-9-0/vendor/autoload.php ../languages/php/probe/modules/blackfire.so
	@$(ON_PHP) php -dmemory_limit=-1 ./bin/tools/phpstan analyse $(PHP_SRC) -c phpstan.neon -l 1
.PHONY: phpstan

package: ## Creates software package
#	@cp .env .env.backup
#	@sed -i 's/APP_ENV=.*/APP_ENV=prod/g' .env
	@$(ON_PHP) bash -c "ls -al"
	@$(ON_PHP) bash -c "APP_ENV=prod composer install --no-dev --optimize-autoloader --no-interaction --apcu-autoloader --prefer-dist"
#	@$(ON_PHP) bash -c "APP_ENV=prod composer dump-env prod"
	@rm -rf package.zip
	@zip -q -r package.zip \
		backup \
		bin/console \
		public \
		vendor \
		.env.local.php \
		composer.lock
#	@mv .env.backup .env
.PHONY: package

##
#### Docker
##

build: ## Build images
	@$(COMPOSE) build $(services)
.PHONY: build

down: ## Stop and remove containers, networks, images, and volumes
	@$(COMPOSE) down
.PHONY: down

exec: ## Execute a command in a container (container="caf", cmd="bash", user="www-data")
	$(eval container ?= cafsite)
	$(eval cmd ?= bash)
	$(eval user ?= www-data)
	@$(COMPOSE) exec --user=$(user) $(container) $(cmd)
.PHONY: exec

logs: ## View output from containers
	@$(COMPOSE) logs -f $(services)
.PHONY: logs

ps: ## List containers
	@$(COMPOSE) ps $(services)
.PHONY: ps

recreate: stop rm up ## Recreate containers
.PHONY: recreate

restart: ## Restart containers
	@$(COMPOSE) restart $(services)
.PHONY: restart

rm: ## Remove containers
	@$(COMPOSE) rm -f $(services)
.PHONY: rm

run: ## Run a command in a new container (container="php", cmd="bash", user="root")
	$(eval container ?= php)
	$(eval cmd ?= bash)
	$(eval user ?= root)
	@$(COMPOSE) run --rm --user=$(user) $(container) $(cmd)
.PHONY: run

stop: ## Stop containers
	@$(COMPOSE) stop $(services)
.PHONY: stop

up: ## Create and start containers
	@$(COMPOSE) up -d $(services)
.PHONY: up

##
## Help
##

help:
	@grep -hE '(^[a-zA-Z_-]+:.*?##.*$$)|(^###)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m\n/'
.PHONY: help
