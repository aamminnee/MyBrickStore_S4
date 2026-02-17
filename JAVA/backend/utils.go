package main

import (
	"os"
	"path"
	"strings"
	"time"
)

func FindOldestFile(dir string) string {
	entries, err := os.ReadDir(dir)
	if err != nil {
		return ""
	}
	oldest := ""
	var oldestDate time.Time
	for _, entry := range entries {
		if !strings.HasSuffix(entry.Name(), ".tmp") {
			s, err := os.Stat(path.Join(dir, entry.Name()))
			if err == nil {
				if oldestDate.IsZero() || s.ModTime().Before(oldestDate) {
					oldestDate = s.ModTime()
					oldest = entry.Name()
				}
			}
		}
	}
	return oldest
}

func WriteFileAtomically(filepath string, content []byte) error {
	err := os.MkdirAll(path.Dir(filepath), 0700)
	if err != nil {
		return err
	}
	err = os.WriteFile(filepath+".tmp", content, 0600)
	if err != nil {
		return err
	}
	return os.Rename(filepath+".tmp", filepath)
}
