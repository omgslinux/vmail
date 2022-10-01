<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Form\UserType;
use App\Repository\UserRepository as UR;

/**
 * User controller.
 *
 * @Route("/user/self", name="user_self_")
 */
class UserController extends AbstractController
{
    const PREFIX = 'user_self_';

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="show", methods={"GET"})
     */
    public function showAction()
    {
        $user=$this->getUser();

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, UR $ur)
    {
        $user=$this->getUser();
        $formOptions['showAutoreply'] = null!==$user->getReply();
        $form = $this->createForm(UserType::class, $user, $formOptions);
        $form
          ->remove('name')
          ->remove('admin')
          ->remove('quota')
          ->remove('domain');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ur->formSubmit($form);

            return $this->redirectToRoute(self::PREFIX . 'show');
        }

        return $this->render('user/self.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ));
    }
}
