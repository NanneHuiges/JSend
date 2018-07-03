#!/bin/bash

if [ ! -f .user.env ]; then
        echo "USER_ID=$(id -u)" > .user.env
        echo "GROUP_ID=$(id -g)" >> .user.env
fi

./bin/shell "composer install"
