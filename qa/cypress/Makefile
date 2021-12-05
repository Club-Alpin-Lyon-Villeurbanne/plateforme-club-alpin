.DEFAULT_GOAL := help

##
## Globals
##

project ?= caflv

# Project name must be compatible with docker-compose
override project := $(shell echo $(project) | tr -d -c '[a-z0-9]' | cut -c 1-55)

##
## Config
##

COMPOSE=docker-compose --project-directory . --project-name $(project) -f docker-compose.yml
CYPRESS=$(RUN_NODE) node_modules/.bin/cypress


# Default
COMPOSE_PROFILE = $(COMPOSE_APP)
ifdef CI
	COMPOSE_BUILD_OPT = --progress=plain
endif
RUN_NODE=$(COMPOSE) run --rm node

help:
	@grep -hE '(^[a-zA-Z_-]+:.*?##.*$$)|(^###)' $(MAKEFILE_LIST) | grep -vhE '(^###[<>])' | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m\n/'
.PHONY: help

##
#### Development
##

install: ## Installs dependencies
	# boot clean
	@echo "--- Running yarn install"
	@$(RUN_NODE) yarn install
	@echo "--- Running cypress install"
	@$(CYPRESS) install
.PHONY: install

run: ## Run a command (args:"bash")
	# boot clean
	@$(RUN_NODE) $(args)
.PHONY: run

##
#### Tests
##

cypress-run: ## Run cypress (args:"")
	# boot clean
	@$(CYPRESS) run $(args)
.PHONY: run

##
#### Docker
##

docker-build: ## Builds docker images (args:"")
	# boot clean
	@$(COMPOSE) build --pull --parallel $(COMPOSE_BUILD_OPT) $(args)
.PHONY: run
