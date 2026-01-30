# Post install as root user

# 6 - OpenDKIM (opcional)

apt install -y opendkim

# Generar la llave
cd /etc/dkimkeys
mkdir ${DOMAIN:-example.org}
opendkim-genkey -s ${SELECTOR:-mail} -d ${DOMAIN:-example.org} -D ${DOMAIN:-example.org}
cat /etc/dkimkeys/${DOMAIN:-example.org}/${SELECTOR:-mail}.txt
chown -R opendkim:opendkim /etc/dkimkeys

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

# Add this at the end of the public_html/config/config.inc.php file
RCONFIG="
$config['imap_conn_options'] = $config['smtp_conn_options'] = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ],
];

$config['enable_installer'] = false;
"
