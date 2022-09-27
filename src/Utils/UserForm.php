<?php

namespace App\Utils;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
//use App\Entity\Config;
use App\Entity\User;
use App\Utils\ReadConfig;
use App\Utils\DeliverMail;
use App\Utils\PassEncoder;
//use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository as REPO;

class UserForm
{
    private $repo;
    private $config;
    private $deliver;
    private $user;
    public $encoder;

    public function __construct(REPO $repo, ReadConfig $config, DeliverMail $deliver, PassEncoder $enc)
    {
        $this->repo = $repo;
        $this->config = $config;
        $this->deliver = $deliver;
        $this->encoder = $enc;
    }

    public function setUser(User $user)
    {
        $this->user=$user;
    }

    public function formSubmit($form)
    {
        $user=$this->user;
        $plainPassword = $form->get('plainPassword')->getData();
        if (!empty($plainPassword)) {
            //$encodedPassword = $this->encoder->encodePassword($user, $user->getPlainpassword());
            //$user->setPassword($encodedPassword);
            //$user->setPassword(PassEncoder::encodePassword($user->getPlainpassword()));
            $user->setPassword($this->encoder->encodePassword($user->getPlainpassword()));
        }
        $this->repo->add($user, true);
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
