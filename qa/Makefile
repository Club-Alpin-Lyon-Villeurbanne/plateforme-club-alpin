.DEFAULT_GOAL := help

SHELL := /bin/bash

.PHONY: help

##
#### General
##

cypress: cypress/node_modules cypress/cypress.env.json ## Open cypress.io client
	cd cypress && node_modules/.bin/cypress open
.PHONY: cypress

cypress-run: cypress/node_modules cypress/cypress.env.json ## Open cypress.io client
	cd cypress && node_modules/.bin/cypress run
.PHONY: cypress

cypress/cypress.env.json: cypress/cypress.env.json.dist
	cp cypress/cypress.env.json.dist cypress/cypress.env.json

cypress/node_modules: cypress/yarn.lock
	cd cypress && yarn

help:
	@grep -hE '(^[a-zA-Z_-]+:.*?##.*$$)|(^###)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m\n/'

