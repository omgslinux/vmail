<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\Config;
use VmailBundle\Entity\Autoreply;
use Doctrine\ORM\EntityManager;

class DeliverMail
{
    public $mailer;

    public function __construct(\Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function manualDeliver($recipient, $body, $virtual_mailbox_base)
    {

        $t=explode('@', $recipient);
        $domain=$t[1];
        $mailbox=$t[0];

        // Deliver the original text manually
        $homemailbox="$virtual_mailbox_base/$domain/$mailbox";
        $tmpdir="$homemailbox/tmp";
        $mytime=time();
        $mymicro=printf("%.06d", rand(0, 1000000));
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
        $message = (new \Swift_Message())
        ->setSubject($subject)
        ->setFrom($sender)
        ->setTo($recipient)
        ->setBody($body)
        ;
        $this->mailer->send($message);
    }
}
