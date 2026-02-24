# Post install as root user

# 6 - OpenDKIM (optional)

apt install -y opendkim

# Generate the keys
cd /etc/dkimkeys
# Set DOMAIN if necessary
#DOMAIN="example.org"
mkdir ${DOMAIN:-example.org}
opendkim-genkey -s ${SELECTOR:-mail} -d ${DOMAIN:-example.org} -D ${DOMAIN:-example.org}
cat /etc/dkimkeys/${DOMAIN:-example.org}/${SELECTOR:-mail}.txt
chown -R opendkim:opendkim /etc/dkimkeys

# This is mandatory if not using TCP/IP socket in /etc/opendkim.conf
adduser postfix opendkim

cd /home/vmail/vmail

bin/console app:dovecot:conf
bin/console app:postfix:conf

service dovecot restart

service postfix restart


# 6 - Setup roundcube. Checkout specific version
RVERSION="1.6.12"
mkdir -p /var/www/vhosts
cd /var/www/vhosts

wget https://github.com/roundcube/roundcubemail/releases/download/${RVERSION}/roundcubemail-${RVERSION}-complete.tar.gz
tar zxf roundcubemail-${RVERSION}-complete.tar.gz
ln -s roundcubemail-${RVERSION} roundcube
chown -R www-data:www-data roundcubemail-${RVERSION}
pushd roundcube/public_html
ln -s ../installer
popd

# Add the php code inside RCONFIG at the end of the public_html/config/config.inc.php file

RCONFIG="
$config['imap_conn_options'] = $config['smtp_conn_options'] = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ],
];

// Set to false only when installation is finished
$config['enable_installer'] = false;
"
