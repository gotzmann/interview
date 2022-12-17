package main

import (
	"context"
	"os"

	"octopus/src/aws"
	"octopus/src/log"
	"octopus/src/queue"

	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
)

const PROJECT = "octopus"

func main() {
	if err := godotenv.Load(); err != nil {
		log.Fatalf("Error loading .env file")
	}

	var awsConfig aws.Config
	if err := envconfig.Process(PROJECT, &awsConfig); err != nil {
		log.Fatalf("Can't read ENV for AWS config")
	}

	session, err := aws.NewSession(awsConfig)
	if err != nil {
		log.Fatalf("Can't create AWS session")
	}

	url := os.Getenv("SQS_URL")
	queue := queue.NewQueue(session, url)

	file, err := os.OpenFile(
		os.Getenv("SERVER_LOG"),
		os.O_CREATE|os.O_WRONLY|os.O_APPEND,
		0666)
	if err != nil {
		log.Fatalf("Can't attach log file %s", os.Getenv("SERVER_LOG"))
	}

	log.File = file

	server := NewServer(queue)
	server.Start(context.Background())
}
