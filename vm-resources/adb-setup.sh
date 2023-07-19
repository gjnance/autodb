#!/bin/bash -ex
# Logs to /var/log/cloud-init-output.log
echo "AutoDB: Executing $(basename "$0") as $(whoami)"

NGINX_ROOT=/var/www
APP_DIR=$NGINX_ROOT/autodb

echo "AutoDB: Script environment"
env

echo "AutoDB: Installing required packages"
sudo apt-get update -y
sudo apt-get install -y nginx php8.1-fpm php-mysql

echo "AutoDB: Cloning git repository under $HOME"
cd $HOME && git clone -b deploy-via-terraform https://github.com/gjnance/autodb.git

echo "AutoDB: Installing application files"
sudo mkdir $APP_DIR
sudo cp -Rp $HOME/autodb/src/www $APP_DIR/

echo "AutoDB: Configuring NGINX"
sudo cp $HOME/autodb/src/nginx/autodb_nginx.conf /etc/nginx/sites-available/autodb

sudo ln -s /etc/nginx/sites-available/autodb /etc/nginx/sites-enabled/
sudo unlink /etc/nginx/sites-enabled/default
sudo systemctl reload nginx

echo "AutoDB: cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log