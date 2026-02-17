//go:build !auth

package main

import (
	"net/http"
)

func (fc *FactoryConfig) InstallWebHandlers2(mux *http.ServeMux) {
}
