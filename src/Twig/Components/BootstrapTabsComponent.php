<?php
// src/Components/TabsComponent.php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class BootstrapTabsComponent
{
    public array $tabs = [];
    public int $activeTab = 0;
    public string $tab_style = 'tabs'; // 'tabs' or 'pills'
    public string $variant = ''; // 'underline', 'fill', etc.
    public string $id = 'default-tabs';
    public string $contentClass = 'p-4 border border-top-0 rounded-bottom';
    public ?string $tabId = null;
    public string $flex = "row";
}
