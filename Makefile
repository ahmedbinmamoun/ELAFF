# Developer commands (PRD §13.3). Everything runs inside Docker.
DC      := docker compose
EXEC    := $(DC) exec app
# Tests run against a dedicated MySQL database, never the dev one.
TESTENV := -e DB_CONNECTION=mysql -e DB_DATABASE=elaf_testing

.DEFAULT_GOAL := help
.PHONY: help env up down install migrate seed fresh test check lint analyse shell logs build-prod

help: ## List targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

env:
	@test -f .env || (cp .env.example .env && sed -i "s/^UID=.*/UID=$$(id -u)/; s/^GID=.*/GID=$$(id -g)/" .env && echo "Created .env from .env.example")

up: env ## Build and start the dev stack, install deps, migrate
	$(DC) up -d --build --wait app mysql redis nginx mailpit
	$(MAKE) install
	$(DC) up -d queue scheduler vite

down: ## Stop the stack
	$(DC) down

install: ## composer install, app key, migrations
	$(EXEC) composer install --no-interaction
	@grep -q '^APP_KEY=base64' .env || $(EXEC) php artisan key:generate --ansi
	$(EXEC) php artisan migrate --force
	@test -L public/storage || $(EXEC) php artisan storage:link

migrate: ## Run migrations
	$(EXEC) php artisan migrate

seed: ## Seed the database
	$(EXEC) php artisan db:seed

fresh: ## Drop all tables, migrate and seed
	$(EXEC) php artisan migrate:fresh --seed

test: ## Pest (all suites) on MySQL
	$(DC) exec $(TESTENV) app composer test

lint: ## Pint (check only)
	$(EXEC) composer lint

analyse: ## Larastan (level max)
	$(EXEC) composer analyse

check: lint analyse test ## Quality gates: Pint + Larastan + Pest

shell: ## Shell in the app container
	$(EXEC) bash

logs: ## Follow logs
	$(DC) logs -f --tail=100

build-prod: ## Build production images locally
	docker build -f docker/php/Dockerfile --target prod -t elaf-app:local .
	docker build -f docker/php/Dockerfile --target web -t elaf-web:local .
