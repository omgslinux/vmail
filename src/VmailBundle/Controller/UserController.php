<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use VmailBundle\Entity\Domain;
use VmailBundle\Utils\UserForm;

/**
 * User controller.
 *
 * @Route("/user/self", name="user_self_")
 */
class UserController extends Controller
{

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="show", methods={"GET"})
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
     * @Route("/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, UserForm $u)
    {
        $user=$this->getUser();
        $formOptions['showAutoreply'] = null!==$user->getReply();
        $form = $this->createForm('VmailBundle\Form\UserType', $user, $formOptions);
        $form
          ->remove('name')
          ->remove('admin')
          ->remove('quota')
          ->remove('domain');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
