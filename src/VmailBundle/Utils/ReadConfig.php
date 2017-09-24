<?php

namespace VmailBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class ReadConfig
{
    private $em;
    private $repo;
    private $value;
    private $c;

    public function __construct(ContainerInterface $c=null)
    {
        if (!empty($c)) {
            $this->c=$c;
        }
        $this->em = $this->c->get('doctrine.orm.entity_manager');
    }


    public function findParameter($parameter)
    {
      $this->repo=$this->em->getRepository(Config::class);
        $config = $this->repo->findOneBy(['name' => $parameter]);
        $this->value=$config->getValue();
        return $this->value;
    }

    public function findAll()
    {
        return $repo->findAll();
    }

    public function __toString()
    {
        return $this->value;
    }
}
