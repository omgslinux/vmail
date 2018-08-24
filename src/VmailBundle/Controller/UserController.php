<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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
        $formOptions['showAutoreply']=($user->getReply()?true:false);
        $form = $this->createForm('VmailBundle\Form\UserType', $user, $formOptions);
        $form
          ->remove('name')
          ->remove('admin')
          ->remove('quota')
          ->remove('domain');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u=$this->get('vmail.userform');
            $u->setUser($user);
            $u->formSubmit($form);

            return $this->redirectToRoute('user_self_show');
        }

        return $this->render('@vmail/user/self.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }
}
