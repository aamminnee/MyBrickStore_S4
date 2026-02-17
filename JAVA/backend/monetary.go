package main

import (
	"encoding/json"
	"fmt"
	"strconv"
	"strings"

	"gopkg.in/yaml.v3"
)

type Monetary int64

const MonetaryPrecision = 1000000000
const MonetaryPrecisionLen = 9

func (m Monetary) MarshalJSON() ([]byte, error) {
	integerPart := m / MonetaryPrecision
	fractPart := m % MonetaryPrecision
	s := fmt.Sprintf("%d.%d", integerPart, fractPart)
	return json.Marshal(s)
}

func (m *Monetary) UnmarshalFromStr(s string) error {
	elements := strings.Split(s, ".")
	var result int64
	var err error
	result, err = strconv.ParseInt(elements[0], 10, 64)
	if err != nil {
		return err
	}
	result *= MonetaryPrecision
	if len(elements) >= 2 {
		elements[1] = elements[1][0:min(len(elements[1]), MonetaryPrecisionLen)]
		elements[1] += strings.Repeat("0", MonetaryPrecisionLen-len(elements[1]))
		var value int64
		value, err := strconv.ParseInt(elements[1], 10, 64)
		if err != nil {
			return err
		}
		result += value
	}
	*m = Monetary(result)
	return nil
}

func (m *Monetary) UnmarshalJSON(data []byte) error {
	var s string
	err := json.Unmarshal(data, &s)
	if err != nil {
		return err
	}
	return m.UnmarshalFromStr(s)
}

func (m *Monetary) UnmarshalYAML(value *yaml.Node) error {
	var s string
	if err := value.Decode(&s); err != nil {
		return err
	}
	return m.UnmarshalFromStr(s)
}
