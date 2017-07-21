<?php

namespace AppBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use AppBundle\Entity\Config;

class ReadConfig
{
    private $em;
    private $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager'); //   getDoctrine()->getManager();
    }

    public function findParameter($parameter)
    {
        $this->config = $this->em->getRepository('AppBundle:Config')->findOneBy(['name' => $parameter]);
        return $this->config;
    }

    public function findAll()
    {
        $this->config = $this->em->getRepository('AppBundle\Entity\Config')->findAll();
        return $this->config;
    }

    public function __toString()
    {
        return $this->config->getValue();
    }
}
