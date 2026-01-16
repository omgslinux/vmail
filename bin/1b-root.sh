# Post install as root user

# 6 - OpenDKIM (opcional)

apt install -y opendkim
# Generar la llave
mkdir -p /etc/opendkim/keys
chown -R opendkim:opendkim /etc/opendkim
opendkim-genkey -s ${SELECTOR:-default} -d ${DOMAIN:-example.org} -D /etc/opendkim/keys/
cat /etc/opendkim/keys/${SELECTOR:-default}.txt

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

# Add this to the end of the public_html/config/config.inc.php file
RCONFIG="
$config['imap_conn_options'] = $config['smtp_conn_options'] = [
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ],
];
"
