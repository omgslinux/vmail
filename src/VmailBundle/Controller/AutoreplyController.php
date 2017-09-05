<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Autoreply;

/**
 * Autoreply controller.
 *
 * @Route("/user/autoreply")
 */
class AutoreplyController extends Controller
{

    /**
     * Creates a new Domain entity.
     *
     * @Route("/new/{id}", name="user_autoreply_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, User $user=null)
    {
        if (is_null($user)) {
            $user=$this->getUser();
        }
        $reply=new Autoreply;
        $reply->setUser($user);
        $form = $this->createForm('VmailBundle\Form\AutoreplyType', $reply);
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

        return $this->render('@vmail/reply/new.html.twig', array(
            //'item' => $reply,
            'user' => $user,
            'form' => $form->createView(),
        ));
    }


    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/{id}", name="user_autoreply_show")
     * @Method("GET")
     */
    public function showAction(User $user=null)
    {
        if (is_null($user)) {
            $user=$this->getUser();
        }

        return $this->render('@vmail/reply/show.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit", name="user_autoreply_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request)
    {
        $user=$this->getUser();
        $em = $this->getDoctrine()->getManager();
        $reply=$em->getRepository('VmailBundle:Autoreply')->findOneBy(['user' => $user]);
        if (empty($reply)) {
            return $this->redirectToRoute('user_autoreply_new');
        }
        $editForm = $this->createForm('VmailBundle\Form\AutoreplyType', $reply);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($reply);
            $em->flush($reply);
            //$this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('user_autoreply_show');
        }

        return $this->render('@vmail/reply/edit.html.twig', array(
            'item' => $reply,
            'user' => $user,
            'form' => $editForm->createView(),
        ));
    }

}
