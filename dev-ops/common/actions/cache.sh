#!/usr/bin/env bash
#DESCRIPTION: execute on app_webserver to provision your environment

shopware/bin/console sw:warm:http:cache
shopware/bin/console sw:cache:siege

