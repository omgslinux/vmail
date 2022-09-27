Vmail (Virtual domain and user management)
==========================================


Overview
========

Vmail tries to solve the problem about user management for virtual mail domains. You can manage lots of domains, and the users for each domain, along with specific domain users, password management and autoreply management for each user. Also, each user can login and change his/her password and manage an autoreply, specifying start datetime, end datetime, and the text to send as reply for the people who send mails while out of the office.

So, a first domain with id 0 (ideally called "default", is necessary). Then, you create a user and initially encode a chosen password, assigning 0 as domain_id for this user. Then, you can login with "user@default" and you get ROLE_ADMIN, with full privileges.

Then, you have to manage virtual domains. So, using the Admin -> Manage domains menu, you see the existing domains and can create one. So, you just choose the FQDN mail domain (i.e. 'example.org'), so you create users for the @example.org domain. Once you create this domain, you start creating users. If one of theses users has the "Admin" option checked, this becomes this user as manager of the domain (ROLE_MANAGER), so s/he can fully manage users ONLY for the domain s/he's in. Also, the ROLE_MANAGER allows to manage "virtual aliases", which means to have an address as a distribution list, together with the users whose mailbox will get a mail when a mail to that alias is sent.

From the user and domain point of view, the 'active' checkbox just means 'Should postfix deliver to this domain or user?'.

If you want to create "global" aliases (i.e., anything defined in $mydestination) you have to do it with the global admin user, and there you can choose any user of any domain to be part of any alias.

Finally, each user without the admin option enabled can login (ROLE_USER), and will only have the chance to manage his/her own password and autoreply settings.


IMPORTANT: Be aware than you need to send a mail to a user for mailbox creation. Without this, IMAP access might fail.



Installation
============

Prerequisites (as root):
=======================
- Create an unprivileged system user called 'vmail', member of 'vmail', having both uid and gid 5000.
  # useradd -u 5000 -U -m vmail
- Create /var/lib/vmail, and set vmail:vmail as owner and 770 as permissions.
  # mkdir -p /var/lib/vmail
  # chown vmail:vmail /var/lib/vmail
  # chmod 770 /var/lib/vmail
- Install courier-imap-ssl (in /etc/courier) and setup everything but authmysqlrc. IMPORTANT: for SHA512 (the default), you may need to edit /etc/courier/imapd and add in the line IMAP_CAPABILITY an extra "AUTH=CRAM-SHA512" at the end of the line.
- Install other mail related stuff like gamin, amavis, spamassassing and postfix.
- Setup everything about postfix but the virtual stuff. Create /etc/postfix/vmail directory.
- Install the common stuff for symfony: php, php-mysql, php-xml... and the web server of your choice. The web server should be able to write to /var/lib/vmail and ~/vmail/var/log/ and ~/vmail/var/cache, so setting vmail group membership for the webserver user is not a bad option.


Vmail deploy (as vmail)
=======================
- Create a vmail dir in the home directory. Clone to this dir, so ~/vmail contains composer.lock.
- After cloning the app from the github repo, install the application with composer (composer install) as usual, trying not to be root (by the moment). Make sure you configure the database and create the schema.
- To initialize the db, run the console command vmail:setup. This will create the default domain, user and password, whose credentials will be shown on the screen.
- Clear the cache for the prod environment (./bin/console cache:clear --env=prod). You may have to repeat this step later.

Post installation (as root)
===========================
- To deploy the file for courier, from the console (./bin/console), issue vmail:conffiles:courier, which will process the necessary template files into /etc/courier and overwrite /etc/courier/authmysqlrc. Courier setup should work with the new authmysqlrc.
- To deploy the files for postfix, from the console, issue vmail:conffiles:postfix, which will process the necessary template files into /etc/postfix/vmail.
- From templates/conffiles/postfix, copy the sasl directory and the _main.cf.twig file into /etc/postfix/.
- From /etc/postfix, add the contents of _main.cf.twig to main.cf. Then, review main.cf and just make sure the uid and gid are properly setup, together with virtual_mailbox_base, replacing the string inside curly braces and removing them. Also, check any possible conflict with the merged lines and set priority to these (alias_maps). WARNING: If for any reason you chose other user and uid/gid, set it all properly in /etc/postfix/main.cf and /etc/courier/authmysqlrc
- In /etc/postfix/sasl, rename the copied file to smtpd.conf, unless you're planning anything you're sure about.
- In /etc/postfix/master.conf, add a line if you want to use the autoreply feature:
autoreply  unix  -       n       n       -       -       pipe flags= user=vmail
    argv=/home/vmail/vmail/bin/autoreply.sh $sender $mailbox $domain
- In /etc/postfix/master.conf, the smtpd line, in the chroot column, must be "n". If you want to run postfix chrooted, you'll have to adjust the paths or copy the files to the chrooted location instead.
- Set the webserver docroot pointing to ~/vmail/web (~ is vmail $HOME). The webserver should be able to rewrite the addresses. There's a file for an apache vhost. This requires libapache2-mod-fcgid and php7.0-cgi.


Start the webserver, go to the address you've setup, and then log in the browser with the previously provided credentials.

Enjoy!
