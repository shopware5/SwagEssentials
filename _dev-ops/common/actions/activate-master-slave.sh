#!/usr/bin/env bash
#DESCRIPTION: activate master/slave replication for mysql WARNING every mysql data will be removed

INCLUDE: ../../docker/actions/stop.sh

I: sudo rm -rvf ./_dev-ops/docker/_volumes/app-mysql-*

INCLUDE: ../../docker/actions/start.sh

echo "Stalling for Mysql"
while ! nc -z 10.123.123.40 3306; do sleep 1; done

mysql -u app -papp -h 10.123.123.40 -e "DROP DATABASE IF EXISTS shopware;"
mysql -u app -papp -h 10.123.123.40 -e "CREATE DATABASE shopware;"

mysql -u app -papp -h 10.123.123.41 -e "DROP DATABASE IF EXISTS shopware;"
mysql -u app -papp -h 10.123.123.41 -e "CREATE DATABASE shopware;"

mysql -u app -papp -h 10.123.123.41 < ./_dev-ops/docker/containers/mysql-slave/master-slave.sql