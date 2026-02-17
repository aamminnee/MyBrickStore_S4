package main

import (
	"crypto/ed25519"
	"crypto/x509"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"path"
	"sync"
)

func (fc *FactoryConfig) InstallWebHandlers(mux *http.ServeMux) {
	var accountLock sync.Mutex

	mux.HandleFunc("GET /ping", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		http.Error(w, "OK", 200)
	}))
	mux.HandleFunc("GET /production", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		filepath := path.Join(fc.DataDir, "production_counters")
		s, err := os.Stat(filepath)
		if err == nil {
			f, err := os.Open(filepath)
			defer f.Close()
			if err == nil {
				http.ServeContent(w, r, "production", s.ModTime(), f)
				return
			}
		}
		w.Write([]byte("{}"))
	})
	mux.HandleFunc("GET /signature-public-key", func(w http.ResponseWriter, r *http.Request) {
		if fc.SigningPrivateKey == "" {
			http.Error(w, "signature key n configured", http.StatusNotFound)
			return
		}
		w.Header().Set("Content-Type", "text/plain")
		pk := fc.DecodedSigningPrivateKey.Public()
		content, err := x509.MarshalPKIXPublicKey(pk)
		if err != nil {
			http.Error(w, "cannot marshal public key", http.StatusInternalServerError)
			return
		}
		content2 := base64.StdEncoding.EncodeToString(content)
		_, _ = w.Write([]byte(content2))
	})
	mux.HandleFunc("POST /verify", func(w http.ResponseWriter, r *http.Request) {
		if fc.SigningPrivateKey == "" {
			http.Error(w, "no signing key is configured", http.StatusInternalServerError)
			return
		}
		var b Block
		decoder := json.NewDecoder(r.Body)
		if err := decoder.Decode(&b); err != nil {
			http.Error(w, "Error decoding JSON", http.StatusBadRequest)
			return
		}
		checked := b.CheckCertificate(fc.DecodedSigningPrivateKey.Public().(ed25519.PublicKey))
		if checked {
			http.Error(w, "OK", http.StatusFound)
			return
		} else {
			http.Error(w, "KO", http.StatusNotFound)
			return
		}
	})
	mux.HandleFunc("GET /catalog", func(w http.ResponseWriter, r *http.Request) {
		var output struct {
			Blocks []string `json:"blocks"`
			Colors []Color  `json:"colors"`
		}
		output.Blocks = fc.BlockCatalog
		output.Colors = fc.ColorCatalog
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(output)
	})
	mux.HandleFunc("GET /billing/balance", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		email := r.Header.Get("X-Email")
		balance := fc.GetBalance(email)
		var balance2 struct {
			Balance Monetary `json:"balance"`
		}
		balance2.Balance = balance
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(balance2)
	}))
	mux.HandleFunc("GET /billing/challenge", func(w http.ResponseWriter, r *http.Request) {
		challenge := fc.CreatePoWChallenge()
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(challenge)
	})
	mux.HandleFunc("POST /billing/challenge-answer", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		email := r.Header.Get("X-Email")
		var requestData struct {
			DataPrefix string `json:"data_prefix"`
			Answer     string `json:"answer"`
		}
		decoder := json.NewDecoder(r.Body)
		if err := decoder.Decode(&requestData); err != nil {
			http.Error(w, "Error decoding JSON", http.StatusBadRequest)
			return
		}
		reward, err := fc.RefillAccount(&accountLock, email, requestData.DataPrefix, requestData.Answer)
		if err != nil {
			http.Error(w, err.Error(), http.StatusBadRequest)
			return
		}
		var answerData struct {
			Reward Monetary `json:"reward"`
		}
		answerData.Reward = reward
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(answerData)
	}))
	mux.HandleFunc("POST /ordering/quote-request", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		var basket map[string]int
		decoder := json.NewDecoder(r.Body)
		if err := decoder.Decode(&basket); err != nil {
			http.Error(w, "Error decoding JSON", http.StatusBadRequest)
			return
		}
		if len(basket) == 0 {
			http.Error(w, "No element in the request", http.StatusBadRequest)
			return
		}
		// check if the reference exists
		for k, _ := range basket {
			inCatalog := fc.CheckBlockReference(k)
			if !inCatalog {
				http.Error(w, fmt.Sprintf("The reference %s does not exist", k), http.StatusBadRequest)
				return
			}
		}
		order, err := fc.CreateQuote(basket)
		if err != nil {
			http.Error(w, "the server encountered an error while managing the quote", http.StatusInternalServerError)
			return
		}
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(order)
	}))
	mux.HandleFunc("POST /ordering/order/{quoteId}", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		email := r.Header.Get("X-Email")
		quoteId := r.PathValue("quoteId")
		code, message := fc.Order(&accountLock, email, quoteId)
		http.Error(w, message, code)
	}))
	mux.HandleFunc("GET /ordering/deliver/{orderId}", fc.HandlerWrapperWithAuth(func(w http.ResponseWriter, r *http.Request) {
		quoteId := r.PathValue("orderId")
		order := fc.Deliver(quoteId)
		if order == nil {
			http.NotFound(w, r)
		}
		encoder := json.NewEncoder(w)
		w.Header().Set("Content-Type", "application/json")
		encoder.Encode(order)
	}))
}
