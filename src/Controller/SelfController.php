<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Entity\Domain;
use App\Entity\Autoreply;
use App\Form\UserType;
use App\Form\AutoreplyType;
use App\Form\ManagePasswordType;
use App\Repository\UserRepository as UR;

/**
 * User controller.
 */
#[Route(path: '/user/self', name: 'user_self_')]
class SelfController extends AbstractController
{
    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => 'user_self_',
        'BASEDIR' => 'user/',
        'modalId' => 'self',
        'included' => 'user/self',
    ];

    /**
     * Lists pass and reply
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, UR $ur): Response
    {
        $entity = $this->getUser();

        $passform = $this->createForm(ManagePasswordType::class, $entity);
        $passform->handleRequest($request);

        if ($passform->isSubmitted() && $passform->isValid()) {
            $ur->formSubmit($passform);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        if (null==$entity->getReply()) {
            $reply = (new Autoreply())
            ->setUser($entity);
        } else {
            $reply = $entity->getReply();
        }

        $replyform = $this->createForm(AutoreplyType::class, $reply);
        $replyform->handleRequest($request);

        if ($replyform->isSubmitted() && $replyform->isValid()) {
            $entity->setReply($reply);
            $ur->add($entity, true);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(self::VARS['BASEDIR'] . '/selfindex.html.twig', array(
            'user' => $entity,
            'passform' => $passform->createView(),
            'replyform' => $replyform->createView(),
            'VARS' => self::VARS,
        ));
    }

    /**
     * Finds and displays a user entity.
     */
    #[Route(path: '/show', name: 'show', methods: ['GET'])]
    public function showAction()
    {
        $user=$this->getUser();

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'VARS' => self::VARS,
        ));
    }

    /**
     * Displays a form to change the password.
     */
    #[Route(path: '/pass', name: 'pass', methods: ['GET', 'POST'])]
    public function password(Request $request, UR $ur)
    {
        $user=$this->getUser();
        $form = $this->createForm(
            ManagePasswordType::class,
            $user,
            [
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'pass'),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ur->formSubmit($form);

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/_pass.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     */
    #[Route(path: '/reply', name: 'reply', methods: ['GET', 'POST'])]
    public function reply(Request $request, UR $ur)
    {
        $user=($this->getUser())
        ->setSendEmail(false);
        $formOptions['showAutoreply'] = null!==$user->getReply();
        $form = $this->createForm(
            UserType::class,
            $user,
            [
                'showAutoreply' => null!==$user->getReply(),
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'reply'),
            ]
        );
        $form
          ->remove('name')
          ->remove('fullname')
          ->remove('active')
          ->remove('admin')
          ->remove('quota')
          ->remove('domain')
          ->remove('sendEmail')
          ->add('sendEmail', HiddenType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ur->formSubmit($form);

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/_self.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }
}
