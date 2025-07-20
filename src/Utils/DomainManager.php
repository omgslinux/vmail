<?php

namespace App\Utils;

use Symfony\Component\Form\FormFactoryInterface as FFI;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Utils\ReadConfig;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

class DomainManager
{

    private $repo;
    private $config;

    public function __construct(REPO $repo, ReadConfig $config)
    {
        $this->repo = $repo;
        $this->config = $config;
    }

    public function getBaseDirectory(): string
    {
        return $this->config->findParameter('virtual_mailbox_base');
    }

    public function create(Domain $entity): void
    {
        $this->repo->add($entity, true);
        $base=$this->config->findParameter('virtual_mailbox_base');
        mkdir($base.'/'.$entity->getId());
        system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
    }

    public function read(string $name): Domain
    {
        $entity = $this->repo->findOneByName($name);

        return $entity;
    }

    public function delete(Domain $entity): void
    {
        $base=$this->config->findParameter('virtual_mailbox_base');
        system("rm -rf " . $base . "/" . $entity->getName());
        rmdir($base . "/" . $entity->getId());
        $this->repo->remove($entity, true);
    }


    public function update(Domain $entity, string $oldname=""): void
    {
        $this->repo->add($entity, true);
        $newName = $entity->getName();
        if ($oldname!=$newName) {
            $base=$config->findParameter('virtual_mailbox_base');
            system("cd $base;mv $oldname $newName;ln -sf " . $entity->getId() . " " . $newName);
        }
    }

    public function validateDomainName($name): bool
    {
        // Verificar si la longitud de la cadena es válida
        if (strlen($name) < 1 || strlen($name) > 253) {
            return false;
        }

        // Definir el patrón de la expresión regular para validar el nombre de dominio
        $pattern = '/^(?!-)([a-zA-Z0-9-]{1,63})(?<!-)\.(?:(?!-)([a-zA-Z0-9-]{1,63})(?<!-)\.)*(?!-)([a-zA-Z]{2,})$/';

        // Usar filter_var con FILTER_VALIDATE_DOMAIN para una validación adicional
        if (filter_var($name, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        // Verificar si la cadena coincide con el patrón de la expresión regular
        return preg_match($pattern, $name) === 1;
    }


}
