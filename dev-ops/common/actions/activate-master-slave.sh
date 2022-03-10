#!/usr/bin/env bash
#DESCRIPTION: activate master/slave replication for mysql

mysqldump -u root -proot -h mysql_master shopware --result-file=shopware.sql
mysqldump -u root -proot -h mysql_master shopware-test --result-file=shopware_test.sql
mysql -u root -proot -h mysql_slave -e 'DROP DATABASE IF EXISTS shopware;'
mysql -u root -proot -h mysql_slave -e 'DROP DATABASE IF EXISTS `shopware-test`;'
mysql -u root -proot -h mysql_slave -e 'CREATE DATABASE shopware;'
mysql -u root -proot -h mysql_slave -e 'CREATE DATABASE `shopware-test`;'
mysql -u root -proot -h mysql_slave shopware < shopware.sql
mysql -u root -proot -h mysql_slave shopware-test < shopware_test.sql
rm shopware.sql
rm shopware_test.sql

mysql -u root -proot -h mysql_slave -e 'STOP SLAVE IO_THREAD;'
mysql -u root -proot -h mysql_slave -e "CHANGE MASTER TO MASTER_HOST='mysql_master', MASTER_USER='root', MASTER_PASSWORD='root', MASTER_LOG_FILE='__DB_MASTER_LOGFILE__', MASTER_LOG_POS = __DB_MASTER_POSITION__;"
mysql -u root -proot -h mysql_slave -e 'STOP SLAVE;'
mysql -u root -proot -h mysql_slave -e 'RESET SLAVE;'
mysql -u root -proot -h mysql_slave -e 'START SLAVE;'
