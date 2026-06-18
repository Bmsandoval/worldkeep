.PHONY: test seed tidy mcp mcp-http setup

setup: seed

test:
	cd mcp && go test ./...

seed:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/seed

mcp:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-mcp

mcp-http:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-mcp-http

tidy:
	cd mcp && go mod tidy
