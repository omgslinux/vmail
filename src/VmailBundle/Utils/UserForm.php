<?php

namespace VmailBundle\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\Config;
use VmailBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class UserForm
{
    private $em;
    public $config;
    public $deliver;
    private $user;
    public $encoder;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
    }

    public function setUser($user)
    {
        $this->user=$user;
    }

    public function formSubmit($form)
    {
        $em = $this->em;
        $user=$this->user;
        $plainPassword = $form->get('plainPassword')->getData();
        if (!empty($plainPassword)) {
            //$encoder = $this->get('security.password_encoder');
            $encodedPassword = $this->encoder->encodePassword($user, $user->getPlainpassword());
            $user->setPassword($encodedPassword);
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
        $this->deliver->sendMail($subject,$sender,$recipient,$body);

    }

}
