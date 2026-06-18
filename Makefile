.PHONY: test seed tidy

test:
	cd mcp && go test ./...

seed:
	cd mcp && WORLDKEEP_DATA_DIR=../data go run ./cmd/seed

tidy:
	cd mcp && go mod tidy
