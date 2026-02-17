package main

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"errors"
	"net/http"
	"os"
	"path"
	"sync"
	"time"
)

type Order struct {
	Id            string         `json:"id"`
	Payed         bool           `json:"payed"`
	PendingBlocks map[string]int `json:"pending_blocks"`
	Price         Monetary       `json:"price"`
	Delay         time.Duration  `json:"delay"`
	BuiltBlocks   []Block        `json:"built_blocks"`
}

func (fc *FactoryConfig) CreateQuote(basket map[string]int) (*Order, error) {
	idBytes := make([]byte, 16)
	_, _ = rand.Read(idBytes)
	id := hex.EncodeToString(idBytes)
	var price Monetary
	var delay time.Duration
	for k, v := range basket {
		if v <= 0 {
			return nil, errors.New("the quantity for a block must be at least 1")
		}
		b := Block{Name: k}
		area := b.Area()
		p := fc.ComputeCost(area)
		d := fc.ComputeTime(area)
		price += p * Monetary(v)
		delay += d * time.Duration(v)
	}
	order := Order{Id: id, PendingBlocks: basket, Price: price, Delay: delay / time.Duration(fc.Lanes)}
	// write the quote
	filepath := path.Join(fc.DataDir, "quotes", order.Id)
	content, err := json.Marshal(order)
	if err != nil {
		return nil, err
	}
	err = WriteFileAtomically(filepath, content)
	if err != nil {
		return nil, errors.New("cannot write the order file")
	}
	order.Payed = false
	return &order, nil
}

func (fc *FactoryConfig) Order(lock *sync.Mutex, email string, quoteId string) (int, string) {
	// get the order
	var order Order
	content, err := os.ReadFile(path.Join(fc.DataDir, "quotes", quoteId))
	if err != nil {
		return http.StatusNotFound, "the quote does not exist"
	}
	err = json.Unmarshal(content, &order)
	if err != nil {
		return http.StatusInternalServerError, "cannot read the quote"
	}
	balance := fc.UpdateBalance(lock, email, -order.Price)
	if balance == nil {
		return http.StatusPaymentRequired, "available balance is insufficient"
	}
	// move the quote to pending_orders
	_ = os.MkdirAll(path.Join(fc.DataDir, "pending_orders"), 0700)
	_ = os.Rename(path.Join(fc.DataDir, "quotes", quoteId), path.Join(fc.DataDir, "pending_orders", quoteId))
	return http.StatusFound, "OK"
}

func (fc *FactoryConfig) Deliver(orderId string) *Order {
	for _, cat := range []string{"quotes", "pending_orders", "completed_orders"} {
		content, err := os.ReadFile(path.Join(fc.DataDir, cat, orderId))
		if err == nil {
			var order Order
			err = json.Unmarshal(content, &order)
			if err == nil {
				order.Payed = cat != "quotes"
				return &order
			}
		}
	}
	return nil
}
