<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\Autoreply;
use App\Form\AutoreplyType;
use App\Repository\AutoreplyRepository as REPO;

/**
 * Autoreply controller.
 */
#[Route(path: '/user/autoreply', name: 'user_autoreply_')]
class AutoreplyController extends AbstractController
{
    const PREFIX = 'user_autoreply_';

    public function __construct(private REPO $repo)
    {
    }

    /**
     * Creates a new Domain entity.
     */
    #[Route(path: '/new/{id}', name: 'new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, User $user = null)
    {
        if (is_null($user)) {
            $user=$this->getUser();
        }
        $reply=new Autoreply;
        $reply->setUser($user);
        $form = $this->createForm(AutoreplyType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($reply);
            $em->flush();
            if ($this->isGranted('ROLE_MANAGER')) {
                return $this->redirectToRoute('manage_user_edit', ['id' => $user->getId()]);
            } else {
                return $this->redirectToRoute('user_self_edit');
            }
        }

        return $this->render('reply/new.html.twig', array(
            //'item' => $reply,
            'user' => $user,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ));
    }


    /**
     * Finds and displays a user entity.
     */
    #[Route(path: '/show/{id}', name: 'show', methods: ['GET'])]
    public function showAction(User $user = null)
    {
        if (is_null($user)) {
            $user=$this->getUser();
        }

        return $this->render('reply/show.html.twig', array(
            'user' => $user,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     */
    #[Route(path: '/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request)
    {
        $user=$this->getUser();
        $em = $this->getDoctrine()->getManager();
        $reply=$em->getRepository(Autoreply::class)->findOneBy(['user' => $user]);
        if (empty($reply)) {
            return $this->redirectToRoute(self::PREFIX . 'new');
        }
        $editForm = $this->createForm(AutoreplyType::class, $reply);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($reply);
            $em->flush();
            return $this->redirectToRoute(self::PREFIX . 'show');
        }

        return $this->render('reply/edit.html.twig', array(
            'item' => $reply,
            'user' => $user,
            'form' => $editForm->createView(),
            'PREFIX' => self::PREFIX,
        ));
    }

    // Para llamar de forma genérica desde otro controlador
    #[Route(path: '/load/{id}/{form}', name: 'load', methods: ['GET', 'POST'])]
    public function loadForm(Request $request, User $user, Symfony\Component\Form\FormView $form = null)
    {
        dump($user, $form);
        if (null!=$form) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $reply = $form->getData();
                dd($form, $reply);
                $this->repo->add($reply, true);
                if ($this->isGranted('ROLE_MANAGER')) {
                    return $this->redirectToRoute('manage_user_edit', ['id' => $reply->getUser()->getId()]);
                }
            }
            return $this->redirectToRoute('user_self_edit');
        }

        $reply = $user->getReply();
        if (null==$reply) {
            $reply = new Autoreply();
        }
        $reply->setUser($user);

        $form = $this->createForm(AutoreplyType::class, $reply);
dump($form);
        return $this->render('reply/_form.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
