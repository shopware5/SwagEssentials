#!/usr/bin/env bash

tools/phpunit
      --stop-on-failure
      --stop-on-error
      --configuration phpunit.xml.dist
      --colors=never
