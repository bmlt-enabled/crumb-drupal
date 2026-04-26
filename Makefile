COMMIT := $(shell git rev-parse --short=8 HEAD 2>/dev/null || echo "untagged")
BUILD_DIR := $(or $(BUILD_DIR),build)
BASENAME := $(shell basename $(PWD))
# Canonical Drupal module name — controls the directory inside the zip.
MODULE := crumb
# Zip filename can be overridden via env (CI sets it to e.g. crumb-v0.1.0.zip).
ZIP_FILENAME := $(or $(ZIP_FILENAME),$(BASENAME).zip)
ZIP_FILE := $(BUILD_DIR)/$(ZIP_FILENAME)
VENDOR_AUTOLOAD := vendor/autoload.php

ifeq ($(PROD)x, x)
	COMPOSER_ARGS := --prefer-dist --no-progress
else
	COMPOSER_ARGS := --no-dev
endif

help:  ## Print the help documentation
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: build
build:  ## Build a distributable zip
	mkdir -p $(BUILD_DIR)
	git archive --format=zip --prefix=$(MODULE)/ --output=$(ZIP_FILE) HEAD

.PHONY: clean
clean:  ## Remove build artifacts
	rm -rf $(BUILD_DIR)

$(VENDOR_AUTOLOAD):
	composer install $(COMPOSER_ARGS)

.PHONY: composer
composer: $(VENDOR_AUTOLOAD)  ## Run composer install

.PHONY: lint
lint: composer  ## Run PHP_CodeSniffer (Drupal standards)
	vendor/bin/phpcs

.PHONY: fmt
fmt: composer  ## Auto-fix lint issues with phpcbf
	vendor/bin/phpcbf

.PHONY: test
test: composer  ## Run PHPUnit unit tests
	vendor/bin/phpunit --colors=always

.PHONY: dev
dev:  ## Start the local Drupal stack via docker compose
	docker compose up --build

.PHONY: down
down:  ## Stop the local Drupal stack
	docker compose down

.PHONY: nuke
nuke:  ## Stop the stack and wipe volumes (fresh DB next time)
	docker compose down -v

.PHONY: shell
shell:  ## Open a shell in the running drupal container
	docker compose exec drupal bash

.PHONY: drush
drush:  ## Run drush in the container, e.g. `make drush ARGS="cr"`
	docker compose exec -T drupal drush $(ARGS)

.PHONY: logs
logs:  ## Tail logs from the drupal container
	docker compose logs -f drupal
