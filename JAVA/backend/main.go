package main

import (
	"context"
	"log"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"sync"
	"syscall"
	"time"
)

func main() {
	var config FactoryConfig
	err := config.LoadAssets()
	if err != nil {
		log.Fatal("cannot read assets: %s", err.Error())
	}
	configPath := os.Args[1]
	addr := os.Args[2]
	config.DataDir = os.Args[3]
	err = config.LoadFromFile(configPath)
	if err != nil {
		log.Fatalf("cannot read config file: %s", err.Error())
	}
	dataDirStat, statErr := os.Stat(config.DataDir)
	if statErr != nil || !dataDirStat.IsDir() {
		log.Fatalf("the data dir does not exist: %s", config.DataDir)
	}
	// create the webserver
	mux := http.NewServeMux()
	config.InstallWebHandlers(mux)
	config.InstallWebHandlers2(mux)
	ctx, _ := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	var wg sync.WaitGroup
	server := http.Server{Addr: addr, Handler: mux}
	go server.ListenAndServe()
	factory := Factory{Config: &config}
	wg.Add(1)
	go func() {
		defer wg.Done()
		factory.Run(ctx)
	}()
	wg.Add(1)
	go func() {
		defer wg.Done()
		config.RunOldChallengePurger(ctx)
	}()
	<-ctx.Done()
	slog.Info("Shutdowning server...")
	ctx2, _ := context.WithTimeout(context.Background(), time.Second*5)
	server.Shutdown(ctx2)
	wg.Wait()
	slog.Info("The end.")
}
