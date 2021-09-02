#!/usr/bin/env bash

# monkey patch shopware vendors directory
I: find vendor/shopware/shopware/vendor/ -type l -exec rm {} \;
ls vendor/ | while read file; do ln -s ../../../../vendor/$file vendor/shopware/shopware/vendor/$file; done
rm -R vendor/shopware/shopware/vendor/shopware

I: rm shopware
ln -s vendor/shopware/shopware shopware
