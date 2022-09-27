#!/bin/bash

# Script para ejecutar como root para instalacion de paquetes adicionales y otras acciones
HOSTNAME=$(cat /etc/hostname)
SHORT=$(cat /etc/hostname|cut -d. -f1)
hostname -F /etc/hostname
IFACE=$(grep "^iface eth" /etc/network/interfaces|awk '{ print $2 }'|head -n1)
IP=$(ip r l|grep "dev $IFACE proto"|awk '{ print $9 }')
LINE="$IP    $HOSTNAME $SHORT"
rm /tmp/hosts 2>/dev/null
if [[ $(grep -w ^$IP /etc/hosts) ]];then
   sed "s/^$IP.*$/$LINE/" /etc/hosts > /tmp/hosts
   mv /tmp/hosts /etc
else
   echo "$LINE" >> /etc/hosts
fi

rm /etc/localtime
ln -s /etc/localtime /usr/share/zoneinfo/Europe/Madrid
dpkg-reconfigure -f noninteractive tzdata
locale-gen es_ES.UTF-8
export LC_ALL="es_ES.UTF-8"
update-locale LC_ALL=es_ES.UTF-8 LANG=es_ES.UTF-8 LC_MESSAGES=POSIX
apt update
apt -y upgrade
BASIC="wget git bash-completion ca-certificates vim.tiny iputils-ping"
# Descomentar para servidor local
#SQLSERVER="mariadb-server"
MAIL="postfix-mysql courier-imap-ssl courier-authlib-mysql sasl2-bin $SQLSERVER"
ANTISPAM="amavisd-new clamav spamassassin"
PHP="php-cli php-xml php-mysql php-zip"
CERTBOT="python-certbot-nginx"
WEBSERVER="libapache2-mod-php"
WEBSERVER="nginx-light php-fpm"
apt install -y $BASIC $CERTBOT $MAIL $ANTISPAM $PHP $WEBSERVER


# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
#php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"


apt-get clean

useradd -u 5000 -d /home/vmail -m -g www-data -s /bin/bash vmail
mkdir -p /var/lib/vmail
chown vmail:www-data /var/lib/vmail

SQLCOMMANDS=<<<END
    mysql -e "create database vmail"
    mysql -e "grant all on vmail.* to 'vmail'@'$HOSTNAME' identified by 'vmail';"
END
if [[ $SQLSERVER ]];then
    eval $SQLCOMMANDS
else
    echo "Don't forget to create the vmail DB and grant"
    echo "$SQLCOMMANDS"
fi
