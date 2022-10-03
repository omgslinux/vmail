<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        'modalSize' => 'modal-lg',
        'PREFIX' => 'manage_user_',
        'included' => 'user/_form',
        'tdir' => 'user',
        'modalId' => 'users',
    ];

    /**
     * Lists all user entities.
     *
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(Request $request, UR $repo, $activetab = 'users')
    {

        $parent=$this->getUser()->getDomain();
        $users=$lists=[];
        $reload = false;

        foreach ($parent->getUsers() as $entity) {
            if ($entity->isList()) {
                $lists[]=$entity;
            } else {
                $users[]=$entity;
            }
        }
        $showDomain=($this->isGranted('ROLE_ADMIN'));
        $entity = (new User())
        ->setDomain($parent)
        ->setSendEmail(true)
        ->setActive(true)
        ;

        $form = $this->createForm(UserType::class, $entity, ['showDomain' => $showDomain]);


        // Pestaña Alias
        $alias = (new User())
        ->setDomain($parent)
        ->setList(false)
        ->setPassword(false)
        ;
        $aliasform = $this->createForm(
            UserType::class,
            $alias,
            [
                'domain' => $parent->getId(),
                'showList' => true,
            ]
        )
        ;
        // Fin pestaña aliases

        // Pestaña listas
        $list = (new User())
        ->setDomain($parent)
        ->setList(true)
        ->setPassword(false)
        ;
        $listform = $this->createForm(
            UserType::class,
            $list,
            [
                'domain' => $parent->getId(),
                'showList' => true,
            ]
        )
        ;
        // Fin pestaña listas

        // Formulario de los alias
        $aliasform->handleRequest($request);
        if ($aliasform->isSubmitted() && $aliasform->isValid()) {
            $ur->add($alias, true);

            $reload = true;
            $activetab = 'aliases';
        }


        // Formulario de las listas
        $listform->handleRequest($request);
        if ($listform->isSubmitted() && $listform->isValid()) {
            $ur->add($list, true);

            $reload = true;
            $activetab = 'lists';
        }

        if ($reload) return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', ['id' => $entity->getId()]);


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repo->formSubmit($form);
            //$users[] = $form->getData();

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('user/index.html.twig', array(
            'parent' => $parent,
            'tabs' => self::TABS,
            'activetab' => $activetab,
            'users' => $users,
            'lists' => $lists,
            'user_form' => $form->createView(),
            'VARS' => self::VARS,
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

        $form = $this->createForm(UserType::class, $entity, ['showDomain' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ur->formSubmit($form);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', ['id' => $user->getId()]);
        }

        return $this->render('user/show.html.twig', array(
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
    public function showAction(User $user)
    {

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'VARS' => self::VARS,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, UR $ur, User $entity, $origin = null)
    {
        $user_form = $this->createForm(
            UserType::class,
            $entity,
            [
                'showDomain'  => $this->isGranted('ROLE_ADMIN'),
                'showAutoreply' => null!==$entity->getReply(),
            ]
        );

        $user_form->handleRequest($request);

        if ($user_form->isSubmitted() && $user_form->isValid()) {
            $ur->formSubmit($user_form);

            if (null==$origin) {
                return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
            } else {
                return $this->redirectToRoute('admin_domain_show', ['id' => $entity->getDomain()->getId()]);
                //return $this->redirectToRoute(self::PREFIX . 'edit', array('id' => $entity->getId()));
            }
        }

        return $this->render('user/_form.html.twig', array(
            'entity' => $entity,
            'form' => $user_form->createView(),
            'VARS' => self::VARS,
        ));
    }

    /**
     * @Route("/{id}", name="delete", methods={"POST"})
     */
    public function delete(Request $request, User $entity, UR $repo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }
}
