package main

import (
	"context"
	"encoding/json"
	"log/slog"
	"os"
	"path"
	"sync"
	"time"
)

type Factory struct {
	Config             *FactoryConfig
	ProductionCounters map[string]uint64
}

type BlockOrder struct {
	Name     string
	Callback func(b *Block)
}

func (f *Factory) runLane(ctx context.Context, orderChannel chan BlockOrder) {
	for order := range orderChannel {
		block := f.Config.BuildBlock(ctx, order.Name)
		if block != nil {
			order.Callback(block)
		}
		if ctx.Err() != nil {
			slog.Info("End of lane", "orderChannel", orderChannel)
			return
		}
	}
}

func (fc *FactoryConfig) ReadProductionCounters() (map[string]uint64, error) {
	filepath := path.Join(fc.DataDir, "production_counters")
	content, err := os.ReadFile(filepath)
	if err != nil {
		return nil, err
	}
	var counters map[string]uint64
	err = json.Unmarshal(content, &counters)
	if err != nil {
		return nil, err
	}
	return counters, nil
}

func (f *Factory) writeProductionCounters() error {
	filepath := path.Join(f.Config.DataDir, "production_counters")
	content, err := json.Marshal(f.ProductionCounters)
	if err != nil {
		return err
	}
	return WriteFileAtomically(filepath, content)
}

func (f *Factory) Run(ctx context.Context) {
	pc, err := f.Config.ReadProductionCounters()
	if err != nil {
		slog.Warn("Cannot read production counters", "error", err)
	} else {
		f.ProductionCounters = pc
	}
	var wg sync.WaitGroup
	laneChannels := make([]chan BlockOrder, 0)
	for i := 0; i < f.Config.Lanes; i++ {
		ch := make(chan BlockOrder)
		laneChannels = append(laneChannels, ch)
		wg.Add(1)
		go func() {
			defer wg.Done()
			f.runLane(ctx, ch)
		}()
	}
	for ctx.Err() == nil {
		// select the order whose modified date is the oldest
		order := FindOldestFile(path.Join(f.Config.DataDir, "pending_orders"))
		if order != "" {
			filepath := path.Join(f.Config.DataDir, "pending_orders", order)
			filepathComplete := path.Join(f.Config.DataDir, "completed_orders", order)
			rawOrder, err := os.ReadFile(filepath)
			var orderObject Order
			if err == nil {
				err = json.Unmarshal(rawOrder, &orderObject)
				if err == nil {
					orderedBlocks := make([]string, 0)
					obtainedBlocks := make([]*Block, 0)
					remainingBlocks := 0
					finishedChannel := make(chan any)
					var lock sync.Mutex
					for name, samples := range orderObject.PendingBlocks {
						for i := 0; i < samples && len(orderedBlocks) < len(laneChannels); i++ {
							orderedBlocks = append(orderedBlocks, name)
							obtainedBlocks = append(obtainedBlocks, nil)
							remainingBlocks += 1
						}
						if len(orderedBlocks) == len(laneChannels) {
							break
						}
						// the following case should not be possible (unless we authorize quotes with <=0 quantities)
						if samples <= 0 {
							delete(orderObject.PendingBlocks, name)
						}
					}
					for i, orderorderedBlock := range orderedBlocks {
						fi := i
						laneChannels[i] <- BlockOrder{orderorderedBlock, func(b *Block) {
							lock.Lock()
							defer lock.Unlock()
							obtainedBlocks[fi] = b
							remainingBlocks -= 1
							if remainingBlocks == 0 {
								close(finishedChannel)
							}
						}}
					}
					if len(orderedBlocks) > 0 {
						select {
						case <-finishedChannel:
						case <-ctx.Done():
						}
					}
					// slog.Info("Manufacturing", "order", orderObject.Id, "pendingBlocks", len(orderObject.PendingBlocks), "builtBlocks", len(orderObject.BuiltBlocks))
					if remainingBlocks == 0 {
						for _, block := range obtainedBlocks {
							orderObject.PendingBlocks[block.Name] -= 1
							if orderObject.PendingBlocks[block.Name] <= 0 {
								delete(orderObject.PendingBlocks, block.Name)
							}
							orderObject.BuiltBlocks = append(orderObject.BuiltBlocks, *block)
							if f.ProductionCounters == nil {
								f.ProductionCounters = make(map[string]uint64)
							}
							f.ProductionCounters[block.Name] += 1
						}
						completed := len(orderObject.PendingBlocks) == 0
						data, err := json.Marshal(orderObject)
						if err == nil {
							if !completed {
								_ = WriteFileAtomically(filepath, data)
							} else {
								_ = WriteFileAtomically(filepathComplete, data)
								_ = os.Remove(filepath)
							}
						}
						_ = f.writeProductionCounters()
					}
				}
			}
		} else {
			// wait one second if no order is present
			select {
			case <-time.After(time.Second):
			case <-ctx.Done():
			}
		}
	}
}
