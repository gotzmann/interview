package models

import "github.com/google/uuid"

const (
	COMMAND_ADD_ITEM      = "add-item"
	COMMAND_DELETE_ITEM   = "delete-item"
	COMMAND_GET_ITEM      = "get-item"
	COMMAND_GET_ALL_ITEMS = "get-all-items"
)

type Message struct {
	ID     string      `json:"id"`
	Method string      `json:"method"`
	Params interface{} `json:"params"`
}

func NewMessage(method string, item interface{}) Message {
	return Message{
		ID:     uuid.New().String(),
		Method: method,
		Params: item,
	}
}
