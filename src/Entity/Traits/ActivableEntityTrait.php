<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ActivableEntityTrait
{

    #[ORM\Column(name: 'is_active', type: 'boolean', nullable: true)]
    private bool $active=true;

    public function setActive(?bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
