package config

import (
	"fmt"
	"os"
	"path/filepath"
)

type Config struct {
	DataDir string
}

func Load() (Config, error) {
	dir := os.Getenv("WORLDKEEP_DATA_DIR")
	if dir == "" {
		dir = "./data"
	}
	abs, err := filepath.Abs(dir)
	if err != nil {
		return Config{}, fmt.Errorf("data dir: %w", err)
	}
	return Config{DataDir: abs}, nil
}

func (c Config) DBPath(campaignID string) string {
	return filepath.Join(c.DataDir, campaignID+".sqlite")
}
