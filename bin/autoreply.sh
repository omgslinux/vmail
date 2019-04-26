#!/bin/bash

#cd /home/vmail/vmail
cd $(dirname $0)
cd ..

input=$(cat)
bodyfile=~/tmp/vmail$$.tmp
echo "$input" > $bodyfile
bodysize=$(cat $bodyfile|wc -c)
sender=$1

## Apaño mientras no se soluciona la query
PARAMS="$@"
NUMPARAMS="$#"
recipients=$(( NUMPARAMS / 2 ))
dparam=$(( recipients + 1 ))
contador=0
logger -t autoreply "Todo: $NUMPARAMS: ($PARAMS) Sender: $sender Destinatarios: $recipients, bodyfile: $bodyfile ($bodysize bytes)"
while [[ $(echo $1|grep -v 'autoreply.') ]]
do
    contador=$(( contador + 1 ))
    mailbox=$1
    domain=${!dparam##autoreply.}
    logger -t autoreply "($contador de $recipients) Recipient: $mailbox@$domain($dparam)"
    #echo "bin/console vmail:autoreply $sender $mailbox@$domain"
    echo $bodyfile|bin/console vmail:autoreply $sender $mailbox@$domain
    shift
done
## Fin de apaño mientras no se soluciona la query

#echo $bodyfile|bin/console vmail:autoreply $sender $mailbox@$domain
