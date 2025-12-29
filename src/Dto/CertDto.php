<?php

namespace App\Dto;

use App\Entity\Domain;
use Symfony\Component\Validator\Constraints as Assert;

class CertDto
{
    private ?Domain $domain = null;

    #[Assert\Valid]
    private ?CertCommonDto $common = null;

    #[Assert\Valid]
    private ?CertIntervalDto $interval = null;

    private ?string $certtype = null;
    private ?array $subject = null;
    private bool $download = false;
    private ?string $certfile = null;
    private ?array $plainPassword = null;
    private bool $new = true;
    private bool $CAInherit = true;


    public function __construct()
    {
        $this->common = new CertCommonDto();
        $this->interval = new CertIntervalDto();
    }

    public function setSubject(array $array): self
    {
        $this->common->setSubject($array);
        $this->subject = $array;

        return $this;
    }

    public function getSubject(): ?array
    {
        return $this->subject;
    }


    public function setCommon(?CertCommonDto $array): self
    {
        $this->common = $array;

        return $this;
    }

    public function getCommon(): ?CertCommonDto
    {
        return $this->common;
    }

    public function setDomain(Domain $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function setDuration(string $text): self
    {
        //$this->duration = $text;
        $this->interval->setDuration($text);

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function getInterval(): ?CertIntervalDto
    {
        return $this->interval;
    }

    public function setInterval(array $array): self
    {
        $this->interval->setCertInterval($array);

        return $this;
    }

    public function setCertfile(string $text): self
    {
        $this->certfile = $text;

        return $this;
    }

    public function getCertfile(): ?string
    {
        return $this->certfile;
    }

    public function setCerttype(string $text): self
    {
        $this->certtype = $text;

        return $this;
    }

    public function getCerttype(): ?string
    {
        return $this->certtype;
    }

    public function setDownload(bool $text): self
    {
        $this->download = $text;

        return $this;
    }

    public function isDownload(): bool
    {
        return $this->download;
    }

    public function setNew(bool $text): self
    {
        $this->new = $text;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->new;
    }

    public function setCAInherit(bool $text): self
    {
        $this->CAInherit = $text;

        return $this;
    }

    public function isCAInherit(): bool
    {
        return $this->CAInherit;
    }

    public function setPlainPassword(array $text): self
    {
        $this->plainPassword = $text;

        return $this;
    }

    public function getPlainPassword(): ?array
    {
        return $this->plainPassword;
    }
}
