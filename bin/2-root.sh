#!/bin/bash

NAME=vmail
GETENT=$(getent passwd $NAME)
USERHOME=$(echo "$GETENT"|cut -d: -f 6)
USERUID=$(echo "$GETENT"|cut -d: -f 3)
USERGID=$(echo "$GETENT"|cut -d: -f 4)
APPHOME=$USERHOME/$NAME
MAILBOXBASE="/var/lib/$NAME"

systemctl daemon-reload

# Configuracion de php-fpm para vmail socket
FPMSOCKET="/run/php/php7-$NAME-fpm.sock"
echo "
[$NAME]
user = $NAME
group = www-data

listen = $FPMSOCKET

listen.owner = $NAME
listen.group = www-data

pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
" > /etc/php/7.0/fpm/pool.d/$NAME.conf

service php7.0-fpm restart


# Configuracion de nginx

echo "
ssl on;

ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
ssl_prefer_server_ciphers on;
#ssl_dhparam /etc/nginx/ssl/dhparam.pem;
ssl_ciphers 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!EDH-RSA-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA';
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:50m;
add_header Strict-Transport-Security max-age=15768000;

" > /etc/nginx/ssl_params



echo "
server {
    listen 80;
    listen [::]:80;
    server_name $NAME;
    return 302 https://$NAME\$request_uri;
}




# Default server configuration
#
server {

        # SSL configuration
        #
        listen 443 ssl default_server;
        listen [::]:443 ssl default_server;
	ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
	ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;

        include /etc/nginx/ssl_params;

      server_name $NAME;

        root $APPHOME/web;

        # Add index.php to the list if you are using PHP
        index index.php app.php index.nginx-debian.html;

    location / {
        # try to serve file directly, fallback to app.php
        try_files \$uri /app.php\$is_args\$args;
    }

    # PROD
    location ~ ^/app\\.php(/|\$) {
        fastcgi_pass unix:$FPMSOCKET;
        fastcgi_split_path_info ^(.+\.php)(/.*)\$;
        include fastcgi_params;
        # When you are using symlinks to link the document root to the
        # current version of your application, you should pass the real
        # application path instead of the path to the symlink to PHP
        # FPM.
        # Otherwise, PHP's OPcache may not properly detect changes to
        # your PHP files (see https://github.com/zendtech/ZendOptimizerPlus/issues/126
        # for more information).
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        # Prevents URIs that include the front controller. This will 404:
        # http://domain.tld/app.php/some-path
        # Remove the internal directive to allow URIs like this
        internal;
    }

        # pass PHP scripts to FastCGI server
        #
        location ~ \\.php\$ {
                include snippets/fastcgi-php.conf;

                # With php-fpm (or other unix sockets):
                fastcgi_pass unix:$FPMSOCKET;
                # With php-cgi (or other tcp sockets):
        #       fastcgi_pass 127.0.0.1:9000;
        }

        # deny access to .htaccess files, if Apache's document root
        # concurs with nginx's one
        #
        location ~ /\\.ht {
                deny all;
        }
}
" > /etc/nginx/sites-available/$NAME.conf

