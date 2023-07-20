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

# Setup webserver
echo "AutoDB: Configuring NGINX"
cp ~/autodb/src/nginx/autodb_nginx.conf /etc/nginx/sites-available/autodb
ln -s /etc/nginx/sites-available/autodb /etc/nginx/sites-enabled/
unlink /etc/nginx/sites-enabled/default
systemctl reload nginx

# Initialize MySQL Database and tables
mysql -h autodb-mysql-server.mysql.database.azure.com -u autodb_user -p'${mysql_password}' < ~/autodb/src/sql/autodb.sql

echo "AutoDB: cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log