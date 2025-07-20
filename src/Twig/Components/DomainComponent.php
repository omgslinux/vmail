<?php

namespace App\Twig\Components;

use App\Entity\Domain as Entity;
use App\Form\DomainType;
use App\Repository\DomainRepository as REPO;
use App\Utils\ReadConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;

#[AsLiveComponent]
class DomainComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?Entity $id=null;

    #[LiveProp]
    public string $modalId;

    #[LiveProp]
    public string $tagPrefix;

    public $itemName = 'domain';


    public function __construct(private REPO $repo, private ReadConfig $config)
    {
    }

    #[LiveAction]
    protected function instantiateForm(): FormInterface
    {
        // we can extend AbstractController to get the normal shortcuts
        return $this->createForm(DomainType::class, $this->id);
    }

    #[LiveAction]
    public function new()
    {
        $this->entity = new Entity();
        $this->resetForm();
    }

    #[LiveAction]
    public function edit(#[LiveArg] Entity $id)
    {
        $this->id = $id;
        $this->resetForm();
        //$this->submitForm();
        $this->addFlash('success', $this->itemName .' updated!');
    }

    #[LiveAction]
    public function delete(#[LiveArg] Entity $id)
    {
        $this->repo->remove($id, true);
        $this->addFlash('success', $this->itemName .' deleted!');
    }

    public function getAll()
    {
        return $this->repo->findAll();
    }

    #[LiveAction]
    public function save()
    {
        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();
        //dump($this->form);
        //$this->entity = $this->getForm()->getData();

        $this->repo->add($this->id, true);

        $this->addFlash('success', $this->itemName . ' saved!');

        $this->resetForm();
    }

    private function createMaildir()
    {
        //$this->repo->add($entity, true);
        $base=$this->config->findParameter('virtual_mailbox_base');
        mkdir($base.'/'.$this->entity->getId());
        system("cd $base;ln -s " . $this->entity->getId() . " " . $this->entity->getName());
    }
}
