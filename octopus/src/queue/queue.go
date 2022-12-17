package queue

import (
	"context"
	"encoding/json"

	"octopus/src/aws"
	"octopus/src/models"

	"github.com/aws/aws-sdk-go/aws/session"
)

const MAX_BATCH_SIZE = 1 // The size of one SQS batch messaging retrieval

type Queue interface {
	SendMessage(ctx context.Context, msg interface{}) error
	ReceiveMessage(ctx context.Context) (interface{}, *string, error)
	DeleteMessage(ctx context.Context, handle *string) error
}

type queue struct {
	sqs aws.SQS
	url string
}

func NewQueue(session *session.Session, url string) *queue {
	return &queue{
		sqs: aws.NewSQS(session, 0),
		url: url,
	}
}

func (q *queue) SendMessage(ctx context.Context, msg interface{}) error {
	bytes, err := json.Marshal(msg)
	if err != nil {
		return err
	}

	_, err = q.sqs.Send(ctx, &models.Request{
		URL:  q.url,
		Body: string(bytes),
	})

	return err
}

func (q *queue) ReceiveMessage(ctx context.Context) (msg interface{}, handle *string, err error) {
	msgs, err := q.sqs.Receive(ctx, q.url, MAX_BATCH_SIZE)
	if err != nil {
		return nil, nil, err
	}

	if len(msgs) == 0 {
		return nil, nil, nil
	}

	msg = msgs[0].Body
	handle = msgs[0].ReceiptHandle
	err = nil

	return
}

func (q *queue) DeleteMessage(ctx context.Context, handle *string) error {
	err := q.sqs.Delete(ctx, q.url, *handle)
	return err
}
