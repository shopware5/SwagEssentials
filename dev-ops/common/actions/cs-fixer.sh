#!/usr/bin/env bash
#DESCRIPTION: execute on app_webserver to provision your environment

cd tools && ./php-cs-fixer fix --config ../.php_cs

