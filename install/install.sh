#!/bin/bash

cp ./ufw-docker-automated.service /etc/systemd/system/
mkdir -p /opt/ufw-docker-automated/Config

php ../Phar_Helper/CreatePhar.php

cp ../bin/ufw-docker-automated.phar /opt/ufw-docker-automated/ufw-docker-automated.phar
cp ../bin/Config/local.json /opt/ufw-docker-automated/Config/local.json
systemctl enable ufw-docker-automated
systemctl start ufw-docker-automated

echo "Done"
echo "setings file can be found in: /opt/ufw-docker-automated/Config/local.json"
echo "Add the following lines to the bottom your /etc/ufw/after.rules"
echo "-------------"
echo "$(cat after.rules)"
echo "-------------"
systemctl status ufw-docker-automated.service