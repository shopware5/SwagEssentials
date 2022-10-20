#!/usr/bin/env bash

# init shopware
I: rm -R vendor/shopware/shopware/var/cache/production*
I: rm -R vendor/shopware/shopware/var/cache/test*
I: rm -R vendor/shopware/shopware/var/cache/dev*

composer dumpautoload --optimize
shopware/bin/console sw:database:setup --steps=drop,create,import,importDemodata
shopware/bin/console sw:cache:clear
shopware/bin/console sw:database:setup --steps=setupShop --shop-url=http://__SW_HOST__
shopware/bin/console sw:snippets:to:db --include-plugins
shopware/bin/console sw:theme:initialize
shopware/bin/console sw:firstrunwizard:disable
shopware/bin/console sw:admin:create --name="Demo" --email="demo@demo.de" --username="demo" --password="demo" --locale=de_DE -n
cd shopware && touch recovery/install/data/install.lock

