package models

type Item struct {
	Key   string
	Value interface{}
}

func NewItem(key string, value interface{}) Item {
	return Item{
		Key:   key,
		Value: value,
	}
}
