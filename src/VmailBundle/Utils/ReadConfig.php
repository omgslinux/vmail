<?php

namespace VmailBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class ReadConfig
{
    private $em;
    private $repo;
    private $value;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
        $this->repo=$this->em->getRepository('VmailBundle:Config');
    }

    public function findParameter($parameter)
    {
        $config = $repo->findOneBy(['name' => $parameter]);
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
