<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\Autoreply;
use App\Form\AutoreplyType;

/**
 * Autoreply controller.
 *
 * @Route("/user/autoreply", name="user_autoreply_")
 */
class AutoreplyController extends AbstractController
{
    const PREFIX = 'user_autoreply_';

    /**
     * Creates a new Domain entity.
     *
     * @Route("/new/{id}", name="new", methods={"GET", "POST"})
     */
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
     *
     * @Route("/show/{id}", name="show", methods={"GET"})
     */
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
     *
     * @Route("/edit", name="edit", methods={"GET", "POST"})
     */
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
}
