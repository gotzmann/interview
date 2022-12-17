package main

import (
	"context"

	"octopus/src/models"
	"octopus/src/queue"
)

type Client interface {
	AddItem(item models.Item) error
	RemoveItem(key string) error
	GetItem(key string) error
	GetAllItems() error
}

type client struct {
	que queue.Queue
}

func NewClient(queue queue.Queue) client {
	return client{que: queue}
}

func (c *client) AddItem(ctx context.Context, item models.Item) error {
	msg := models.NewMessage(
		models.COMMAND_ADD_ITEM,
		item)

	return c.que.SendMessage(ctx, msg)
}

func (c *client) DeleteItem(ctx context.Context, key string) error {
	msg := models.NewMessage(
		models.COMMAND_DELETE_ITEM,
		models.NewItem(key, nil))

	return c.que.SendMessage(ctx, msg)
}

func (c *client) GetItem(ctx context.Context, key string) error {
	msg := models.NewMessage(
		models.COMMAND_GET_ITEM,
		models.NewItem(key, nil))

	return c.que.SendMessage(ctx, msg)
}

func (c *client) GetAllItems(ctx context.Context) error {
	msg := models.NewMessage(
		models.COMMAND_GET_ALL_ITEMS,
		nil)

	return c.que.SendMessage(ctx, msg)
}
