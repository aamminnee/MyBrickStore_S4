package main

import (
	"context"
	"crypto/ed25519"
	"crypto/rand"
	"encoding/binary"
	"encoding/hex"
	"strconv"
	"strings"
	"time"
)

type Block struct {
	Name        string `json:"name"`
	Serial      string `json:"serial"`
	Certificate string `json:"certificate"`
}

func ParseBlockReference(reference string) (int, int, string, string) {
	elements := strings.Split(reference, "/")
	elements2 := strings.Split(elements[0], "-")
	if len(elements2) < 2 || len(elements2) > 3 {
		return 0, 0, "", ""
	}
	v1, err1 := strconv.ParseInt(elements2[0], 10, 64)
	v2, err2 := strconv.ParseInt(elements2[1], 10, 64)
	if err1 != nil || err2 != nil {
		return 0, 0, "", ""
	}
	missing := ""
	if len(elements2) == 3 {
		missing = elements2[2]
	}
	color := ""
	if len(elements) == 2 {
		color = strings.ToLower(elements[1])
	}
	return int(v1), int(v2), missing, color
}

func (b *Block) Area() int {
	v1, v2, _, _ := ParseBlockReference(b.Name)
	return int(v1 * v2)
}

func CreateSerial(t time.Time) string {
	epoch2000, _ := time.Parse("2006-01-02", "2000-01-01")
	elapsed := t.Sub(epoch2000).Milliseconds()
	day := uint16(elapsed / (3600 * 1000 * 24))
	hour := uint16(elapsed % (3600 * 1000 * 24))
	r := make([]byte, 11)
	_, _ = rand.Read(r)
	var result []byte
	result, _ = binary.Append(result, binary.BigEndian, day)
	result, _ = binary.Append(result, binary.BigEndian, hour)
	result = append(result, r...)
	return hex.EncodeToString(result)
}

func (b *Block) CertifiedMessage() []byte {
	content := []byte(b.Name)
	serial, _ := hex.DecodeString(b.Serial)
	content = append(content, serial...)
	return content
}

func (b *Block) Certify(privateKey ed25519.PrivateKey) string {
	return hex.EncodeToString(ed25519.Sign(privateKey, b.CertifiedMessage()))
}

func (fc *FactoryConfig) BuildBlock(ctx context.Context, name string) *Block {
	now := time.Now()
	b := Block{Name: name, Serial: CreateSerial(now), Certificate: ""}
	t := fc.ComputeTime(b.Area())
	built := false
	select {
	case <-time.After(t):
		built = true
	case <-ctx.Done():
	}
	if built {
		if fc.SigningPrivateKey != "" {
			b.Certificate = b.Certify(fc.DecodedSigningPrivateKey)
		}
		return &b
	} else {
		return nil
	}
}

func (b *Block) CheckCertificate(publicKey ed25519.PublicKey) bool {
	c, _ := hex.DecodeString(b.Certificate)
	return ed25519.Verify(publicKey, b.CertifiedMessage(), c)
}
