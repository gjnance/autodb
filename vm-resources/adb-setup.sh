#!/bin/bash -ex
# Logs to /var/log/cloud-init-output.log
echo "AutoDB: Executing $(basename "$0") as $(whoami)"

NGINX_ROOT=/var/www
APP_DIR=$NGINX_ROOT/autodb

echo "AutoDB: Script environment"
env

echo "AutoDB: Installing required packages"
apt-get update -y
apt-get install -y nginx php8.1-fpm php-mysql

echo "AutoDB: Cloning git repository under ~"
git clone -b deploy-via-terraform https://github.com/gjnance/autodb.git ~/autodb

echo "AutoDB: Installing application files"
mkdir $APP_DIR
cp -Rp ~/autodb/src/www/* $APP_DIR

echo "AutoDB: Configuring NGINX"
cp ~/autodb/src/nginx/autodb_nginx.conf /etc/nginx/sites-available/autodb

ln -s /etc/nginx/sites-available/autodb /etc/nginx/sites-enabled/
unlink /etc/nginx/sites-enabled/default
systemctl reload nginx

echo "AutoDB: cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log