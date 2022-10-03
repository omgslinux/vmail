<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
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
 *
 * @Route("/admin/domain", name="admin_domain_")
 */
class DomainController extends AbstractController
{

    const TABS = [
        [
          'n' => 'users',
          't' => 'Usuarios',
        ],
        [
          'n' => 'aliases',
          't' => 'Alias',
        ],
        [
          'n' => 'lists',
          't' => 'Listas',
        ],
      ];

    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => 'admin_domain_',
        'included' => 'domain/_form',
        'tdir' => 'domain',
    ];

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Lists all domain entities.
     *
     * @Route("/", name="index", methods={"GET", "POST"})
     */
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

        return $this->render(self::VARS['tdir'] . '/index.html.twig', array(
            'entities' => $this->repo->findAll(),
            'title' => 'Domain list',
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }


    /**
     * Edit a domain entity.
     *
     * @Route("/{id}/edit/", name="edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Domain $entity, ReadConfig $config): Response
    {
        $form = $this->createForm(DomainType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->makeMaildir($entity);
            //$this->repo->add($entity, true);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(self::VARS['tdir'] . '/_form.html.twig', [
            'entity' => $entity,
            'modalTitle' => 'Domain edit',
            'form' => $form->createView(),
            'VARS' => self::VARS,
            'tagPrefix' => 'Editar',
            'modalId' => 'domains',
            'nobutton' => true,
        ]
        );
    }


    /**
     * Creates a new User in a Domain entity.
     *
     * @Route("/user/new/{id}", name="user_new", methods={"GET", "POST"})
     */
    public function newUserAction(Request $request, UR $ur, Domain $domain)
    {
        $user = new User();
        $user->setDomain($domain)->setSendEmail(true)->setActive(true);
        $form = $this->createForm(
            UserType::class,
            $user,
            [
                'action' => $this->generateUrl('user_new', ['id' => $domain->getId()]),
                'showAutoreply' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ur->formSubmit($form);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', array('id' => $domain->getId()));
        }

        return $this->render('user/form.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'ajax' => true,
            'VARS' => self::VARS,
        ]
        );
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"POST"})
     */
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
     *
     * @Route("/show/byname/{name}", name="showbyname", methods={"GET", "POST"})
     */
    public function showByNameAction(Request $request, $name, UR $ur, ReadConfig $config, $activetab = 'users')
    {

        // Para la entidad (el dominio)
        $entity=$this->repo->findOneByName($name);
        $oldname=$entity->getName();
        $users=$ur->findBy(['domain' => $entity, 'list' => 0]);
        $lists=$ur->findBy(['domain' => $entity, 'list' => 1]);
        $form = $this->createForm(DomainType::class, $entity);
        // Fin de definicion de la entidad

        // Pestaña usuarios
        $user = (new User())
            ->setDomain($entity)->setSendEmail(true)->setActive(true);
        $userform = $this->createForm(UserType::class, $user,
            [
                'showAutoreply' => false,
            ]
        );

        // Fin pestaña usuarios

        // Pestaña Alias
        $alias = (new User())
        ->setDomain($entity)
        ->setList(false)
        ->setPassword(false)
        ;
        $aliasform = $this->createForm(
            UserType::class,
            $alias,
            [
                'domain' => $entity->getId(),
                'showList' => true,
            ]
        )
        ;
        // Fin pestaña aliases

        // Pestaña listas
        $list = (new User())
        ->setDomain($entity)
        ->setList(true)
        ->setPassword(false)
        ;
        $listform = $this->createForm(
            UserType::class,
            $list,
            [
                'domain' => $entity->getId(),
                'showList' => true,
            ]
        )
        ;
        // Fin pestaña listas

        // Vamos a ver los POST de los distintos formularios. Sólo puede ser uno

        $reload = false;
        // Formulario de la entidad
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($entity, true);
            if ($oldname!=$entity->getName()) {
                $base=$config->findParameter('virtual_mailbox_base');
                system("cd $base;mv $oldname " .$entity->getName(). ";ln -sf " . $entity->getId() . " " . $entity->getName());
            }

            $reload = true;
        }
dump($form);

        // Formulario de los usuarios
        $userform->handleRequest($request);

        if ($userform->isSubmitted() && $userform->isValid()) {
            $ur->formSubmit($userform);

            $reload = true;
        }


        // Formulario de los alias
        $aliasform->handleRequest($request);
        if ($aliasform->isSubmitted() && $aliasform->isValid()) {
            $ur->add($alias, true);

            $reload = true;
        }


        // Formulario de las listas
        $listform->handleRequest($request);
        if ($listform->isSubmitted() && $listform->isValid()) {
            $ur->add($list, true);

            $reload = true;
        }


        if ($reload) return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', ['id' => $entity->getId()]);


        return $this->render(self::VARS['tdir'] . '/show.html.twig', [
            'entity' => $entity,
            'tabs' => self::TABS,
            'activetab' => $activetab,
            'form' => $form->createView(),
            'user_form' => $userform->createView(),
            'alias_form' => $aliasform->createView(),
            'list_form' => $listform->createView(),
            'users' => $users,
            'lists' => $lists,
            'VARS' => self::VARS,
        ]
        );
    }

    /**
     * Creates a form to show a entity.
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET", "POST"})
     */
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'showbyname', ['name' => $domain->getName()]);
    }
}
