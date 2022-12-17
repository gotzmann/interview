.PHONY: all up down install update test

# Run checkout API with local .env settings
all:
up:
	docker-compose.exe -f docker-compose.yml up --force-recreate --build

# Graceful shutdown
down:
	docker-compose -f docker-compose.yml down

# Update source code from GitHub
install:
	git pull
	composer install
	rm -f checkout.db
	sqlite3 checkout.db < init.sql

# Update source code from GitHub
update:
	git pull
	composer install

# Run PHUnit test suite
test:
	vendor/bin/phpunit tests