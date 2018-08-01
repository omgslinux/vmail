#!/bin/bash

# Script para automatizar la creacion de un contenedor lxc para vmail con debian

DISTRO=debian
RELEASE=stretch
NAME=vmail
DESTROY="1"
if [ $DESTROY ];then
  lxc-stop -n $NAME
  lxc-destroy -n $NAME
fi
lxc-create -n $NAME -t $DISTRO -- -r $RELEASE
lxc-start -n $NAME
echo "Iniciando contenedor $NAME ..."
sleep 5
LXCROOT="/var/lib/lxc/$NAME/rootfs"
cp [0-9]*.sh $LXCROOT/tmp
chmod a+x $LXCROOT/tmp/*.sh
echo "$NAME.lxc.localnet" > $LXCROOT/etc/hostname
lxc-attach -n $NAME -- /tmp/0-root_install.sh
lxc-attach -n $NAME -- su - vmail -c "/tmp/1-user.sh"
lxc-attach -n $NAME -- /tmp/2-root.sh

