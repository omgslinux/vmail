#!/bin/bash

cd /home/vmail/vmail
input=$(cat)
sender=$1
mailbox=$2
domain=${3##autoreply.}
bodysize=$(echo "$input"|wc -c)
logger -t autoreply "Sender: $sender Recipient: $mailbox@$domain, tamaño: $bodysize"
echo "$input"|bin/console vmail:autoreply $sender $mailbox@$domain
