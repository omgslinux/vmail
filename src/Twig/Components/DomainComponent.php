<?php

namespace App\Twig\Components;

use App\Entity\Domain;
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

    //public ?Question $initialFormData = null;

    //#[LiveProp]
    //public bool $isModalOpen = false;

    #[LiveProp]
    public ?Domain $entity=null;

    #[LiveProp]
    public string $modalId;

    #[LiveProp]
    public string $tagPrefix;

    public $itemName = 'domain';


    public function __construct(private REPO $repo, private ReadConfig $config)
    {}

    #[LiveAction]
    protected function instantiateForm(): FormInterface
    {
        // we can extend AbstractController to get the normal shortcuts
        return $this->createForm(DomainType::class, $this->entity);
    }

    #[LiveAction]
    public function new()
    {
        $this->entity = new Domain();
        $this->resetForm();
    }

    #[LiveAction]
    public function edit(#[LiveArg] Domain $id)
    {
        $this->entity = $id;
        $this->resetForm();
    }

    #[LiveListener('deleteConfirmed')]
    public function delete(#[LiveArg] Domain $id)
    {
        $this->repo->remove($id, true);
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

        $this->repo->add($this->entity, true);

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
