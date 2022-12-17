package aws

import (
	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/credentials"
	"github.com/aws/aws-sdk-go/aws/session"
)

type Config struct {
	Region string `envconfig:"AWS_REGION"`
	ID     string `envconfig:"AWS_ID"`
	Secret string `envconfig:"AWS_SECRET"`
}

func NewSession(config Config) (*session.Session, error) {
	return session.NewSessionWithOptions(
		session.Options{
			Config: aws.Config{
				Credentials:      credentials.NewStaticCredentials(config.ID, config.Secret, ""),
				Region:           aws.String(config.Region),
				S3ForcePathStyle: aws.Bool(true),
			},
		},
	)
}
