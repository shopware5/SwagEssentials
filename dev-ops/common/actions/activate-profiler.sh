#!/usr/bin/env bash
#DESCRIPTION: activate frosh-profiler

rm -rf ./shopware/custom/plugins/FroshProfiler*

curl -k -L -o ./shopware/custom/plugins/FroshProfiler.zip https://github.com/FriendsOfShopware/FroshProfiler/releases/download/1.2.1/FroshProfiler-1.2.1.zip

cd ./shopware/custom/plugins && unzip FroshProfiler.zip

shopware/bin/console sw:plugin:refresh

shopware/bin/console sw:plugin:install FroshProfiler
shopware/bin/console sw:plugin:activate FroshProfiler