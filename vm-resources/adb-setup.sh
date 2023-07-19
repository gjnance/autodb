#!/bin/bash -ex
# Logs to /var/log/cloud-init-output.log

NGINX_ROOT=/var/www
APP_DIR=$(NGINX_ROOT)/autodb

echo "AutoDB: Executing $(basename "$0") as $USER"

echo "AutoDB: Installing required packages"
sudo apt-get update -y
sudo apt-get install -y nginx php8.1-fpm php-mysql

echo "AutoDB: Cloning git repository under $HOME"
cd $HOME && git clone https://github.com/gjnance/autodb.git

echo "AutoDB: Installing application files"
sudo mkdir $APP_DIR
sudo cp -Rp $HOME/autodb/src/www/ $APP_DIR

echo "AutoDB: Configuring NGINX"
#TODO: Copy adb.conf 

echo "AutoDB: cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log