#!/usr/bin/env bash
#DESCRIPTION: activate master/slave replication for mysql

mysqldump -u root -proot -h 10.123.123.40 shopware --result-file=shopware.sql
mysql -u root -proot -h 10.123.123.41 -e "DROP DATABASE IF EXISTS shopware;"
mysql -u root -proot -h 10.123.123.41 -e "CREATE DATABASE shopware;"
mysql -u root -proot -h 10.123.123.41 shopware < shopware.sql
rm shopware.sql

mysql -u root -proot -h 10.123.123.41 -e "STOP SLAVE IO_THREAD;"
mysql -u root -proot -h 10.123.123.41 -e "CHANGE MASTER TO MASTER_HOST='10.123.123.40',MASTER_USER='root', MASTER_PASSWORD='root', MASTER_LOG_FILE='__DB_MASTER_LOGFILE__', MASTER_LOG_POS = __DB_MASTER_POSITION__;"
mysql -u root -proot -h 10.123.123.41 -e "START SLAVE;"
