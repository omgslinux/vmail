#!/bin/bash

cd /home/vmail/vmail
input=$(cat)
sender=$1
mailbox=$2
domain=${3##autoreply.}
echo "$input"|bin/console vmail:autoreply $sender $mailbox@$domain
