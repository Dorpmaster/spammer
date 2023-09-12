.SILENT: ;               # no need for @
.ONESHELL: ;             # recipes execute in same shell
.NOTPARALLEL: ;          # wait for this target to finish
.EXPORT_ALL_VARIABLES: ; # send all vars to shell
default: help ;   		 # default target
Makefile: ;              # skip prerequisite discovery

NETWORK=spammer_default

help:
	@echo "help: Shows Help menu"
	@echo "status: Shows containers status"
	@echo "build: Builds or rebuilds services"
	@echo "up: Creates and starts application in detached mode (in the background)"
	@echo "stop: Stops application"
	@echo "down: Stops application and removes all containers"
	@echo "build: Builds or rebuilds application image"
	@echo "spam: Starts the \"Spammer\" process"
	@echo "spam-dev: Starts the \"Spammer\" process with shared source codes"
	@echo "checker-up: Starts a pool of \"Checker\" process in background mode"
	@echo "checker-up-dev: Starts the \"Checker\" process with shared source codes"
	@echo "checker-down: Stops a pool of \"Checker\" process and removes all containers"
	@echo "sender-up: Starts a pool of \"Sender\" process in background mode"
	@echo "sender-up-dev: Starts the \"Sender\" process with shared source codes"
	@echo "sender-down: Stops a pool of \"Sender\" process and removes all containers"
	@echo ""

status:
	docker compose ps -a

build:
	docker compose build

up:
	docker compose up -d --remove-orphans

stop:
	docker compose stop

down:
	docker compose down -v

build-php:
	docker build -t spammer .

spam:
	docker run -it --rm --name spammer --network ${NETWORK} --env-file .env spammer

spam-dev:
	docker run -it --rm --name spammer -v "./src:/usr/src/app" --network ${NETWORK} --env-file .env spammer

checker-up:
	docker compose up -d checker

checker-up-dev:
	docker run -it --rm --name checker -v "./src:/usr/src/app" --network ${NETWORK} --env-file .env spammer php ./checker.php

checker-down:
	docker compose down checker

sender-up:
	docker compose up -d sender

sender-up-dev:
	docker run -it --rm --name sender -v "./src:/usr/src/app" --network ${NETWORK} --env-file .env spammer php ./sender.php

sender-down:
	docker compose down sender