#!/bin/bash

if [ -d /var/www/html/osTicket ]; then
    sudo rm -rf /var/www/html/osTicket
fi
sudo mkdir -vp /var/www/html/osTicket

