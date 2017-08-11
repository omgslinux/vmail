<?php

namespace VmailBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class ReadConfig
{
    private $em;
    private $config;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
    }

    public function findParameter($parameter)
    {
        $this->config = $this->em->getRepository('VmailBundle:Config')->findOneBy(['name' => $parameter]);
        return $this->config->getValue();
    }

    public function findAll()
    {
        $this->config = $this->em->getRepository('VmailBundle\Entity\Config')->findAll();
        return $this->config;
    }

    public function __toString()
    {
        return $this->config->getValue();
    }
}
