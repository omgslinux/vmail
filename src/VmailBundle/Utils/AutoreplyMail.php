<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\AutoreplyCache;
use VmailBundle\Entity\Autoreply;
use VmailBundle\Entity\User;
use VmailBundle\Entity\Domain;
use Doctrine\ORM\EntityManager;

class AutoreplyMail
{
    private $em;
    public $config;
    public $deliver;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
    }

    public function deliverReply($sender, $recipient, $body)
    {
        $virtual_mailbox_base=$this->config->findParameter('virtual_mailbox_base');
        $this->deliver->manualDeliver($sender, $recipient, $body, $virtual_mailbox_base);

        //$body=file_get_contents('php://STDIN');
        $t=explode('@', $recipient);

        $em = $this->em;
        $domain=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        //dump($domain);
        $user=$em->getRepository(User::class)->findOneBy(['domain' => $domain, 'name' => $t[0]]);
        //$reply=$em->getRepository(Autoreply::class)->findOneBy(['user' => $user, 'active' => true]);
        $reply=$user->getReply();

        $now=new \DateTime();
        if (!empty($reply)) {
          if ($reply->isActive() && $now>$reply->getStartDate() && $now<$reply->getEndDate()) {
            $lastreply=$em->getRepository(AutoreplyCache::class)->findBy(['sender' => $sender], ['datesent' => 'DESC'], 1);
            $delay=$this->config->findParameter('autoreply_delay');
            if (empty($lastreply)) {
              $newcache=new \DateTime();
              $newcache->modify('-'. $delay+1 .' hour');
            } else {
              $newcache=$lastreply[0]->getDateSent();
              $newcache->modify('+'.$delay.' hour');
            }
            if ($newcache<$now) {
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
        $this->deliver->sendMail($subject,$from,$sender,$body);
    }

}
