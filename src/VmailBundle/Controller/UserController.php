<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use VmailBundle\Entity\Domain;

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

        return $this->render('@vmail/user/show.html.twig', array(
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
        $formOptions['showAutoreply']=($user->getReplys()?true:false);
        $form = $this->createForm('VmailBundle\Form\UserType', $user, $formOptions);
        $form
          ->remove('user')
          ->remove('admin')
          ->remove('domain');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $encoder = $this->get('security.password_encoder');
                $encodedPassword = $encoder->encodePassword($user, $user->getPlainpassword());
                //$encodedPassword = $encoder->encodePassword($user, $editForm->get('plainPassword')->getData());
                $user->setPassword($encodedPassword);
            }
            $em->persist($user);
            $em->flush();
            //$this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('user_self_show');
        }

        return $this->render('@vmail/user/edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

}
