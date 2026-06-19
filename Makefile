.PHONY: test test-all seed tidy mcp mcp-http api serve setup web-install web-test docker-build backfill-seats

setup: seed web-install

test:
	cd mcp && go test ./...

test-all: test web-test

seed:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/seed

# Unified Go process — MCP + REST on one port (preferred)
serve:
	@chmod +x scripts/serve.sh
	./scripts/serve.sh

mcp:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-mcp

mcp-http:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-mcp-http

api:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-api

web-install:
	cd web && composer install

web-test:
	cd web && php artisan test

docker-build:
	docker build -t worldkeep:local .

backfill-seats:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/backfill-seats

tidy:
	cd mcp && go mod tidy
