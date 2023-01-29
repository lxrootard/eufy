#!/bin/bash
#set -x
usage()
{
 echo "usage: "`basename $0`" install|uninstall|status|test|stop|start <device> <login> <passwd> [ port ]" 
}
port=3000
options=''
options2=' --restart=unless-stopped'
pstype=`uname -m|grep arm`
dir=`dirname $0`

action=$1
[ "$action" == "" ] && usage && exit 1;

echo '*** Eufy service' $action '***'
#echo 'parameters: ' $*
test ! -f /usr/bin/docker && echo "Please install docker first" && exit 1;
container_id=`sudo docker ps -a | grep eufy-security-ws|awk '{ print $1 }'`
image=`sudo docker images |grep 'bropat/eufy-security-ws'`

if [ "$action" == "test" ]
then
	test ! -f /usr/bin/python3 && echo "Please install python3 first" && exit 1;
        sudo python3 $dir/test_eufy.py 2>/dev/null
	rc=$?
	[ "$rc" -ne 0 ] && echo "Failed to connect to container" && exit 1;
fi

if [ "$action" == "status" ]
then
	echo "image:" $image
	[ "$container_id" == "" ] && container_id='not running' ;
	echo "container:" $container_id
fi
if [ "$action" == "stop" ]
then
   if [ "$container_id" != "" ]
   then
	echo 'removing container ' $container_id 
        sudo docker stop $container_id
        sudo docker rm -f $container_id
   fi
fi
if [ "$action" == "uninstall" ]
then
  if [ "$container_id" != "" ]
  then
	echo 'removing container ' $container_id
	$0 stop
	sudo docker stop $container_id
	sudo docker rm -f $container_id
  fi
  $0 stop
  echo 'removing image'
  if [ "$image" != "" ]
  then
     sudo docker rmi -f $image
  fi
fi
if [ "$action" == "install" ]
then
	[ "$container_id" != "" ] && echo "please uninstall eufy container first" && exit 1;
	[ "$image" != "" ] && echo "please uninstall eufy image first" && exit 1;
	echo 'installing bropat/eufy-security-ws'
	sudo docker pull bropat/eufy-security-ws
fi
if [ "$action" == "start" ]
then
	[ $# -lt 4 ] && usage && exit 1;
	EUFY_DEVICE=$2
	EUFY_EMAIL=$3
	EUFY_PASSWD=$4
	[ "$container_id" != "" ] && echo "Container already started: " $container_id && exit 1;
	[ "$pstype" != "" ] && options=$options' --privileged ' ;
	options='-d '$options
	[ "$5" != "" ] && port=$5 ;

        echo 'starting container, options=' $options 
	set -x
        sudo docker run $options --name Eufy-WS \
                -e TRUSTED_DEVICE_NAME="$EUFY_DEVICE" -e USERNAME="$EUFY_EMAIL" -e PASSWORD="$EUFY_PASSWD" \
                -e COUNTRY=FR -e LANGUAGE=fr -p $port:3000 $options2 bropat/eufy-security-ws
fi
echo '*** ' $action 'terminated OK ***'
exit 0
#pour faire le ménage si nécessaire:
#sudo docker image prune -a
