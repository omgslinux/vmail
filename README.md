Vmail (Virtual domain and user management)
==========================================

Vmail tries to solve the problem about user management for virtual mail domains. You can manage lots of domains, and the users for each domain, along with specific domain users, password management and autoreply management for each user. Also, each user can login and change his/her password and manage an autoreply, specifying start datetime, end datetime, and the text to send as reply for the people who send mails while out of the office.

So, a first domain with id 0 (ideally called "default", is necessary). Then, you create a user and initially encode a chosen password, assigning 0 as domain_id for this user. Then, you can login with "user@default" and you get ROLE_ADMIN, with full privileges.

Then, you have to manage virtual domains. So, using the Admin -> Manage domains menu, you see the existing domains and can create one. So, you just choose the FQDN mail domain (i.e. 'example.org'), so you create users for the @example.org domain. Once you create this domain, you start creating users. If one of theses users has the "Admin" option checked, this becomes this user as manager of the domain (ROLE_MANAGER), so s/he can fully manage users ONLY for the domain s/he's in. Also, the ROLE_MANAGER allows to manage "virtual aliases", which means to have an address as a distribution list, together with the users whose mailbox will get a mail when a mail to that alias is sent.

Finally, each user without the admin option enabled can login (ROLE_USER), and will only have the change to manage his/her own password and the autoreply settings.
