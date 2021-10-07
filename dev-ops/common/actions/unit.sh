#!/usr/bin/env bash

composer dump-autoload --dev
tools/phpunit
      --stop-on-failure
      --stop-on-error
      --configuration phpunit.xml.dist
      --colors=never