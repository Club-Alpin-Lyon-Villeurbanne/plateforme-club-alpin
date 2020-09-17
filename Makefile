.DEFAULT_GOAL := help

# correctly support redirection
SHELL=/bin/bash

##
## Globals
##

env ?= dev
project ?= caf-site
services ?=

# Project name must be compatible with docker-compose
override project := $(shell echo $(project) | tr -d -c '[a-z0-9]' | cut -c 1-55)

##
## Config
##

COMPOSE=docker-compose --project-directory . --project-name $(project) $(COMPOSE_FILES)
COMPOSE_FILES = -f docker-compose.yml

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
	$(eval container ?= caf)
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
