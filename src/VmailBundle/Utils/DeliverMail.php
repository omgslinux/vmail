<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\Config;
use VmailBundle\Entity\Autoreply;
use VmailBundle\Utils\ReadConfig;
use Doctrine\ORM\EntityManager;

class DeliverMail implements ContainerInterface
{
    private $em;
    private $config;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
        $this->config=new ReadConfig($em);
    }

    public function deliverMail($sender, $recipient, $body)
    {

        $t=explode('@', $recipient);

        $domain=$t[1];
        $mailbox=$t[0];

        // Deliver the original text manually
        $syslog.=", original delivery";
        $mybase=$this->config->findParameter('virtual_mailbox_base');
        $homemailbox="$mybase/$domain/". $mailbox;
        $tmpdir="$homemailbox/tmp";
        $mytime=time();
        $mymicro=printf("%.06d", rand(0,1000000));
        $mypid=getmypid();
        $myhost=gethostname();
        $mytmpfile=$tmpdir . "/" . $mytime . ".M" . $mymicro . "P". $mypid . "." . $myhost;
        file_put_contents($mytmpfile,$body);
        $mystat=stat($mytmpfile);
        $mydev=$mystat['dev'];
        $myino=$mystat['ino'];
        $mysize=$mystat['size'];
        $mynewfile="$homemailbox/new/" . $mytime . ".M" . $mymicro . "P". $mypid . "V". $mydev . "I". $myino . "." . $myhost . ",S=" . $mysize;

        rename ($mytmpfile,$mynewfile);
    }

    public function sendReply(Autoreply $reply, $sender)
    {
        $subject=sprintf($this->config->findParameter('autoreply_subject'),$reply->getUser()->getEmail());
        $from='autoreply@' . $reply->getUser()->getDomainName();
        $body=$reply->getMessage();
        $message = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom($from)
          ->setTo($sender)
          ->setBody($body)
    ;
    $this->get('mailer')->send($message);



        $this->config = $this->em->getRepository('VmailBundle:Config')->findAll();
        return $this->config;
    }

}
