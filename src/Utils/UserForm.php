<?php

namespace App\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use App\Entity\Config;
use App\Entity\User;
use App\Utils\ReadConfig;
use App\Utils\DeliverMail;
use App\Utils\PassEncoder;
use Doctrine\ORM\EntityManagerInterface;

class UserForm
{
    private $EM;
    private $config;
    private $deliver;
    private $user;
    public $encoder;

    public function __construct(EntityManagerInterface $em, ReadConfig $config, DeliverMail $deliver, PassEncoder $enc)
    {
        $this->EM = $em;
        $this->config = $config;
        $this->deliver = $deliver;
        $this->encoder = $enc;
    }

    public function setUser($user)
    {
        $this->user=$user;
    }

    public function formSubmit($form)
    {
        $em = $this->EM;
        $user=$this->user;
        $plainPassword = $form->get('plainPassword')->getData();
        if (!empty($plainPassword)) {
            //$encodedPassword = $this->encoder->encodePassword($user, $user->getPlainpassword());
            //$user->setPassword($encodedPassword);
            //$user->setPassword(PassEncoder::encodePassword($user->getPlainpassword()));
            $user->setPassword($this->encoder->encodePassword($user->getPlainpassword()));
        }
        $em->persist($user);
        $em->flush();
        if ($form->get('sendemail')) {
            $this->sendWelcomeMail($user);
        }

    }

    private function sendWelcomeMail(User $user)
    {
        $body=$this->config->findParameter('welcome_body');
        $subject=$this->config->findParameter('welcome_subject');
        $recipient=$user->getEmail();
        $sender='welcome@default';
        $this->deliver->sendMail($subject, $sender, $recipient, $body);

    }
}
