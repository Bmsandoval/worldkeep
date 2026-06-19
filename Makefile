.PHONY: test test-all seed tidy mcp serve setup web-install web-test docker-build backfill-seats playtest-handoff

setup: seed web-install

test: web-test

test-all: web-test

seed:
	cd web && php artisan worldkeep:seed

serve:
	cd web && php artisan serve

mcp:
	cd web && php artisan worldkeep:mcp

web-install:
	cd web && composer install

web-test:
	cd web && php artisan test

docker-build:
	docker build -t worldkeep:local .

backfill-seats:
	cd web && php artisan worldkeep:seed

playtest-handoff:
	@chmod +x scripts/playtest-handoff.sh
	./scripts/playtest-handoff.sh
