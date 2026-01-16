#!/bin/bash

# 0 - Basic environment setup. You can skip and go to next step
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

apt update
rm /etc/localtime
export DEBIAN_FRONTEND=noninteractive
ln -s /usr/share/zoneinfo/Europe/Madrid /etc/localtime
dpkg-reconfigure tzdata
unset LC_ALL
export LANG="C"
apt install -y locales
sed -i 's/# es_ES.UTF-8 UTF-8/es_ES.UTF-8 UTF-8/' /etc/locale.gen
locale-gen es_ES.UTF-8
update-locale LANG=es_ES.UTF-8 LC_MESSAGES=POSIX LC_ALL=es_ES.UTF-8
echo 'LANG=es_ES.UTF-8' > /etc/default/locale

source /etc/default/locale
echo "vmail.exmaple.org">/etc/mailname


# 1 - Packages installation. SQL database packages not included.
BASIC="wget git bash-completion ca-certificates vim-tiny iputils-ping"
IMAP="dovecot-core dovecot-mysql dovecot-imapd dovecot-sieve dovecot-lmtpd dovecot-managesieved $SQLSERVER"
SMTP="postfix-mysql"
ANTISPAM="amavisd-new"
PHP="php-cli php-xml php-mysql php-zip php-mbstring php-intl"
#CERTBOT="python-certbot-nginx"
WEBSERVER="nginx php-fpm"
apt install -y $BASIC $CERTBOT $IMAP $SMTP $ANTISPAM $PHP $WEBSERVER
apt -y upgrade

# 2 - Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"


# 3 - Create vmail user and basic dirs
apt clean

useradd -u 5000 -m -s /bin/bash vmail
adduser www-data vmail
mkdir -p /var/lib/vmail
chown vmail:vmail /var/lib/vmail


# 4 - Setup nginx and vhosts
cd /etc/nginx
rm sites-enabled/default
git clone https://github.com/omgslinux/nginx-vhost-generator vhost-generator
cd vhost-generator
cp defaults.inc_dist defaults.inc
# Setup defaults.inc for your domain SUFFIX, etc.

mkdir vmail

cat > vmail/vmail.inc <<EOF
VHOST_TYPE="symfony"
SERVER="vmail"
DOCROOT="/home/vmail/\$SERVER/public"
HTTP_PORT="80"
HTTP_ENV="prod"
#HTTPS_PORT="8080"
#HTTPS_ENV="prod"
unset SSL_CERTIFICATE
SSLCLIENT_FASTCGI=""
FASTCGI_PASS="unix:/run/php/vmail-fpm.sock"
EOF

mkdir roundcube
cat > roundcube/roundcube.inc <<EOF
VHOST_TYPE="php"
SERVER="roundcube"
DOCROOT="/var/www/vhosts/\$SERVER/public_html"
HTTP_PORT="80"
unset SSL_CERTIFICATE
SSLCLIENT_FASTCGI=""
EOF

./mkvhost.sh vmail roundcube
nginx -t && service nginx restart


# 5 - Setup extra php-fpm
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")

cp /etc/php/$PHP_VER/fpm/pool.d/www.conf /etc/php/$PHP_VER/fpm/pool.d/vmail.conf

sed -i 's/\[www\]/\[vmail\]/' /etc/php/$PHP_VER/fpm/pool.d/vmail.conf
sed -i 's/www-data/vmail/g' /etc/php/$PHP_VER/fpm/pool.d/vmail.conf
sed -i "s|^listen =.*|listen = /run/php/vmail-fpm.sock|" /etc/php/$PHP_VER/fpm/pool.d/vmail.conf
systemctl restart php$PHP_VER-fpm

echo "192.168.12.100 mysql">>/etc/hosts

cd ~

echo "PLEASE INSTALL mariadb-server AND/OR CONFIGURE ACCESS FOR VMAIL AND ROUNDCUBE APPS"


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
