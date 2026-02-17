package main

import (
	"bytes"
	"context"
	"crypto/rand"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"errors"
	"os"
	"path"
	"time"
)

type PoWChallenge struct {
	DataPrefix string   `json:"data_prefix"`
	HashPrefix string   `json:"hash_prefix"`
	Reward     Monetary `json:"reward"`
}

func getRandomHexBytes(n int) string {
	b := make([]byte, n)
	_, _ = rand.Read(b)
	return hex.EncodeToString(b)
}

func (fc *FactoryConfig) CreatePoWChallenge() PoWChallenge {
	var powc PoWChallenge
	powc.Reward = fc.ProofOfWorkReward
	powc.DataPrefix = getRandomHexBytes(16)
	powc.HashPrefix = getRandomHexBytes(fc.ProofOfWorkCost)
	filepath := path.Join(fc.DataDir, "pow_challenges", powc.DataPrefix)
	jsonified, _ := json.Marshal(powc)
	_ = WriteFileAtomically(filepath, jsonified)
	return powc
}

func (fc *FactoryConfig) CheckPoWChallenge(dataPrefix string, answer string) (Monetary, error) {
	decodedAnswer, err := hex.DecodeString(answer)
	if err != nil {
		return 0, errors.New("answer is not a valid hex string")
	}
	decodedDataPrefix, err := hex.DecodeString(dataPrefix)
	if err != nil {
		return 0, errors.New("dataPrefix is not a valid hex string")
	}
	if !bytes.HasPrefix(decodedAnswer, decodedDataPrefix) {
		return 0, errors.New("answer does not have the expected data prefix")
	}
	filepath := path.Join(fc.DataDir, "pow_challenges", dataPrefix)
	jsonChallenge, err2 := os.ReadFile(filepath)
	if err2 != nil {
		return 0, errors.New("the challenge does not exist, has already been used or is expired")
	}
	var challenge PoWChallenge
	err = json.Unmarshal(jsonChallenge, &challenge)
	if err != nil {
		return 0, errors.New("error in the stored challenge")
	}
	decodedHashPrefix, err := hex.DecodeString(challenge.HashPrefix)
	if err != nil {
		return 0, errors.New("hashPrefix is not a valid hex string")
	}
	h := sha256.New()
	h.Write(decodedAnswer)
	result := h.Sum(nil)
	if !bytes.HasPrefix(result, decodedHashPrefix) {
		return 0, errors.New("the computed hash does not start with the correct prefix")
	}
	_ = os.Remove(filepath)
	return challenge.Reward, nil
}

func (fc *FactoryConfig) PurgeOldChallenges() {
	// challenges that are oldest than one hour are purged
	threshold := time.Now().Add(-time.Hour)
	dirpath := path.Join(fc.DataDir, "pow_challenges")
	entries, _ := os.ReadDir(dirpath)
	for _, entry := range entries {
		s, err := os.Stat(path.Join(dirpath, entry.Name()))
		if err != nil && s.ModTime().Before(threshold) {
			os.Remove(path.Join(dirpath, entry.Name()))
		}
	}
}

func (fc *FactoryConfig) RunOldChallengePurger(ctx context.Context) {
	for ctx.Err() == nil {
		fc.PurgeOldChallenges()
		select {
		case <-time.After(time.Hour):
		case <-ctx.Done():
		}
	}
}
