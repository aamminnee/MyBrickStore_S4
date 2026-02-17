package main

import (
	"encoding/json"
	"os"
	"path"
	"sync"
)

func (fc *FactoryConfig) RefillAccount(lock *sync.Mutex, email string, dataPrefix string, answer string) (Monetary, error) {
	reward, challengeErr := fc.CheckPoWChallenge(dataPrefix, answer)
	if challengeErr != nil {
		return 0, challengeErr
	}
	fc.UpdateBalance(lock, email, reward)
	return reward, challengeErr
}

func (fc *FactoryConfig) GetBalance(email string) Monetary {
	filepath := path.Join(fc.DataDir, "balances", email)
	content, err := os.ReadFile(filepath)
	var balance Monetary
	if err == nil {
		err = json.Unmarshal(content, &balance)
	}
	return balance
}

func (fc *FactoryConfig) UpdateBalance(lock *sync.Mutex, email string, delta Monetary) *Monetary {
	filepath := path.Join(fc.DataDir, "balances", email)
	lock.Lock()
	defer lock.Unlock()
	balance := fc.GetBalance(email)
	balance += delta
	if balance < 0 {
		return nil
	} else {
		content, err := json.Marshal(balance)
		if err != nil {
			return nil
		}
		err = WriteFileAtomically(filepath, content)
		if err != nil {
			return nil
		}
		return &balance
	}
}
