package main

import (
	"crypto/ed25519"
	"crypto/x509"
	"encoding/base64"
	"errors"
	"math"
	"os"
	"regexp"
	"time"

	"gopkg.in/yaml.v3"
)

type FactoryConfig struct {
	UnitPrice           Monetary       `yaml:"unit_price"` // unitary price to build a block
	UnitTime            time.Duration  `yaml:"unit_time"` // unitary time to build a block
	PriceDecreaseFactor float64        `yaml:"price_decrease_factor"` // price decrease factor to build big blocks
	TimeDecreaseFactor  float64        `yaml:"time_decrease_factor"` // time decrease factor to build big blocks
	ProofOfWorkCost     int            `yaml:"proof_of_work_cost"` // cost for a proof of work (number of bytes for the hash prefix)
	ProofOfWorkReward   Monetary       `yaml:"proof_of_work_reward"` // money earned if a proof of work challenge is succeeded
	Secret              string         `yaml:"secret"` // secret used for authentication key (must not be revealed)
	SigningPrivateKey   string         `yaml:"signing_private_key"` // private key to sign the blocks (must not be revealed)
	Lanes               int            `yaml:"lanes"` // number of lanes of production in the factory
	EmailPattern        *regexp.Regexp `yaml:"email_pattern"` // pattern that must be enforced for the users
	Referrer            string         `yaml:"referrer"` // special use (not mandatory)

	// the following fields are automatically filled, they are useless in the config file
	DataDir                  string // to be specified after YAML file loading
	DecodedSigningPrivateKey ed25519.PrivateKey
	BlockCatalog             []string
	ColorCatalog             []Color
}

func (fc *FactoryConfig) LoadFromFile(filepath string) error {
	content, err := os.ReadFile(filepath)
	if err != nil {
		return err
	}
	err = yaml.Unmarshal(content, &fc)
	if err != nil {
		return err
	}
	if fc.Lanes == 0 {
		fc.Lanes = 1 // at least one line is enabled
	}
	if fc.SigningPrivateKey != "" {
		d, err := base64.StdEncoding.DecodeString(fc.SigningPrivateKey)
		k, err := x509.ParsePKCS8PrivateKey(d)
		if err != nil {
			return err
		}
		k2, ok := k.(ed25519.PrivateKey)
		if !ok {
			return errors.New("private key does not appear being a ed25519 key")
		}
		fc.DecodedSigningPrivateKey = k2
	}
	return nil
}

func (fc *FactoryConfig) ComputeCost(area int) Monetary {
	v := uint64(float64(fc.UnitPrice) * math.Pow(fc.PriceDecreaseFactor, math.Log2(float64(area))))
	return Monetary(v)
}

func (fc *FactoryConfig) ComputeTime(area int) time.Duration {
	v := uint64(float64(fc.UnitTime.Nanoseconds()) * math.Pow(fc.TimeDecreaseFactor, math.Log2(float64(area))))
	return time.Nanosecond * time.Duration(v)
}