rm /etc/nginx/sites-enabled/*
ln -s ../sites-available/$NAME.conf /etc/nginx/sites-enabled

service nginx restart




# Configuracion de courier

cd $APPHOME
bin/console vmail:conffiles:courier

sed 's/^authmodulelist=.*$/authmodulelist="authmysql"/' /etc/courier/authdaemonrc > /tmp/authdaemonrc
mv /tmp/authdaemonrc /etc/courier
service courier-authdaemon restart
service courier-imap restart
service courier-imap-ssl restart

# Configuracion de postfix
adduser postfix sasl
cd $APPHOME
mkdir /etc/postfix/vmail
bin/console vmail:conffiles:postfix

echo "
pwcheck_method: authdaemond
authdaemond_path: /var/run/courier/authdaemon/socket
mech_list: plain login
#log_level: 9
" > /etc/postfix/sasl/smtpd.conf

POSTCONF="
recipient_delimiter = +

content_filter = smtp-amavis:[127.0.0.1]:10024
inet_interfaces = all
alias_maps = hash:/etc/aliases, mysql:/etc/postfix/vmail/mysql-alias_maps.cf
smtpd_banner = $myhostname ESMTP $mail_name (Debian/GNU)
smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination, check_policy_service unix:private/spfcheck
smtpd_relay_restrictions = permit_mynetworks, permit_sasl_authenticated, defer_unauth_destination
smtpd_sasl_auth_enable = yes
smtpd_sasl_authenticated_header = yes
transport_maps = mysql:/etc/postfix/vmail/mysql-virtual_transport.cf, mysql:/etc/postfix/vmail/mysql-virtual_autoreply_transport.cf
virtual_alias_domains =
virtual_alias_maps = mysql:/etc/postfix/vmail/mysql-virtual_autoreply_mailboxes.cf, mysql:/etc/postfix/vmail/mysql-virtual_alias_maps.cf
virtual_gid_maps = static:$USERGID
virtual_mailbox_base = $MAILBOXBASE
virtual_mailbox_domains = mysql:/etc/postfix/vmail/mysql-virtual_mailbox_domains.cf, mysql:/etc/postfix/vmail/mysql-virtual_autoreply_domains.cf
virtual_mailbox_maps = mysql:/etc/postfix/vmail/mysql-virtual_mailbox_maps.cf
virtual_uid_maps = static:$USERUID
"

while read linea;do
if [ ! -z "$linea" ];then
   echo "Añadiendo $linea"
   postconf -e "$linea"
fi
done <<< $POSTCONF

MASTERCONF="
smtp       inet  n       -       n       -       -       smtpd
smtp       unix  -       -       y       -       -       smtp
submission inet  n       -       n       -       -       smtpd -v -o syslog_name=postfix/submission -o smtpd_tls_security_level=encrypt -o smtpd_sasl_auth_enable=yes -o smtpd_reject_unlisted_recipient=no -o smtpd_recipient_restrictions= -o smtpd_relay_restrictions=permit_sasl_authenticated,reject -o milter_macro_daemon_name=ORIGINATING
virtual    unix  -       n       n       -       -       virtual
smtp-amavis unix -       -       -       -       2       smtp -o smtp_data_done_timeout=1200 -o smtp_send_xforward_command=yes -o disable_dns_lookups=yes -o max_use=20
127.0.0.1:10025 inet n   -       -       -       -       smtpd -o content_filter= -o local_recipient_maps= -o relay_recipient_maps= -o smtpd_restriction_classes= -o smtpd_delay_reject=no -o smtpd_client_restrictions=permit_mynetworks,reject -o smtpd_helo_restrictions= -o smtpd_sender_restrictions= -o smtpd_recipient_restrictions=permit_mynetworks,reject -o smtpd_data_restrictions=reject_unauth_pipelining -o smtpd_end_of_data_restrictions= -o mynetworks=127.0.0.0/8 -o smtpd_error_sleep_time=0 -o smtpd_soft_error_limit=1001 -o smtpd_hard_error_limit=1000 -o smtpd_client_connection_count_limit=0 -o smtpd_client_connection_rate_limit=0 -o receive_override_options=no_header_body_checks,no_unknown_recipient_checks
autoreply  unix  -       n       n       -       -       pipe flags= user=vmail argv=$APPHOME/bin/autoreply.sh \$sender \$mailbox \$domain
"

while read linea;do
   if [ ! -z "$linea" ];then
      service=$(echo "$linea"|awk '{ print $1 }')
      type=$(echo "$linea"|awk '{ print $2 }')
      postconf -M $service/$type="$linea" 2>&1
      #if [[ $(postconf -e -P "$clean" 2>&1 ) ]];then
        #echo $(echo $clean|cut -d/ -f1) "   " $(echo $clean|cut -d/ -f2) >> /etc/postfix/master.cf
      #fi
   fi
done <<< $MASTERCONF

service postfix restart

# Configuracion de sasl
sed -e 's/START=no/START=yes/' /etc/default/saslauthd |
    sed -e 's/^MECHANISMS=.*$/MECHANISMS="rimap"/' - |
    sed -e 's/^MECH_OPTIONS=.*$/MECH_OPTIONS="127.0.0.1"/' - |
    sed -e 's@^OPTIONS=.*$@OPTIONS="-c -m /var/spool/postfix/var/run/saslauthd -r"@' >/tmp/sasl.sh
if [ -f /tmp/sasl.sh ];then
    mv /tmp/sasl.sh /etc/default/saslauthd
fi
service saslauthd restart

