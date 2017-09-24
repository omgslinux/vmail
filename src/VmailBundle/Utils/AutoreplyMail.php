<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\Config;
use VmailBundle\Entity\Autoreply;
use VmailBundle\Utils\ReadConfig;
use Doctrine\ORM\EntityManager;

class AutoreplyMail
{
    private $em;
    private $config;
    private $deliver;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
    }

    public function deliverReply($sender, $recipient, $body)
    {
        $virtual_mailbox_base=$this->config->findParameter('virtual_mailbox_base');
        $deliver->manualDeliver($sender, $recipient, $body, $virtual_mailbox_base);

        //$body=file_get_contents('php://STDIN');
        $t=explode('@', $recipient);

        $em = $this->em;
        $domain=$em->getRepository('VmailBundle:Domain')->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository('VmailBundle:User')->findOneBy(['domain' => $domain, 'user' => $t[0]]);
        //$reply=$em->getRepository('VmailBundle:Autoreply')->findOneBy(['user' => $user, 'active' => true]);
        $reply=$user->getReply();

        $date=new \DateTime();
        if (!empty($reply)) {
          if ($reply->isActive() && $date>$reply->getStartDate() && $date<$reply->getEndDate()) {
            $lastcache=$em->getRepository('VmailBundle:AutoreplyCache')->findBy(['user' => $user], ['order' => 'DESC'], 1);
            $delay=$this->config->findParameter('autoreply_delay');
            $lastcache->modify('+'.$delay.' h');
            if ($lastcache->format('Y-m-d H:i:s')>$date) {
              $this->sendReply($reply, $sender, $recipient, $body);
              $cache=new AutoreplyCache;
              $cache
              ->setReply($reply)
              ->setSender($sender)
              ->setRecipient($recipient);
              $em->persist($cache);
              $em->flush();
            }
          }
        }
    }

    public function sendReply(Autoreply $reply, $sender)
    {
        $subject=sprintf($this->config->findParameter('autoreply_subject'),$reply->getUser()->getEmail());
        $from='autoreply@' . $reply->getUser()->getDomainName();
        $body=$reply->getMessage();
        $deliver->sendMail($subject,$from,$sender,$body);
    }

}
