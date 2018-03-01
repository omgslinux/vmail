#!/bin/bash

cp /tmp/1-*sh $HOME
# Script para descargar y configurar la aplicacion vmail

git clone https://github.com/omgslinux/vmail.git

cd vmail
umask 002
composer install

bin/console doctrine:schema:update --force
bin/console vmail:setup

