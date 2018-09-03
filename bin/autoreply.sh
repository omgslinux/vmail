#!/bin/bash

#cd /home/vmail/vmail
cd $(dirname $0)
cd ..

input=$(cat)
bodyfile=~/tmp/vmail-$$.tmp
echo "$input" > $bodyfile
bodysize=$(cat $bodyfile|wc -c)
sender=$1
mailbox=$2
domain=${3##autoreply.}
logger -t autoreply "Sender: $sender Recipient: $mailbox@$domain, bodyfile: $bodyfile ($bodysize bytes)"
echo $bodyfile|bin/console vmail:autoreply $sender $mailbox@$domain
