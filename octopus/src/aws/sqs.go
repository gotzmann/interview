package aws

import (
	"context"
	"fmt"
	"time"

	"octopus/src/models"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/sqs"
)

const DEFAULT_WAIT_TIME = 1 // Default polling SQS interval in seconds

type SQS struct {
	timeout time.Duration
	client  *sqs.SQS
}

func NewSQS(session *session.Session, timeout time.Duration) SQS {
	return SQS{
		timeout: timeout,
		client:  sqs.New(session),
	}
}

func (s SQS) Send(ctx context.Context, req *models.Request) (string, error) {
	res, err := s.client.SendMessageWithContext(ctx, &sqs.SendMessageInput{
		MessageBody: aws.String(req.Body),
		QueueUrl:    aws.String(req.URL),
	})
	if err != nil {
		return "", fmt.Errorf("send: %w", err)
	}

	return *res.MessageId, nil
}

func (s SQS) Receive(ctx context.Context, url string, maxMsg int64) ([]*sqs.Message, error) {
	res, err := s.client.ReceiveMessageWithContext(ctx, &sqs.ReceiveMessageInput{
		QueueUrl:            aws.String(url),
		MaxNumberOfMessages: aws.Int64(maxMsg),
		WaitTimeSeconds:     aws.Int64(DEFAULT_WAIT_TIME),
	})

	if err != nil {
		return nil, fmt.Errorf("receive: %w", err)
	}

	return res.Messages, nil
}

func (s SQS) Delete(ctx context.Context, url, handle string) error {
	_, err := s.client.DeleteMessageWithContext(ctx, &sqs.DeleteMessageInput{
		QueueUrl:      aws.String(url),
		ReceiptHandle: aws.String(handle),
	})

	if err != nil {
		return fmt.Errorf("delete: %w", err)
	}

	return nil
}
