<?php

namespace App\Dto;

use App\Entity\Domain;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class CertCommonDto
{
    private array $subject = [];

    #[Assert\NotBlank(groups: ['group_co'])]
    private ?string $countryName = 'ES';

    #[Assert\NotBlank(groups: ['group_st'])]
    private ?string $stateOrProvinceName = null;

    #[Assert\NotBlank(groups: ['group_loc'])]
    private ?string $localityName = null;

    #[Assert\NotBlank(groups: ['group_orgun'])]
    private ?string $organizationalUnitName = null;

    #[Assert\NotBlank(groups: ['group_org'])]
    private ?string $organizationName = null;

    #[Assert\NotBlank(groups: ['group_cn'])]
    private ?string $commonName = null;

    #[Assert\NotBlank(groups: ['with_file'])]
    private ?string $customFile = null;

    private ?User $user = null;
    private ?string $emailAddress = null;

    #[Assert\NotBlank(groups: ['password'])]
    #[Assert\Length(min: 5, groups: ['password'])]
    private ?string $plainPassword = null;
    //private array $plainPassword = [];

    public function __construct()
    {
    }

    public function toArray(): array
    {
        return [
            'subject'                 => $this->subject,
            'countryName'             => $this->countryName,
            'stateOrProvinceName'     => $this->stateOrProvinceName,
            'localityName'            => $this->localityName,
            'organizationalUnitName'  => $this->organizationalUnitName,
            'organizationName'        => $this->organizationName,
            'commonName'              => $this->commonName,
            'customFile'              => $this->customFile,
            'emailAddress'            => $this->emailAddress,
            'plainPassword'           => $this->plainPassword,
        ];
    }

    public function getSubject(): array
    {
        return $this->subject;
    }

    public function setSubject(array $array): self
    {
        $this->subject = $array;
        return $this->setCountryName($array["countryName"])
        ->setStateOrProvinceName($array["stateOrProvinceName"])
        ->setLocalityName($array["localityName"])
        ->setOrganizationName($array['organizationName'])
        ->setOrganizationalUnitName($array['organizationalUnitName']??null)
        ->setEmailAddress($array['emailAddress']??null)
        ->setCommonName($array['commonName']);
    }

    public function setCountryName(?string $text): self
    {
        $this->countryName = $text;

        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setStateOrProvinceName(?string $text): self
    {
        $this->stateOrProvinceName = $text;

        return $this;
    }

    public function getStateOrProvinceName(): ?string
    {
        return $this->stateOrProvinceName;
    }

    public function setLocalityName(?string $text): self
    {
        $this->localityName = $text;

        return $this;
    }

    public function getLocalityName(): ?string
    {
        return $this->localityName;
    }

    public function setorganizationalUnitName(?string $text): self
    {
        $this->organizationalUnitName = $text;

        return $this;
    }

    public function getOrganizationalUnitName(): ?string
    {
        return $this->organizationalUnitName;
    }

    public function setOrganizationName(?string $text): self
    {
        $this->organizationName = $text;

        return $this;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setCommonName(?string $text): static
    {
        $this->commonName = $text;

        return $this;
    }

    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    public function setCustomFile(?string $text): self
    {
        $this->customFile = $text;

        return $this;
    }

    public function getCustomFile(): ?string
    {
        return $this->customFile;
    }

    public function setEmailAddress(?string $text): self
    {
        $this->emailAddress = $text;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setUser(User $text): self
    {
        $this->user = $text;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setPlainPassword(?string $text): self
    {
        dump($text);
        if (is_array($text)) {
            $this->plainPassword = $text['setkey']->getPlainPassword();
        } else {
            $this->plainPassword = $text;
        }

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
