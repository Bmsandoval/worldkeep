.PHONY: help setup install db-reset migrate seed test serve dev fe-build

.DEFAULT_GOAL := help

APP_URL ?= http://127.0.0.1:8000
SQLITE := database/database.sqlite

help: ## Show available targets
	@grep -E '^[a-zA-Z0-9_-]+:.*##' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

setup: install migrate ## First-time setup (.env, deps, SQLite, migrate)
	@echo "Ready: $(APP_URL)/app"

install: ## Composer + .env + app key + SQLite file
	composer install
	@test -f .env || cp .env.example .env
	@php artisan key:generate --ansi
	@touch $(SQLITE)

db-reset: ## Drop SQLite DB and rebuild (migrate)
	rm -f $(SQLITE)
	touch $(SQLITE)
	$(MAKE) migrate

migrate: ## Run migrations
	@test -f $(SQLITE) || touch $(SQLITE)
	php artisan migrate

seed: ## Run database seeders (optional)
	php artisan db:seed

test: ## Run PHPUnit (composer test)
	composer test

serve: ## Start dev server (UI at /app)
	@echo "UI: $(APP_URL)/app"
	php artisan serve

dev: ## Laravel dev stack (server, queue, logs, Vite) via composer
	composer run dev

fe-build: ## Build frontend assets (optional; UI works without this)
	npm install
	npm run build
