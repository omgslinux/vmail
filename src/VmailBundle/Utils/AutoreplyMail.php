<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\AutoreplyCache;
use VmailBundle\Entity\Autoreply;
use VmailBundle\Entity\User;
use VmailBundle\Entity\Domain;
use VmailBundle\Utils\ReadConfig;
use VmailBundle\Utils\DeliverMail;
use Doctrine\ORM\EntityManagerInterface;

class AutoreplyMail
{
    private $EM;
    private $deliver;
    private $config;

    public function __construct(EntityManagerInterface $em, DeliverMail $deliver, ReadConfig $config)
    {
        $this->EM = $em;
        $this->deliver = $deliver;
        $this->config = $config;
    }

    public function deliverReply($sender, $recipient, $body)
    {
        $this->deliver->manualDeliver($recipient, $body);

        $t=explode('@', $recipient);

        $em = $this->EM;
        $domain=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository(User::class)->findOneBy(['domain' => $domain, 'name' => $t[0]]);

        if ($reply=$user->getReply()) {
            $now=new \DateTime();
            if ($reply->isActive() && $now>$reply->getStartDate() && $now<$reply->getEndDate()) {
                $lastreply=$em->getRepository(AutoreplyCache::class)->findBy(
                    [
                        'recipient' => $recipient,
                        'sender' => $sender
                    ],
                    ['datesent' => 'DESC'],
                    1
                );
                $delay=$this->config->findParameter('autoreply_delay');
                if (empty($lastreply)) {
                    $newcache=new \DateTime();
                } else {
                    $newcache=$lastreply[0]->getDateSent();
                    $newcache->modify('+'.$delay.' hour');
                }
                if ($newcache<=$now) {
                    $this->sendReply($reply, $sender);
                    $cache=new AutoreplyCache();
                    $cache
                    ->setReply($reply)
                    ->setSender($sender)
                    ->setRecipient($recipient);
                    $em->persist($cache);
                    $em->flush();
                    $now->modify('+'.$delay.' hour');
                    syslog(
                        LOG_INFO,
                        "Autoreply INFO (SENT): Sender: $sender, recipient: $recipient, next autoreply: ".
                            $now->format('d/m/Y H:i:s')
                    );
                } else {
                    syslog(
                        LOG_NOTICE,
                        "Autoreply NOTICE (NOT SENT): Sender: $sender, recipient: $recipient, next sent: ".
                        $newcache->format('d/m/Y H:i:s')
                    );
                }
            }
        }
    }

    public function sendReply(Autoreply $reply, $sender)
    {
        $subject=sprintf($this->config->findParameter('autoreply_subject'), $reply->getUser()->getEmail());
        $from='autoreply@' . $reply->getUser()->getDomainName();
        $body=$reply->getMessage();
        $this->deliver->sendMail($subject, $from, $sender, $body);
    }
}
