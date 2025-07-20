<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ModalComponent
{
    public string $modalId = 'user';
    public string $modalSize = 'modal-md';
    public string $tagPrefix = 'create';

    public ?string $modalTitle = null;

    public int $button = 0;
    public ?string $liveAction = null;



    public function mount()
    {
    }

    public function getId2(): string
    {
        return $this->tagPrefix . $this->modalId . "Modal";
    }

    public function getTitle(): ?string
    {
        return $this->modalTitle??$this->tagPrefix . ' ' . $this->modalId;
    }
}
