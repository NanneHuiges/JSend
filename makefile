# Summary
# Setting up local environment
#     * make build install PHP=8.3
#
# Run a shell
#     * make shell
#
# Run other tools:
# make <tool>
#    * phpunit
#    * phpstan
#    * codeclimate


# Get PHP version from the commandline, or default to this
PHP?=8.3

# This is used to bind a volume to the root of the project.
# We use the location of the makefile since the makefile is located in the project's root and
# makes sure you dont bind the working directory of your terminal since the working directory can be different than the project root.
MAKEFILE_DIR :=  $(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

# Constants for easy reuse.
IMAGE_NAME = jsend_dev_image$(PHP)
CONTAINER_NAME = jsend_dev$(PHP)
# shortens exec calls
EXEC := docker exec $(CONTAINER_NAME)
# Helper values for checking the state of the container
CONTAINER_EXISTS := $(shell docker ps -a -q -f name=$(CONTAINER_NAME) 2> /dev/null)
CONTAINER_STOPPED := $(shell docker ps -aq -f status=exited -f name=$(CONTAINER_NAME) 2> /dev/null)

build:
# build docker images we can use, with the given IMAGE_NAME and versions
	docker build -t $(IMAGE_NAME) ./docker/jsend-dev/ --build-arg PHP_VERSION=$(PHP_VERSION)

install: | run
# Have composer install all the requirements
	$(EXEC) composer install

stop:
# docker kill stops the container
	docker kill $(CONTAINER_NAME)

remove: | stop
# docker rm removes a container. You might need to kill ($ make stop) before you can do this.
	docker rm $(CONTAINER_NAME)

run:
# Called automatically for users.
# Little bit of logic so that the container will be started if it is stopped
# or do nothing if the container is running normally.
	if [ -n "$(CONTAINER_EXISTS)" ]; then \
		if [ -n  "$(CONTAINER_STOPPED)" ]; then \
			docker rm $(CONTAINER_NAME); \
			docker run -d -t -v $(MAKEFILE_DIR):/usr/src --rm --name $(CONTAINER_NAME) $(IMAGE_NAME); \
		fi \
	else \
		docker run -d -t -v $(MAKEFILE_DIR):/usr/src --rm --name $(CONTAINER_NAME) $(IMAGE_NAME); \
	fi

shell: | run
# Docker exec run a command in a running container. In this case it opens a shell (bash) inside the (CONTAINER_NAME) container
# -it means interactive and tty. This keeps the stdin open and allocates a pseudo tty.
# /bin/bash is the executable we want to execute. In this case we want to run a simple bash shell.
	docker exec -it $(CONTAINER_NAME) /bin/bash

phpunit: | run
# Runs phpunit on the PHP code
	$(EXEC) ./vendor/bin/phpunit

codeclimate: | run
# Runs codeclimate. as it uses docker itself, it is best run from its own dockerfile
	  docker run \
        --interactive --tty --rm \
        --env CODECLIMATE_CODE="$PWD" \
        --volume "$PWD":/code \
        --volume /var/run/docker.sock:/var/run/docker.sock \
        --volume /tmp/cc:/tmp/cc \
        codeclimate/codeclimate analyze

phpstan: | run
# Runs phpstan on the PHP code
	$(EXEC) vendor/bin/phpstan analyse -l 5 src
