# Octopus

## Multi-threaded client-server demo with Go

###### What's implemented

- Multi-threaded server with multiple workers listening to message queue and spawning goroutines in parallel when commands are received. Workers count, polling intervals and other important things are configurable and have meaningful defaults
- Single threaded client (no limitations on how many copies could be started in parallel)
- **Amazon SQS** queue between server and clients
- **sync.Map** to store all key-value pairs. It should suit the task better than standard **map[string]interface{}** wrapped with **sync.RWMutex**
- Logging of debug/errors to the screen (stdout) and logging of all server actions to the specified log file

###### What could be improved

- Implement performant **sharded map** allowing higher levels of concurrent access 
- Allow real idempotence of message flow and processing with provided UUIDs
- Allow multiple servers work in parallel within distributed network
- Order stored key-values with timestamp or key (not idiomatic for Go)
- Improve logging with something like **uber/zap** package
- Graceful shutdown without loosing the local server state
- All kinds of testing :)

###### How to build?

On Linux, just type **make** :) You also might need to manually run **go mod tidy** to install project dependencies locally. 

###### How to start?

You should have an AWS account and working SQS queue. Please create **.env** from **env.sample** and type all needed credentials there.

Then **make** and start server:

```./server```

You could check the server output with command like this:

```tail -f server.log```

###### How to use?

You could send commands with client app:

```./client add {key} {value}``` - adds {key: value} pair to server memory

```./client delete {key}``` - deletes key pair from server

```./client get {key}``` - shows the key and corresponding value if it was set

```./client all``` - shows all key-pairs stored to the server
