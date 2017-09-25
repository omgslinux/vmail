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

        $t=explode('@', $recipient);

        $em = $this->em;
        $domain=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository(User::class)->findOneBy(['domain' => $domain, 'name' => $t[0]]);
        $reply=$user->getReply();

        if (!empty($reply)) {
          $now=new \DateTime();
          if ($reply->isActive() && $now>$reply->getStartDate() && $now<$reply->getEndDate()) {
            $lastreply=$em->getRepository(AutoreplyCache::class)->findBy(['sender' => $sender], ['datesent' => 'DESC'], 1);
            $delay=$this->config->findParameter('autoreply_delay');
            if (empty($lastreply)) {
              $newcache=new \DateTime();
            } else {
              $newcache=$lastreply[0]->getDateSent();
              $newcache->modify('+'.$delay.' hour');
            }
            if ($newcache<=$now) {
              $this->sendReply($reply, $sender, $recipient, $body);
              $cache=new AutoreplyCache;
              $cache
              ->setReply($reply)
              ->setSender($sender)
              ->setRecipient($recipient);
              $em->persist($cache);
              $em->flush();
              $now->modify('+'.$delay.' hour');
              syslog(LOG_INFO, "Autoreply INFO (SENT): Sender: $sender, recipient: $recipient, next autoreply: ". $now->format('d/m/Y H:i:s'));
            } else {
              syslog(LOG_NOTICE, "Autoreply NOTICE (NOT SENT): Sender: $sender, recipient: $recipient, next sent: ". $newcache->format('d/m/Y H:i:s'));
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
