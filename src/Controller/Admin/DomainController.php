<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Utils\UserForm;
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
    const PREFIX = 'admin_domain_';

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
    //public function index(Request $request, REPO $repo, ReadConfig $config): Response
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
        }

        return $this->render('domain/index.html.twig', array(
            'entities' => $this->repo->findAll(),
            'title' => 'Domain list',
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ));
    }


    /**
     * Creates a new User in a Domain entity.
     *
     * @Route("/user/new/{id}", name="user_new", methods={"GET", "POST"})
     */
    public function newUserAction(Request $request, UserForm $u, Domain $domain)
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
            $u->setUser($user);
            $u->formSubmit($form);

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }

        return $this->render('user/form.html.twig', array(
            'user' => $user,
            'action' => 'Create a new user',
            'backlink' => $this->generateUrl(self::PREFIX . 'index'),
            'backmessage' => 'Back',
            'form' => $form->createView(),
            'ajax' => true,
            'PREFIX' => self::PREFIX,
        ));
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

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }


    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/show/byname/{name}", name="showbyname", methods={"GET", "POST"})
     */
    public function showByNameAction(Request $request, $name, UR $ur, UserForm $uf, ReadConfig $config, $activetab = 'users')
    {
        $entity=$this->repo->findOneByName($name);
        $oldname=$entity->getName();
        $users=$ur->findBy(['domain' => $entity, 'list' => 0]);
        //$lists=$em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 1]);
        $lists=$ur->findBy(['domain' => $entity, 'list' => 1]);
        //$deleteForm = $this->createDeleteForm($domain);
        $editform = $this->createForm(DomainType::class, $entity);
        $editform->handleRequest($request);

        if ($editform->isSubmitted() && $editform->isValid()) {
            $this->repo->add($entity, true);
            if ($oldname!=$entity->getName()) {
                $base=$config->findParameter('virtual_mailbox_base');
                //system("rm -rf " . $base . "/" . $entity->getName());
                //system("cd $base;ln -sf " . $entity->getId() . " " . $entity->getName());
                system("cd $base;mv $oldname " .$entity->getName(). ";ln -sf " . $entity->getId() . " " . $entity->getName());
            }
        }

        // Pestaña usuarios
        $user = new User();
        $user->setDomain($entity)->setSendEmail(true)->setActive(true);
        $userform = $this->createForm(UserType::class, $user,
            [
                'showAutoreply' => false,
            ]
        );

        $userform->handleRequest($request);

        if ($userform->isSubmitted() && $userform->isValid()) {
            $uf->setUser($user);
            $uf->formSubmit($userform);
            $users[] = $user;

            //return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }
        // Fin pestaña usuarios

        // Pestaña Alias
        $alias = new User();
        $alias
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
        $aliasform->handleRequest($request);
        if ($aliasform->isSubmitted() && $aliasform->isValid()) {
            $ur->add($alias, true);

            //return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }
        // Fin pestaña aliases

        // Pestaña listas
        $list = new User();
        $list
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
        $listform->handleRequest($request);
        if ($listform->isSubmitted() && $listform->isValid()) {
            $ur->add($list, true);

            //return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }
        // Fin pestaña listas


        return $this->render('domain/show.html.twig', array(
            'entity' => $entity,
            'tabs' => self::TABS,
            'activetab' => $activetab,
            'form' => $editform->createView(),
            'user_form' => $userform->createView(),
            'alias_form' => $aliasform->createView(),
            'list_form' => $listform->createView(),
            'delete_form' => true, // $deleteForm->createView(),
            'users' => $users,
            'lists' => $lists,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET", "POST"})
     */
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute(self::PREFIX . 'showbyname', ['name' => $domain->getName()]);
    }
}
