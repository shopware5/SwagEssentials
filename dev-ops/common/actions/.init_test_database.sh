#!/usr/bin/env bash

I: mysql -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ -e "DROP DATABASE \`__DB_NAME__-test\`"
mysql -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ -e "CREATE DATABASE \`__DB_NAME__-test\` DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_unicode_ci"
mysqldump __DB_NAME__ -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ | mysql __DB_NAME__-test -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__

# deactivate sendOrderMail for test environment
mysql __DB_NAME__-test -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ -e "REPLACE INTO s_core_config_values SELECT NULL, scce.id, 1 AS shopId, 'b:0;' FROM s_core_config_elements scce WHERE name='sendOrderMail';"

mysql __DB_NAME__-test -u "__DB_USER__" -p"__DB_PASSWORD__" -h "__DB_HOST__" -e "UPDATE s_core_plugins SET active = 0 WHERE name = 'ErrorHandler'"

mysql __DB_NAME__-test -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ -e "UPDATE s_core_shops SET base_path = NULL"
mysql __DB_NAME__-test -u __DB_USER__ -p__DB_PASSWORD__ -h __DB_HOST__ -e "UPDATE s_core_shops SET base_url = NULL"
I: mysql __DB_NAME__ -u "__DB_USER__" -p"__DB_PASSWORD__" -h "__DB_HOST__" < dev-ops/common/fixtures.sql
