<?php

namespace App\Entity;

use App\Repository\ServerCertificateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[UniqueConstraint(name: 'cert_description_unique', columns: ['domain_id', 'description'])]
#[ORM\Entity(repositoryClass: ServerCertificateRepository::class)]
#[UniqueEntity(fields: ['domain', 'description'], message: 'Ya existe ese para ese dominio', errorPath: 'description')]
class ServerCertificate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serverCertificates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Domain $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private array $certdata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?Domain
    {
        return $this->domain;
    }

    public function setDomain(?Domain $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCertdata(): array
    {
        return $this->certdata;
    }

    public function setCertdata(array $certdata): static
    {
        $this->certdata = $certdata;

        return $this;
    }
}
