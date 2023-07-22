#!/bin/bash -ex
# Logs to /var/log/cloud-init-output.log
echo "AutoDB: Executing $(basename "$0") as $(whoami)"

NGINX_ROOT=/var/www
APP_DIR=$NGINX_ROOT/autodb

echo "AutoDB: Script environment"
env

# Install requirements
echo "AutoDB: Installing required packages"
apt-get update -y
apt-get install -y nginx php8.1-fpm php-mysql mysql-client

# Clone git repository and copy application files
echo "AutoDB: Cloning git repository"
git clone -b deploy-via-terraform https://github.com/gjnance/autodb.git ~/autodb

echo "AutoDB: Installing application files"
mkdir $APP_DIR
cp -Rp ~/autodb/src/www/* $APP_DIR

# Replace the placeholders with actual values, escaped to ensure that characters
# special to sed don't cause issues (& and \, specifically)
MYSQL_PASS_ESCAPED=$(echo "${MYSQL_PASS}" | sed 's/[&/\]/\\&/g')

sed -i "s/', 'MYSQL_HOST'/', '${MYSQL_HOST}'/g" $APP_DIR/adb_config.php
sed -i "s/', 'MYSQL_USER'/', '${MYSQL_USER}'/g" $APP_DIR/adb_config.php
sed -i "s/', 'MYSQL_PASS'/', '$MYSQL_PASS_ESCAPED'/g" $APP_DIR/adb_config.php

# Setup webserver
echo "AutoDB: Configuring NGINX"
cp ~/autodb/src/nginx/autodb_nginx.conf /etc/nginx/sites-available/autodb
ln -s /etc/nginx/sites-available/autodb /etc/nginx/sites-enabled/
unlink /etc/nginx/sites-enabled/default
systemctl reload nginx

# Initialize MySQL Database and tables
mysql -h ${MYSQL_HOST} -u autodb_user -p'${MYSQL_PASS}' < ~/autodb/src/sql/autodb.sql
mysql -h ${MYSQL_HOST} -u autodb_user -p'${MYSQL_PASS}' < ~/autodb/src/sql/demo.sql

echo "AutoDB: cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log