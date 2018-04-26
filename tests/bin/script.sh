#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
    composer install
    vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist
else
    phpunit -c phpunit.xml.dist
fi
