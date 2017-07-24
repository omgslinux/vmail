<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Domain;

/**
 * User controller.
 *
 * @Route("/user/self")
 */
class UserController extends Controller
{

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="user_self_show")
     * @Method("GET")
     */
    public function showAction()
    {
        $user=$this->getUser();

        return $this->render('user/show.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit", name="user_self_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request)
    {
        $user=$this->getUser();
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm
          ->remove('user')
          ->remove('admin')
          ->remove('domain');
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $plainPassword = $editForm->get('plainpassword')->getData();
            if (!empty($plainPassword)) {
                $encoder = $this->get('security.password_encoder');
                $encodedPassword = $encoder->encodePassword($user, $user->getPlainpassword());
                $encodedPassword = $encoder->encodePassword($user, $editForm->get('plainpassword')->getData());
                $user->setPassword($encodedPassword);
            }
            $em->persist($user);
            $em->flush($user);
            //$this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('user_self_show');
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

}
