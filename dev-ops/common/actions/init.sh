#!/usr/bin/env bash
#DESCRIPTION: execute on app_webserver to provision your environment

rm -rf ./shopware
cd tools && composer install

INCLUDE: ./.init_composer.sh
INCLUDE: ./.init_links.sh
INCLUDE: ./.init_config.sh
INCLUDE: ./.init_shopware.sh
INCLUDE: ./.init_plugins.sh
INCLUDE: ./.init_test_database.sh

shopware/bin/console sw:store:download SwagDemoDataDE
shopware/bin/console sw:plugin:install SwagDemoDataDE

I: SHOPWARE_ENV=dev shopware/bin/console sw:cache:clear
I: SHOPWARE_ENV=test shopware/bin/console sw:cache:clear
I: shopware/bin/console sw:cache:clear
