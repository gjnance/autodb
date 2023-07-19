#!/bin/bash

set -e
sudo apt update
sudo apt install -y nginx php8.1-fpm php-mysql

echo "vm-provision.sh completed successfully" >> /var/log/vm-provision.log