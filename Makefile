.PHONY: test test-coverage analyse format lint help

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

test: ## Run the Pest test suite
	vendor/bin/pest

test-coverage: ## Run the Pest test suite with 100% coverage enforcement
	@if php -m | grep -qi '^pcov$$'; then \
		vendor/bin/pest --coverage --min=100; \
	elif php -m | grep -qi '^xdebug$$'; then \
		php -d xdebug.mode=coverage vendor/bin/pest --coverage --min=100; \
	elif XDEBUG_SO=$$(find $$(php -r "echo PHP_PREFIX;") -name xdebug.so 2>/dev/null | head -n 1) && test -n "$$XDEBUG_SO" && test -f "$$XDEBUG_SO"; then \
		php -d zend_extension="$$XDEBUG_SO" -d xdebug.mode=coverage vendor/bin/pest --coverage --min=100; \
	else \
		echo "No coverage driver found. Install PCOV or Xdebug to run coverage locally."; \
		echo "  - PCOV:   pecl install pcov"; \
		echo "  - Xdebug: pecl install xdebug"; \
		exit 1; \
	fi

analyse: ## Run PHPStan static analysis
	vendor/bin/phpstan analyse --no-progress --memory-limit=1G

format: ## Run Laravel Pint code style fixes
	vendor/bin/pint

format-check: ## Check code style without making changes
	vendor/bin/pint --test

lint: format-check analyse ## Run all linting checks
