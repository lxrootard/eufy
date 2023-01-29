#!/bin/bash
#set -x
touch /tmp/dependancy_eufy_in_progress
echo 0 > /tmp/dependancy_eufy_in_progress
echo "Launch install of eufy dependancy"
#sudo apt-get update
echo 50 > /tmp/dependancy_eufy_in_progress
sudo apt-get install -y python3-pip
echo 66 > /tmp/dependancy_eufy_in_progress
echo 75 > /tmp/dependancy_eufy_in_progress
sudo pip3 install websocket-client
echo 80 > /tmp/dependancy_eufy_in_progress
echo 95 > /tmp/dependancy_eufy_in_progress
cd /tmp
#sudo rm -R /tmp/eufy-security-client
#sudo git clone https://github.com/bropat/eufy-security-client
#sudo rm -R /tmp/eufy-security-client
echo 100 > /tmp/dependancy_eufy_in_progress
echo "Everything is successfully installed!"
rm /tmp/dependancy_eufy_in_progress
