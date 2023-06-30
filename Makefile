.DEFAULT_GOAL := help

filter := "default"
dirname := $(notdir $(CURDIR))
envprefix := $(shell echo "$(dirname)" | tr A-Z a-z)
envname := $(envprefix)test
debug := "false"

help:
	@grep -E '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

composer-install: ## Install composer requirements for ci test environment
	@echo "Install composer requirements with --no-dev"
	composer install --no-dev --ignore-platform-req=ext-redis

install-plugin: .refresh-plugin-list ## Install and activate the plugin
	php ./../../../bin/console sw:plugin:install SwagEssentials
	php ./../../../bin/console sw:cache:clear

.refresh-plugin-list:
	@echo "Refresh the plugin list"
	./../../../bin/console sw:plugin:refresh

fix-cs: ## Run the code style fixer
	./../../../vendor/bin/php-cs-fixer fix -v $(CS_FIXER_RUN) --config .php-cs-fixer.php

fix-cs-dry: CS_FIXER_RUN= --dry-run
fix-cs-dry: fix-cs  ## Run the code style fixer in dry mode

phpstan: ## Run PHPStan
	./../../../vendor/bin/phpstan analyse .