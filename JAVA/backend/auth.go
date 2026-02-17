package main

import (
	"crypto/sha256"
	"encoding/hex"
	"net/http"
)

func (fc *FactoryConfig) ComputeApiKey(email string) string {
	content := append([]byte(email), []byte(fc.Secret)...)
	h := sha256.Sum256(content)
	h2 := h[:]
	return hex.EncodeToString(h2)
}

func (fc *FactoryConfig) HandlerWrapperWithAuth(handler http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		email := r.Header.Get("X-Email")
		if !fc.EmailPattern.MatchString(email) {
			http.Error(w, "email address is not valid", http.StatusUnauthorized)
			return
		}
		secretKey := r.Header.Get("X-Secret-Key")
		checked := fc.CheckApiKey(email, secretKey)
		if checked {
			handler(w, r)
		} else {
			http.Error(w, "secret API key is invalid", http.StatusUnauthorized)
		}
	}
}
