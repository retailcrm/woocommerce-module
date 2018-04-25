#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
    composer global require "phpunit/phpunit=6.*"
    composer install
fi