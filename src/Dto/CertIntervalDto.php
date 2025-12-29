<?php

namespace App\Dto;

use App\Entity\Domain;

class CertIntervalDto
{
    private ?\DateInterval $interval = null;
    private ?string $duration = null;
    private \DateTime $notAfter;
    private \DateTime $notBefore;

    public function __construct()
    {
        $this->notAfter = new \DateTime();
        $this->notBefore = new \DateTime();
    }

    public function toArray(): array
    {
        return [
            'interval' => $this->interval,
            'duration' => $this->duration,
            'notAfter' => $this->notAfter,
            'notBefore' => $this->notBefore,
        ];
    }

    public function setNotAfter(\DateTime $date): self
    {
        $this->notAfter = $date;

        return $this;
    }

    public function setNotBefore(\DateTime $date): self
    {
        $this->notBefore = $date;

        return $this;
    }

    public function getNotAfter(): \DateTime
    {
        return $this->notAfter;
    }

    public function getNotBefore(): \DateTime
    {
        return $this->notBefore;
    }

    public function setInterval(\DateInterval|string $text): self
    {
        if ($text instanceof \DateInterval) {
            $this->interval = $text;
        } else {
            $this->interval = \DateInterval::createFromDateString($text);
        }
        //$this->duration = $text;
        //$this->notAfter->add(\DateInterval::createFromDateString($this->duration));
        $this->notAfter->add($this->interval);

        return $this;
    }

    public function getInterval(): ?\DateInterval
    {
        //return  \DateInterval::createFromDateString($this->duration)??null;
        return  $this->interval??null;
    }

    public function setDuration(string $text): self
    {
        $this->duration = $text;
        $this->setInterval($text);

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setCertInterval(?array $array): self
    {
        $this->notAfter = $array['NotAfter'];
        $this->notBefore = $array['NotBefore'];

        return $this;
    }

    public function getCertInterval(): array
    {
        return [
            'duration' => $this->duration,
            'interval' => $this->interval,
        ];
    }
}
