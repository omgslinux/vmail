#!/bin/bash

cp /tmp/1-*sh $HOME
# Script para descargar y configurar la aplicacion vmail

git clone https://github.com/omgslinux/vmail.git

cd vmail
echo 'APP_ENV=prod
DATABASE_URL="mysql://vmail:vmail@mysql:3306/vmail?serverVersion=11.8.3-MariaDB&charset=utf8mb4"'>>.env

composer install
bin/console asset-map:compile

bin/console doctrine:schema:update --force
bin/console vmail:setup
