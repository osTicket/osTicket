#!/bin/bash

if [ -d /var/www/html/osTicket/setup ]; then
    sudo rm -rf /var/www/html/osTicket/setup
fi

aws s3 cp s3://codepipeline-ap-south-1-821620239570/config-files/osTicket-stage/ost-config.php /var/www/html/osTicket/include/

