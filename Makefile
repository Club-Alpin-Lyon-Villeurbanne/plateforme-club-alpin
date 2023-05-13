.DEFAULT_GOAL := help

# correctly support redirection
SHELL=/bin/bash

ifneq ($(MAKE_NO_DOT_ENV),true)
	include .env
	-include .env.local
endif

##
## Globals
##

env ?= dev
project ?= cafsite
services ?=

ifeq ($(env),dev)
	dbname = caf
else
	dbname = caf_test
endif


# Project name must be compatible with docker-compose
override project := $(shell echo $(project) | tr -d -c '[a-z0-9]' | cut -c 1-55)

##
## Config
##

COMPOSE=docker-compose --project-directory . --project-name $(project) $(COMPOSE_FILES)
COMPOSE_FILES = -f docker-compose.yml
ON_PHP=$(COMPOSE) run --rm --no-deps cafsite
ON_ASSETS=$(COMPOSE) run --rm --no-deps assets

migrate: ## Migrate (env="dev")
	@$(ON_PHP) php bin/console doctrine:migration:sync-metadata-storage --env $(env)
	@$(ON_PHP) php bin/console doctrine:migration:migrate --env $(env) --no-interaction
	@$(ON_PHP) php bin/console messenger:setup-transports --env $(env) --no-interaction
.PHONY: migrate

migration-diff: ## Migrate (env="dev")
	@$(ON_PHP) php bin/console doctrine:migration:diff --env $(env)
.PHONY: migration-diff

##
## Yarn
##

yarn-install: ## Installs node dependencies using yarn
	$(ON_ASSETS) yarn install --pure-lockfile --frozen-lockfile --emoji
.PHONY: yarn-install

yarn-build: ## Builds assets
	$(ON_ASSETS) yarn build
.PHONY: yarn-build

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
phpstan: bin/tools/phpstan composer-install vendor/bin/.phpunit/phpunit-9.5-0/vendor/autoload.php
	@$(ON_PHP) php -dmemory_limit=-1 ./bin/tools/phpstan analyse legacy public src tests resources -c phpstan.neon -l 1
.PHONY: phpstan

phpunit: vendor/autoload.php vendor/bin/.phpunit/phpunit-9.5-0/vendor/autoload.php ## Run phpunit
	@$(ON_PHP) vendor/bin/simple-phpunit -v $(args)
.PHONY: phpunit

vendor/bin/.phpunit/phpunit-9.5-0/vendor/autoload.php: composer-install
	@echo "INSTALL phpunit $*"
	@$(ON_PHP) vendor/bin/simple-phpunit --version 2>&1>/dev/null
	@touch $@

composer-install:
	@echo "INSTALL $(@D)"
	@$(ON_PHP) bash -c "composer install --no-interaction --prefer-dist"
	@touch $@
.PHONY: composer-install

composer-update:
	@echo "INSTALL $(@D)"
	@$(ON_PHP) bash -c "composer update --no-interaction --prefer-dist"
	@touch $@
.PHONY: composer-update

setup-db: composer-install ## Migrate (env="dev")
	@echo "Checking if the database is up..."
	@$(ON_PHP) timeout --foreground 120s bash -c 'while ! timeout --foreground 3s echo > /dev/tcp/caf-db/3306 2 > /dev/null ; do sleep 1; done' \
	    || (echo "Unable to connect to the database. Exiting..." && exit 1)
	@echo "Database is up!"
	@$(ON_PHP) bin/console doctrine:database:create --env $(env) --if-not-exists
	@$(COMPOSE) exec -T caf-db mysql -D$(dbname) -uroot -ptest < ./legacy/config/bdd_caf.sql
	@$(COMPOSE) exec -T caf-db mysql -D$(dbname) -uroot -ptest < ./legacy/config/bdd_caf.1.1.sql
	@$(COMPOSE) exec -T caf-db mysql -D$(dbname) -uroot -ptest < ./legacy/config/bdd_caf.1.x.sql
	@$(COMPOSE) exec -T caf-db mysql -D$(dbname) -uroot -ptest < ./legacy/config/bdd_caf.1.1.1.sql
	@$(COMPOSE) exec -T caf-db mysql -D$(dbname) -uroot -ptest < ./legacy/config/bdd_caf.partenaires.sql
.PHONY: setup-db

fixtures: migrate ## Load fixtures (env="dev" email="")
ifeq ("$(email)","")
	$(eval email ?= none)
endif
	@$(ON_PHP) bin/console --env=$(env) caf:fixtures:load $(email) resources/fixtures/$(env)/
.PHONY: fixtures

package: yarn-install yarn-build ## Creates software package
	@cp .env .env.backup
	@sed -i 's/APP_ENV=.*/APP_ENV=$(env)/g' .env
	@$(ON_PHP) bash -c "APP_ENV=$(env) composer install --optimize-autoloader --no-interaction --apcu-autoloader --prefer-dist"
	@$(ON_PHP) bash -c "APP_ENV=$(env) composer dump-env $(env)"
	@rm -rf package.zip
	@zip -q -r package.zip \
		backup \
		bin/console \
		config \
		legacy \
		migrations \
		public \
		src \
		templates \
		var/cache/prod \
		vendor \
		resources \
		.env.local.php \
		composer.json \
		composer.lock \
	@mv .env.backup .env
.PHONY: package

##
#### Docker
##

build: volumes ## Build images
	@$(COMPOSE) build $(services)
.PHONY: build

volumes:
	@mkdir -p ~/.phive ~/.composer
.PHONY: volumes

down: ## Stop and remove containers, networks, images, and volumes
	@$(COMPOSE) down
.PHONY: down

exec: ## Execute a command in a container (container="cafsite", cmd="bash", user="www-data")
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
