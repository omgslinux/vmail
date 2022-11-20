<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use App\Entity\Domain;
use App\Form\UserType;
use App\Repository\UserRepository as UR;

/**
 * User controller.
 *
 * @Route("/manage/user", name="manage_user_")
 */
class UserController extends AbstractController
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
        'modalSize' => 'modal-lg',
        'PREFIX' => 'manage_user_',
        'included' => 'user/_form',
        'tdir' => 'user',
        'BASEDIR' => 'user/',
        'modalId' => 'users',
    ];

    /**
     * Lists all user entities.
     *
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(Request $request, FormFactoryInterface $ff, UR $repo, $activetab = null)
    {
        $parent=$this->getUser()->getDomain();
        $users=$aliases=[];
        $reload = false;

        foreach ($parent->getUsers() as $user) {
            if ($user->isList()) {
                $aliases[]=$user;
            } else {
                $users[]=$user;
            }
        }
        $showDomain=($this->isGranted('ROLE_ADMIN'));
        $entity = (new User())
        ->setDomain($parent)
        ->setSendEmail(true)
        ->setActive(true)
        ;
        $userform = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain' => $showDomain,
                'showAutoreply' => false,
            ]
        );

        $userform->handleRequest($request);
        if ($userform->isSubmitted() && $userform->isValid()) {
            $repo->formSubmit($userform);

            $reload = true;
            //return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }


        // Pestaña Alias
        $alias = (new User())
        ->setDomain($parent)
        ->setList(true)
        ->setPassword(false)
        ;
        // createNamed es para dar un nombre al formulario para que no sean ambos 'user'
        $aliasform = $ff->createNamed(
            'alias',
            UserType::class,
            $alias,
            [
                'domainId' => $parent->getId(),
                'showAlias' => true,
            ]
        )
        ;
        // Fin pestaña aliases

        // Formulario de los alias
        $aliasform->handleRequest($request);
        if ($aliasform->isSubmitted() && $aliasform->isValid()) {
dump($alias);
            $repo->add($alias, true);

            $reload = true;
            $activetab = 'aliases';
        }

        if ($reload) {
            return $this->redirectToRoute(
                self::VARS['PREFIX'] . 'index',
                [
                    'id' => $entity->getId(),
                    'activetab' => $activetab,
                ]
            );
        } else {
            $activetab = $request->get('activetab')??'users';
        }

        return $this->render(self::VARS['BASEDIR'] . 'index.html.twig', array(
            'tabs' => self::TABS,
            'activetab' => $activetab,
            'users' => $users,
            'aliases' => $aliases,
            'user_form' => $userform->createView(),
            'alias_form' => $aliasform->createView(),
            'VARS' => self::VARS,
            'origin' => self::VARS['PREFIX'] . 'index',
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/byemail/{email}", name="show_byemail", methods={"GET", "POST"})
     */
    public function showByEmailAction(Request $request, UR $ur, $email)
    {
        $t=explode('@', $email);
        $em = $this->getDoctrine()->getManager();
        $parent=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        if (null==$parent) {
            $this->addFlash('error', 'Correo incorrecto');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }
        $entity=$em->getRepository(User::class)->findOneBy(['domain' => $parent, 'name' => $t[0]]);
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
            $ur->formSubmit($form);

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
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET"})
     */
    public function show(User $user)
    {

        return $this->render(self::VARS['BASEDIR'] . 'show.html.twig', array(
            'user' => $user,
            'VARS' => self::VARS,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit/{origin}", name="edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UR $ur, User $entity, $origin = null)
    {
        $user_form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => false,
                //'domainId' => $entity->getDomain()->getId(),
                'showAutoreply' => null!==$entity->getReply(),
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'edit', ['id' => $entity->getId()]),
            ]
        );
        $session = $request->getSession();
        if ($origin) {
            $session->remove('useredit');
            $session->set('useredit', $origin);
        }
        $user_form->handleRequest($request);

        if ($user_form->isSubmitted() && $user_form->isValid()) {
            $origin = $session->get('useredit');
            $session->remove('useredit');

            $ur->formSubmit($user_form);

            if (null==$origin) {
                return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
            } else {
                return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $origin ]);
                //return $this->redirectToRoute('admin_domain_show', ['id' => $entity->getDomain()->getId()]);
                //return $this->redirectToRoute(self::PREFIX . 'edit', array('id' => $entity->getId()));
            }
        }

        return $this->render(self::VARS['BASEDIR'] . '_form.html.twig', array(
            'entity' => $entity,
            'form' => $user_form->createView(),
            'delete_form' => true,
            'VARS' => self::VARS,
        ));
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"POST"})
     */
    public function delete(Request $request, User $entity, UR $repo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
