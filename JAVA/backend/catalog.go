package main

import (
	"embed"
	"errors"
	"fmt"
	"io/fs"
	"slices"
	"strings"
)

//go:embed assets/*
var resources embed.FS

func (fc *FactoryConfig) LoadBlockCatalog() ([]string, error) {
	data, err := fs.ReadFile(resources, "assets/blocks.txt")
	if err != nil {
		return nil, err
	}
	lines := strings.Split(string(data), "\n")
	blocks := make([]string, 0)
	for _, line := range lines {
		line = strings.TrimSpace(line)
		if line != "" {
			blocks = append(blocks, line)
		}
	}
	slices.Sort(blocks)
	return blocks, nil
}

type Color struct {
	Name    string `json:"name"`
	HexCode string `json:"hex_code"`
}

func (fc *FactoryConfig) LoadColorCatalog() ([]Color, error) {
	data, err := fs.ReadFile(resources, "assets/colors.csv")
	if err != nil {
		return nil, err
	}
	lines := strings.Split(string(data), "\n")
	colors := make([]Color, 0)
	for _, line := range lines[1:] {
		line = strings.TrimSpace(line)
		if line != "" {
			parts := strings.Split(line, ",")
			name := parts[1]
			hexCode := parts[2]
			colors = append(colors, Color{name, strings.ToLower(hexCode)})
		}
	}
	slices.SortFunc(colors, func(a, b Color) int {
		if a.HexCode < b.HexCode {
			return -1
		}
		if a.HexCode > b.HexCode {
			return 1
		}
		return 0
	})
	return colors, nil
}

func (fc *FactoryConfig) LoadAssets() error {
	blocks, err1 := fc.LoadBlockCatalog()
	fc.BlockCatalog = blocks
	colors, err2 := fc.LoadColorCatalog()
	fc.ColorCatalog = colors
	return errors.Join(err1, err2)
}

func (fc *FactoryConfig) CheckBlockReference(reference string) bool {
	v1, v2, missing, color := ParseBlockReference(reference)
	if v1 == 0 || v2 == 0 || color == "" {
		return false
	}
	ref := fmt.Sprintf("%d-%d", v1, v2)
	if missing != "" {
		ref += "-" + missing
	}
	_, found := slices.BinarySearch(fc.BlockCatalog, ref)
	if !found {
		return false
	}
	_, found2 := slices.BinarySearchFunc(fc.ColorCatalog, color, func(c Color, s string) int {
		if c.HexCode < s {
			return -1
		}
		if c.HexCode > s {
			return 1
		}
		return 0
	})
	if !found2 {
		return false
	}
	return true
}
