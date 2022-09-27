<?php

namespace App\Utils;

use App\Repository\ConfigRepository as REPO;
use Doctrine\ORM\EntityManagerInterface;

class ReadConfig
{
    private $repo;
    private $value;

    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
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
