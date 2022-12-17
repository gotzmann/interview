package main

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"strconv"
	"sync"
	"time"

	"octopus/src/log"
	"octopus/src/models"
	"octopus/src/queue"
)

const (
	DEFAULT_MAX_WORKERS   = 4
	DEFAULT_IDLE_INTERVAL = 1000
)

type Server interface {
	Start() error
	Stop() error
}

type server struct {
	items        sync.Map
	que          queue.Queue
	idleInterval time.Duration
	maxWorkers   int
}

func NewServer(queue queue.Queue) server {
	var idleInterval time.Duration
	idleEnv := os.Getenv("SERVER_IDLE_INTERVAL")
	idleInt, err := strconv.ParseInt(idleEnv, 10, 64)

	if err != nil {
		idleInterval = time.Duration(DEFAULT_IDLE_INTERVAL) * time.Millisecond
	} else {
		idleInterval = time.Duration(idleInt) * time.Millisecond
	}

	var maxWorkers int
	maxEnv := os.Getenv("SERVER_MAX_WORKERS")
	maxInt, err := strconv.ParseInt(maxEnv, 10, 64)

	if err != nil {
		maxWorkers = DEFAULT_MAX_WORKERS
	} else {
		maxWorkers = int(maxInt)
	}

	return server{
		items:        sync.Map{},
		que:          queue,
		idleInterval: idleInterval,
		maxWorkers:   maxWorkers,
	}
}

func (s *server) Start(ctx context.Context) {
	log.Debugf("Server starting with %d workers...", s.maxWorkers)

	wg := &sync.WaitGroup{}
	wg.Add(s.maxWorkers)

	for id := 1; id <= s.maxWorkers; id++ {
		go s.worker(ctx, wg, id)
	}

	wg.Wait()

	log.Debugf("Server finishing...")
}

func (s *server) worker(ctx context.Context, wg *sync.WaitGroup, id int) {
	defer wg.Done()

	for {
		// --- Stop worker goroutine when Done() or continue processing
		select {
		case <-ctx.Done():
			return
		default:
		}

		// --- The most interesting part of the server
		any, handle, err := s.que.ReceiveMessage(ctx)
		if err != nil {
			log.Errorf("[WRK #%d] Error : %w", id, err)
			// Sleep some time if there some errors with queue
			time.Sleep(s.idleInterval)
			continue
		} else {
			// Parse message
			if any != nil {
				var msg models.Message
				bytes := []byte(*any.(*string))
				if err := json.Unmarshal(bytes, &msg); err != nil {
					log.Errorf("Unmarshall : %v", err)
					s.que.DeleteMessage(ctx, handle)
				} else {
					// Process message in separate goroutine
					go func() {
						s.processMessage(msg)
						defer s.que.DeleteMessage(ctx, handle)
					}()
				}
			} else {
				// Sleep a bit if the queue is empty
				time.Sleep(s.idleInterval)
			}
		}
	}
}

func (s *server) processMessage(msg models.Message) error {
	switch msg.Method {

	case models.COMMAND_ADD_ITEM:
		key, ok := msg.Params.(map[string]interface{})["Key"].(string)
		value, ok2 := msg.Params.(map[string]interface{})["Value"]
		if !ok || !ok2 {
			return fmt.Errorf("problems whith serialized item")
		}
		item := models.NewItem(key, value)
		s.addItem(&item)

	case models.COMMAND_DELETE_ITEM:
		key, ok := msg.Params.(map[string]interface{})["Key"].(string)
		if !ok {
			return fmt.Errorf("problems whith serialized item key")
		}
		s.deleteItem(key)

	case models.COMMAND_GET_ITEM:
		key, ok := msg.Params.(map[string]interface{})["Key"].(string)
		if !ok {
			return fmt.Errorf("problems whith serialized item key")
		}
		s.getItem(key)

	case models.COMMAND_GET_ALL_ITEMS:
		s.getAllItems()

	default:
		return fmt.Errorf("wrong method call in message")
	}

	return nil
}

func (s *server) addItem(item *models.Item) {
	s.items.Store(item.Key, item.Value)
	str := fmt.Sprintf("[ADD] %s = %s", item.Key, item.Value)
	log.Infof(str)
}

func (s *server) deleteItem(key string) {
	s.items.Delete(key)
	str := fmt.Sprintf("[DEL] %s", key)
	log.Infof(str)
}

func (s *server) getItem(key string) {
	item, ok := s.items.Load(key)
	if !ok {
		str := fmt.Sprintf("[GET] No item for key %s", key)
		log.Infof(str)
	} else {
		str := fmt.Sprintf("[GET] %s = %s", key, item)
		log.Infof(str)
	}
}

func (s *server) getAllItems() {
	var snapshot string
	var count int64

	s.items.Range(
		func(key interface{}, value interface{}) bool {
			snapshot += fmt.Sprintf("{%s: %s} ", key, value)
			count++
			return true
		})

	prefix := fmt.Sprintf("[ALL] Total %d items: ", count)
	log.Infof(prefix + snapshot)
}
