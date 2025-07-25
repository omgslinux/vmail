<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Form\UserType;
use App\Repository\UserRepository as REPO;

/**
 * Alias controller.
 */
#[Route(path: '/manage/alias', name: 'manage_alias_')]
class AliasController extends AbstractController
{
    const PREFIX = 'manage_alias_';

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Lists all alias entities.
     */
    #[Route(path: '/index/{id}', name: 'index', methods: ['GET'])]
    public function indexAction(Domain $domain)
    {
        if (!($this->isGranted('ROLE_ADMIN')) && $domain->getId()===0) {
            return $this->redirectToRoute(
                'manage_domain_alias_index',
                [
                    'id' => $this->getUser()->getDomain()->getId(),
                    'PREFIX' => self::PREFIX,
                ]
            );
        }

        //$em = $this->getDoctrine()->getManager();
        //$aliases = $em->getRepository(User::class)->findBy(['domain' => $domain->getId(), 'list' => 1]);
        $aliases = $this->repo->findBy(['domain' => $domain->getId(), 'list' => 1]);

        return $this->render('alias/index.html.twig', array(
            'items' => $aliases,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Lists all alias entities.
     */
    #[Route(path: '/index/{id}', name: 'domain_index', methods: ['GET'])]
    public function domainIndexAction(Domain $domain)
    {
        if ($domain->getId==0) {
            return $this->redirectToRoute(self::PREFIX . 'index', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        //$em = $this->getDoctrine()->getManager();
        //$aliases = $em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 1]);
        $aliases = $this->repo->findBy(['domain' => $domain, 'list' => 1]);

        return $this->render('alias/index.html.twig', array(
            'domain' => $domain,
            'items' => $aliases,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Creates a new alias entity.
     */
    #[Route(path: '/new/{id}', name: 'new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, Domain $domain)
    {
        if (!($this->isGranted('ROLE_ADMIN')) && $domain->getId()===0) {
            return $this->redirectToRoute('manage_domain_alias_new', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        //$em = $this->getDoctrine()->getManager();

        $alias = new User();
        $alias
          ->setDomain($domain)
          ->setList(true)
          ->setPassword(false)
        ;
        $form = $this->createForm(
            UserType::class,
            $alias,
            [
                'domainId' => $domain->getId(),
                'showAlias' => true
            ]
        )
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($alias, true);

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $alias->getId()));
        }

        return $this->render(
            'alias/edit.html.twig',
            [
                'domain' => $domain,
                'form' => $form->createView(),
                'title' => 'Alias creation',
                'PREFIX' => self::PREFIX,
            ]
        );
    }

    /**
     * Creates a new alias entity.
     */
    #[Route(path: '/domain/{id}', name: 'domain_new', methods: ['GET', 'POST'])]
    public function domainNewAction(Request $request, Domain $domain)
    {
        if ($domain->getId()===0) {
            return $this->redirectToRoute(self::PREFIX . 'new', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $alias = new User();
        $alias
        ->setDomain($domain)
        ->setList(true)
        ->setPassword(false)
        ;
        $form = $this->createForm(
            UserType::class,
            $alias,
            [
                'domainId' => $domain->getId(),
                'showAlias' => true,
                'action' => $this->generateUrl(self::PREFIX . 'domain_new', ['id' => $domain->getId()]),
            ],
        )
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($alias, true);

            return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName(), 'activetab' => 1 ]);
        }

        return $this->render('tabs/aliases/_form.html.twig', array(
            'domain' => $domain,
            'form' => $form,
            'title' => 'Alias creation',
        ));
    }

    /**
     * Finds and displays a alias entity.
     */
    #[Route(path: '/show/{id}', name: 'show', methods: ['GET'])]
    public function showAction(User $alias)
    {

        return $this->render('alias/show.html.twig', array(
            'item' => $alias,
        ));
    }

    /**
     * Displays a form to edit an existing alias entity.
     */
    #[Route(path: '/{id}/edit/', name: 'edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, User $alias)
    {
        $origin = $request->query->get('origin', null);
        $domain=$alias->getDomain();
        $form = $this->createForm(
            UserType::class,
            $alias,
            [
                'domainId' => $domain->getId(),
                'showAlias' => true,
                //'showList' => true
                'action' => $this->generateUrl(self::PREFIX . 'edit', ['id' => $alias->getId()]),
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->add($alias, true);

            if (null==$origin) {
                //return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
                return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName(), 'activetab' => 1 ], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $alias->getId()));
            }
        }

        return $this->render('tabs/aliases/_form.html.twig', [
            'domain' => $domain,
            'title' => 'Alias edit',
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $entity): Response
    {
        $domain = $entity->getDomain();
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $this->repo->remove($entity, true);
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName(), 'activetab' => 1 ], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('manage_user_index');
        }


        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
