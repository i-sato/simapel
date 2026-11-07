SHELL := /bin/sh
COMPOSE ?= docker compose
APP := $(COMPOSE) exec -T app
# Pembanding untuk uji UI terdampak: origin/main bila ada, selain itu main lokal.
BASE_REF ?= $(shell git rev-parse --verify -q origin/main >/dev/null 2>&1 && echo origin/main || echo main)
COVERAGE_MIN ?= 80
E2E_NIP ?= 999900000000000001

.DEFAULT_GOAL := help
.PHONY: help setup up down restart build ps logs shell tools migrate fresh user \
        test test-unit test-feature test-arch coverage lint fix stan audit \
        e2e e2e-affected e2e-report ci

help: ## Tampilkan daftar perintah
	@grep -E '^[a-zA-Z0-9_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

# --- Lingkungan -------------------------------------------------------------

setup: ## Pertama kali: .env acak, build image, dependensi, APP_KEY, migrasi
	./scripts/setup-env.sh
	$(COMPOSE) build
	$(COMPOSE) up -d --wait db app
	$(APP) composer install --no-interaction --no-progress
	@grep -q '^APP_KEY=base64:' .env || $(APP) php artisan key:generate --ansi
	$(APP) php artisan migrate --force
	$(APP) php artisan db:seed --force
	$(COMPOSE) up -d --wait
	@echo "SIMAPEL berjalan di http://localhost:$$(grep '^APP_PORT=' .env | cut -d= -f2). Buat akun pertama: make user"

up: ## Jalankan semua service
	$(COMPOSE) up -d --wait

down: ## Hentikan service (data database tetap tersimpan)
	$(COMPOSE) down

restart: down up ## Mulai ulang service

build: ## Build ulang image aplikasi
	$(COMPOSE) build

ps: ## Status service
	$(COMPOSE) ps

logs: ## Ikuti log semua service
	$(COMPOSE) logs -f

shell: ## Masuk ke shell container aplikasi
	$(COMPOSE) exec app sh

tools: ## Jalankan Adminer di http://localhost:8081
	$(COMPOSE) --profile tools up -d adminer

# --- Database & akun ----------------------------------------------------------

migrate: ## Jalankan migrasi
	$(APP) php artisan migrate

fresh: ## HAPUS semua data lokal lalu migrasi ulang (lokal saja)
	$(APP) php artisan migrate:fresh

user: ## Buat akun pengguna (interaktif)
	$(COMPOSE) exec app php artisan simapel:user:create

# --- Kualitas & pengujian ------------------------------------------------------

test: ## Semua PHPUnit (unit, arsitektur, feature)
	$(APP) php vendor/bin/phpunit

test-unit: ## PHPUnit suite Unit
	$(APP) php vendor/bin/phpunit --testsuite Unit

test-feature: ## PHPUnit suite Feature
	$(APP) php vendor/bin/phpunit --testsuite Feature

test-arch: ## Uji aturan dependensi antar-layer
	$(APP) php vendor/bin/phpunit --testsuite Architecture

coverage: ## PHPUnit + coverage (gagal bila di bawah COVERAGE_MIN %)
	$(APP) php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text --coverage-clover build/coverage/clover.xml
	$(APP) php scripts/coverage-check.php build/coverage/clover.xml $(COVERAGE_MIN)

lint: ## Cek gaya kode (Pint)
	$(APP) php vendor/bin/pint --test

fix: ## Rapikan gaya kode (Pint)
	$(APP) php vendor/bin/pint

stan: ## Analisis statis (Larastan)
	$(APP) php vendor/bin/phpstan analyse --no-progress --memory-limit=1G

audit: ## Audit kerentanan dependensi Composer & npm (e2e)
	$(APP) composer audit --locked
	$(COMPOSE) --profile e2e run --rm --no-deps playwright sh -c "npm audit --omit=optional"

# --- UI / E2E (Playwright di Docker) -----------------------------------------

e2e: ## Semua uji UI Playwright
	@printf 'ALL\n' > e2e/.changed-files
	@$(MAKE) --no-print-directory _e2e-run

e2e-affected: ## Uji UI hanya untuk area yang terdampak perubahan terhadap BASE_REF
	@{ git diff --name-only $(BASE_REF)...HEAD 2>/dev/null; git diff --name-only HEAD; git ls-files --others --exclude-standard; } | sort -u > e2e/.changed-files
	@$(MAKE) --no-print-directory _e2e-run

_e2e-run:
	@./scripts/check-playwright-version.sh
	@password="$$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)Aa1!"; \
	$(COMPOSE) up -d --wait && \
	$(COMPOSE) exec -T -e E2E_NIP=$(E2E_NIP) -e E2E_PASSWORD="$$password" app php artisan db:seed --class=E2eSeeder --force && \
	$(COMPOSE) --profile e2e run --rm -e E2E_NIP=$(E2E_NIP) -e E2E_PASSWORD="$$password" playwright \
		sh -c "npm ci --no-audit --no-fund --loglevel=error && node scripts/run-affected.mjs .changed-files"

e2e-report: ## Buka laporan Playwright terakhir (e2e/playwright-report)
	@echo "Buka e2e/playwright-report/index.html di browser."

ci: lint stan coverage e2e-affected ## Rangkaian pemeriksaan seperti CI
