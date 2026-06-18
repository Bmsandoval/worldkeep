.PHONY: test seed tidy mcp

test:
	cd mcp && go test ./...

seed:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/seed

mcp:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/worldkeep-mcp

tidy:
	cd mcp && go mod tidy
