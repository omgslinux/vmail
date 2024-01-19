<?php

namespace App\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;
use App\Entity\Config;
use App\Entity\Autoreply;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Mime\Email;

class DeliverMail
{
    private $mailer;
    private $config;

    public function __construct(MailerInterface $mailer, ReadConfig $config)
    {
        $this->mailer = $mailer;
        $this->config = $config;
    }

    public function manualDeliver($recipient, $body, $virtual_mailbox_base = null)
    {
        if (null==$virtual_mailbox_base) {
            $virtual_mailbox_base=$this->config->findParameter('virtual_mailbox_base');
        }

        $t=explode('@', $recipient);
        $domain=$t[1];
        $mailbox=$t[0];

        // Deliver the original text manually
        $homemailbox="$virtual_mailbox_base/$domain/$mailbox";
        $tmpdir="$homemailbox/tmp";
        $mytime=time();
        $mymicro=sprintf("%06d", rand(0, 1000000));
        $mypid=getmypid();
        $myhost=gethostname();
        $mytmpfile=$tmpdir . "/" . $mytime . ".M" . $mymicro . "P". $mypid . "." . $myhost;
        file_put_contents($mytmpfile, $body);
        $mystat=stat($mytmpfile);
        $mydev=$mystat['dev'];
        $myino=$mystat['ino'];
        $mysize=$mystat['size'];
        $mynewfile=$mytime . ".M" . $mymicro . "P". $mypid . "V". $mydev . "I". $myino . "." . $myhost . ",S=$mysize";

        rename($mytmpfile, "$homemailbox/new/" . $mynewfile);
    }

    public function sendMail($subject, $sender, $recipient, $body)
    {
        $email = (new Email())
        ->subject($subject)
        ->from($sender)
        ->to($recipient)
        ->text($body)
        ;
        $this->mailer->send($email);
    }
}
