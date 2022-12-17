package main

import (
	"context"
	"flag"
	"os"

	"octopus/src/aws"
	"octopus/src/log"
	"octopus/src/models"
	"octopus/src/queue"

	"github.com/joho/godotenv"
	"github.com/kelseyhightower/envconfig"
)

const PROJECT = "octopus"

func main() {
	ctx := context.Background()

	if err := godotenv.Load(); err != nil {
		log.Fatalf("Error loading .env file")
	}

	flag.Parse()
	args := flag.Args()

	// Verify input
	if len(args) == 0 ||
		len(args) > 3 ||
		(args[0] == "delete" && len(args) > 2) ||
		(args[0] == "get" && len(args) > 2) ||
		(args[0] == "all" && len(args) > 1) {
		log.Fatalf("Wrong count of command line arguments")
	}

	var awsConfig aws.Config
	err := envconfig.Process(PROJECT, &awsConfig)
	if err != nil {
		log.Fatalf("Can't read ENV for AWS config")
	}

	session, err := aws.NewSession(awsConfig)
	if err != nil {
		log.Fatalf("Can't create AWS session")
	}

	url := os.Getenv("SQS_URL")
	queue := queue.NewQueue(session, url)
	client := NewClient(queue)

	switch args[0] {

	case "add":
		item := models.NewItem(args[1], args[2])
		client.AddItem(ctx, item)

	case "delete":
		client.DeleteItem(ctx, args[1])

	case "get":
		client.GetItem(ctx, args[1])

	case "all":
		client.GetAllItems(ctx)

	default:
		log.Fatalf("Wrong command: %s", args[0])
	}
}
