<?php

namespace AppBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use AppBundle\Entity\Config;
use Doctrine\ORM\EntityManager;

class DeliverMail
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; //   getDoctrine()->getManager();
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
