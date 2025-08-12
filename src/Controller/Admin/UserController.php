<?php

namespace App\Controller\Admin;

use App\Entity\Domain;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use App\Form\UserType;
use App\Repository\UserRepository as REPO;
use App\Repository\DomainRepository;
use App\Utils\Certificate;

/**
 * User controller.
 */
#[Route(path: '/manage/user', name: 'manage_user_')]
class UserController extends AbstractController
{
    const VARS = [
        'modalSize' => 'modal-lg',
        'PREFIX' => 'manage_user_',
        'included' => 'user/_form',
        'tdir' => 'user',
        'BASEDIR' => 'user/',
        'modalId' => 'users',
    ];

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Lists all user entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request)
    {
        $activeTab = $request->query->get('activetab', 0);

        $parent=$this->getUser()->getDomain();
        $users=$aliases=[];

        foreach ($parent->getUsers() as $user) {
            if ($user->isList()) {
                $aliases[]=$user;
            } else {
                $users[]=$user;
            }
        }

        $tabs = [
            [
                'template' => 'tabs/users/_index.html.twig',
                'title' => 'Users',
                'context' => [
                    'users' => $users,
                    'modalSize' => 'modal-lg',
                    //'VARS' => $VARS,
                ]
            ],
            [
            'template' => 'tabs/aliases/_index.html.twig',
            'title' => 'Alias',
            'context' => [
                'aliases' => $aliases,
                'modalSize' => 'modal-lg'
                ]
            ],
        ];

        return $this->render(self::VARS['BASEDIR'] . 'index.html.twig', array(
            'tabs' => [
                'tabId' => 'domainTabs',
                'activeTab' => $activeTab,
                'tabs' => $tabs
            ],
            'targetPrefix' => 'users',
            'VARS' => self::VARS,
        ));
    }

    /**
     * Finds and displays a user entity.
     */
    #[Route(path: '/show/byemail/{email}', name: 'show_byemail', methods: ['GET', 'POST'])]
    public function showByEmailAction(Request $request, DomainRepository $DR, $email)
    {
        $t=explode('@', $email);
        //$em = $this->getDoctrine()->getManager();
        //$parent=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        $parent=$DR->findOneBy(['name' => $t[1]]);
        if (null==$parent) {
            $this->addFlash('error', 'Correo incorrecto');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }
        //$entity=$em->getRepository(User::class)->findOneBy(['domain' => $parent, 'name' => $t[0]]);
        $entity=$this->repo->findOneBy(['domain' => $parent, 'name' => $t[0]]);
        if (null==$entity) {
            $this->addFlash('error', 'Correo incorrecto');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        $form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain' => false,
                'showAutoreply' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->formSubmit($form);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', ['id' => $user->getId()]);
        }

        return $this->render(self::VARS['BASEDIR'] . 'show.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }

    /**
     * Finds and displays a user entity.
     */
    #[Route(path: '/show/byid/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user)
    {

        return $this->render(self::VARS['BASEDIR'] . 'show.html.twig', array(
            'user' => $user,
            'VARS' => self::VARS,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     */
    #[Route(path: '/{id}/edit/', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $entity)
    {
        $origin = $request->request->get('origin', null) ?? $request->query->get('origin', null);
        $form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => false,
                'showAutoreply' => null!==$entity->getReply(),
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'edit', ['id' => $entity->getId()]),
            ]
        );

        $render = [
            'entity' => $entity,
            'form' => $form,
            'delete_form' => true,
            'VARS' => self::VARS,
        ];
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->repo->formSubmit($form);
                $redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $entity->getDomain()->getName() ]);
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $redirectUrl = $this->generateUrl(self::VARS['PREFIX'] . 'index');
                }
                $redirectUrl = $this->getReferer($request);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                self::VARS['BASEDIR'] . '_form.html.twig',
                $render,
                new Response(null, 422)
            );
        } else {
            return $this->render(
                self::VARS['BASEDIR'] . '_form.html.twig',
                $render
            );
        }
    }

    #[Route(path: '/new/', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request)
    {
        $domain = $this->getUser()->getDomain();
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
        }

        $action = $this->generateUrl(self::VARS['PREFIX'] . 'new');

        $entity = (new User())
        ->setDomain($domain)
        ->setSendEmail(true)
        ->setActive(true)
        ;

        $form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => false,
                'passRequired' => true,
                'showAutoreply' => false,
                'action' => $action // $this->generateUrl(self::VARS['PREFIX'] . 'new', ['id' => $domain->getId()]),
            ]
        );

        $form->handleRequest($request);
        $render = [
            'template' => self::VARS['BASEDIR'] . '_form.html.twig',
            'args' => [
                'entity' => $entity,
                'form' => $form,
                'VARS' => self::VARS,
                'modalTitle' => 'User creation',
                'modalSize' => 'modal-xl',
            ]
        ];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->repo->formSubmit($form);
                //$redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
                $redirectUrl = $this->getReferer($request);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                $render['template'],
                $render['args'],
                new Response(null, 422)
            );
        }
            return $this->render(
                $render['template'],
                $render['args']
            );
    }


    public function newOLD(Request $request)
    {
        $domain = $this->getUser()->getDomain();
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
        }

        $action = $this->generateUrl(self::VARS['PREFIX'] . 'new');

        $entity = (new User())
        ->setDomain($domain)
        ->setSendEmail(true)
        ->setActive(true)
        ;

        $form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => false,
                'passRequired' => true,
                'showAutoreply' => false,
                'action' => $action // $this->generateUrl(self::VARS['PREFIX'] . 'new', ['id' => $domain->getId()]),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->formSubmit($form);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(self::VARS['BASEDIR'] . '_form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
            //'ajax' => true,
            'VARS' => self::VARS,
            'modalTitle' => 'User creation',
            'modalSize' => 'modal-xl',
        ));
    }


    #[Route(path: '/{id}/new/', name: 'admin_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, Domain $domain)
    {
        if (null==$domain) {
            $domain = $this->getUser()->getDomain();
            $origin = $this->generateUrl(self::VARS['PREFIX'] . 'index');
        } else {
            $origin = $this->generateUrl(self::VARS['PREFIX'] . 'admin_new', ['id' => $domain->getId()]);
        }

        $action = $this->generateUrl(
            self::VARS['PREFIX'] . 'admin_new',
            ['id' => $domain->getId(), 'origin' => $origin]
        );
        if (!$this->isGranted('ROLE_ADMIN')) {
            $action = $this->generateUrl(self::VARS['PREFIX'] . 'new', ['origin' => $origin]);
        }

        $entity = (new User())
        ->setDomain($domain)
        ->setSendEmail(true)
        ->setActive(true)
        ;

        $form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => false,
                'passRequired' => true,
                //'domainId' => $entity->getDomain()->getId(),
                'showAutoreply' => false,
                'action' => $action // $this->generateUrl(self::VARS['PREFIX'] . 'new', ['id' => $domain->getId()]),
            ]
        );

        $form->handleRequest($request);
        $render = [
            'template' => self::VARS['BASEDIR'] . '_form.html.twig',
            'args' => [
                'entity' => $entity,
                'form' => $form,
                'VARS' => self::VARS,
                'modalTitle' => 'User creation',
                'modalSize' => 'modal-xl',
            ]
        ];
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->repo->formSubmit($form);
                //$redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
                $redirectUrl = $this->getReferer($request);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                //self::VARS['BASEDIR'] . '_form.html.twig',
                $render['template'],
                $render['args'],
                new Response(null, 422)
            );
        }

        return $this->render(
            //self::VARS['BASEDIR'] . '_form.html.twig',
            $render['template'],
            $render['args']
        );
    }

    private function getReferer(Request $request, $activetab = 0)
    {
        $referer = $request->headers->get('referer');
        $origin = $_redirect = $request->headers->get('origin');

        if ($referer && $origin && str_starts_with($referer, $origin)) {
            // Quitar el origin de la URL
            $_redirect = substr($referer, strlen($origin));
        }
        $pos = strcspn($_redirect, "?#");
        $redirect = substr($_redirect, 0, $pos) ?: '/';

        //return $this->redirect($redirect. ($activetab > 0 ? "?activetab=$activetab" : ''));
        return $redirect . ($activetab > 0 ? "?activetab=$activetab" : '');
    }

    #[Route(path: '/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, User $entity): Response
    {
        $origin = $request->request->get('origin', null) ?? $request->query->get('origin', null);
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $name = $entity->getDomain()->getName();
            $this->repo->remove($entity, true);
            if ($this->isGranted('ROLE_ADMIN')) {
                //return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $name ]);
                return $this->redirect($this->getReferer($request));
            }
        }

        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route(path: '/ca/download/', name: 'ca_download', methods: ['GET', 'POST'])]
    public function serverDownload(Request $request, Certificate $util): Response
    {
        $entity = $this->getUser()->getDomain();
        $dtype = 'chain';
        if (($dtype == 'chain')) {
            //$this->addFlash('success', 'Se creo el certificado');
            return $util
            //->setDomain($domain)
            ->certDownload('ca', [$entity, $dtype]);
        } else {
            $this->addFlash('error', "Opción incorrecta $dtype");
        }
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
    }
}
