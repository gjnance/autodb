#!/bin/bash -e
set -ex

sudo apt-get update -y
sudo apt-get install -y nginx php8.1-fpm php-mysql

echo "AutoDB cloud-init.yaml completed successfully" > /var/log/adb-cloud-init.log