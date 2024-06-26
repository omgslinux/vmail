<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface as FFI;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Utils\ReadConfig;
use App\Form\UserType;
use App\Form\DomainType;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

/**
 * Domain controller.
 */
#[Route(path: '/admin/domain', name: 'admin_domain_')]
class DomainController extends AbstractController
{

    const TABS = [
        [
          'n' => 'users',
          't' => 'Users',
        ],
        [
          'n' => 'aliases',
          't' => 'Alias',
        ],
      ];

    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => 'admin_domain_',
        'included' => 'domain/_form',
        'tdir' => 'domain',
        'BASEDIR' => 'domain/',
        'modalId' => 'domains',
    ];

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Lists all domain entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ReadConfig $config): Response
    {
        $entity = new Domain();
        $form = $this->createForm(DomainType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);
            $base=$config->findParameter('virtual_mailbox_base');
            mkdir($base.'/'.$entity->getId());
            system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(self::VARS['BASEDIR'] . '/index.html.twig', array(
            'entities' => $this->repo->findAll(),
            'title' => 'Domain list',
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }


    /**
     * Edit a domain entity.
     */
    #[Route(path: '/{id}/edit/', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Domain $entity, ReadConfig $config): Response
    {
        $form = $this->createForm(DomainType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->makeMaildir($entity);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(
            self::VARS['BASEDIR'] . '/_form.html.twig',
            [
                'entity' => $entity,
                'modalTitle' => 'Domain edit',
                'form' => $form->createView(),
                'VARS' => self::VARS,
                'ajax' => true,
                'delete_form' => true,
            ]
        );
    }


    #[Route(path: '/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Domain $entity, REPO $repo, ReadConfig $config): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $base=$config->findParameter('virtual_mailbox_base');
            system("rm -rf " . $base . "/" . $entity->getName());
            rmdir($base . "/" . $entity->getId());
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }


    /**
     * Creates a form to show a FundBanks entity.
     */
    #[Route(path: '/show/byname/{name}', name: 'showbyname', methods: ['GET', 'POST'])]
    public function showByName(Request $request, FFI $ff, $name, UR $ur, ReadConfig $config)
    {
        $activetab = 'users';
        $session = $request->getSession();
        // Para la entidad (el dominio)
        $entity=$this->repo->findOneByName($name);
        $oldname=$entity->getName();
        $users=$aliases=[];

        foreach ($entity->getUsers() as $user) {
            if ($user->isList()) {
                $aliases[]=$user;
            } else {
                $users[]=$user;
            }
        }
        //$users=$ur->findBy(['domain' => $entity, 'list' => 0]);
        //$lists=$ur->findBy(['domain' => $entity, 'list' => 1]);
        $form = $this->createForm(DomainType::class, $entity);
        // Fin de definicion de la entidad

        // Pestaña usuarios
        $user = (new User())
        ->setDomain($entity)
        ->setSendEmail(true)
        ->setActive(true)
        ;
        $userform = $this->createForm(
            UserType::class,
            $user,
            [
                'showAutoreply' => false,
            ]
        );

        // Fin pestaña usuarios

        // Pestaña Alias
        $alias = (new User())
        ->setDomain($entity)
        ->setList(true)
        ->setPassword(false)
        ;
        // createNamed es para dar un nombre al formulario para que no sean ambos 'user'
        $aliasform = $ff->createNamed(
            'alias',
            UserType::class,
            $alias,
            [
                'domainId' => $entity->getId(),
                'showAlias' => true,
            ]
        )
        ;
        // Fin pestaña aliases

        // Vamos a ver los POST de los distintos formularios. Sólo puede ser uno

        $reload = false;

        // Formulario de la entidad
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);
            $newName = $entity->getName();
            if ($oldname!=$newName) {
                $base=$config->findParameter('virtual_mailbox_base');
                system("cd $base;mv $oldname $newName;ln -sf " . $entity->getId() . " " . $newName);
            }

            $reload = true;
        }

        // Formulario de los usuarios
        $userform->handleRequest($request);

        if ($userform->isSubmitted() && $userform->isValid()) {
            $ur->formSubmit($userform);

            $reload = true;
        }


        if ($reload) {
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', ['id' => $entity->getId()]);
        } else {
            if (null!=$session->get('activetab')) {
                $activetab = $session->get('activetab');
                $session->remove('activetab');
            }
        }


        return $this->render(
            self::VARS['BASEDIR'] . '/show.html.twig',
            [
                'entity' => $entity,
                'tabs' => self::TABS,
                'activetab' => $activetab,
                'form' => $form->createView(),
                'user_form' => $userform->createView(),
                'alias_form' => $aliasform->createView(),
                'users' => $users,
                'aliases' => $aliases,
                'VARS' => self::VARS,
                'origin' => $this->generateUrl(self::VARS['PREFIX'] . 'show', ['id' => $entity->getId()]),
            ]
        );
    }


    /**
     * Creates a form to show a entity.
     */
    #[Route(path: '/show/byid/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'showbyname', ['name' => $domain->getName()]);
    }
}
