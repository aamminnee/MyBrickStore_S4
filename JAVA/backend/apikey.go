//go:build !auth

package main

func (fc *FactoryConfig) CheckApiKey(email string, apiKey string) bool {
	return true
}