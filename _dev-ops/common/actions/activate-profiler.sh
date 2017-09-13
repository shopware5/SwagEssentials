#!/usr/bin/env bash
#DESCRIPTION: activate shyim-profiler

rm -rf ./shopware/custom/plugins/ShyimProfiler*

curl -k -L -o ./shopware/custom/plugins/ShyimProfiler.zip https://github.com/shyim/shopware-profiler/releases/download/1.1.3/ShyimProfiler-1.1.3.zip

cd ./shopware/custom/plugins && unzip ShyimProfiler.zip

shopware/bin/console sw:plugin:refresh

shopware/bin/console sw:plugin:install ShyimProfiler
shopware/bin/console sw:plugin:activate ShyimProfiler