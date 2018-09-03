<?php

namespace VmailBundle\Utils;

use VmailBundle\Entity\Config;
use Doctrine\ORM\EntityManagerInterface;

class ReadConfig
{
    private $repo;
    private $value;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Config::class);
    }


    public function findParameter($parameter)
    {
        $config = $this->repo->findOneBy(['name' => $parameter]);
        $this->value=$config->getValue();
        return $this->value;
    }

    public function findAll()
    {
        return $this->repo->findAll();
    }

    public function __toString()
    {
        return $this->value;
    }
}
