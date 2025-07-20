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
use App\Repository\UserRepository as UR;

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

    #[Route(path: '/show/{id}', name: 'show', methods: ['GET'])]
    public function showAction(User $user = null)
    {
        if (null==$user) {
            $user=$this->getUser();
        }

        return $this->render('reply/show.html.twig', array(
            'user' => $user,
            'PREFIX' => self::PREFIX,
        ));
    }

    #[Route(path: '/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, UR $ur, ?User $user = null)
    {
        $origin = $request->request->get('origin', null) ?? $request->query->get('origin', null);
        if (null == $user) {
            $user=$this->getUser();
        }
        $reply=$user->getReply();
        if (null==$reply) {
            $reply = new Autoreply();
            $reply->setUser($user);
        }
        $options = ['id' => $user->getId()];
        if (null!=$origin) {
            $options['origin'] = $origin;
        }
        $form = $this->createForm(
            AutoreplyType::class,
            $reply,
            [
                'action' => $this->generateUrl(self::PREFIX . 'edit', $options),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setReply($reply);
            $ur->add($user, true);
            if (null==$origin) {
                if ($this->isGranted('ROLE_ADMIN')) {
                    return $this->redirectToRoute('admin_domain_showbyname', ['name' => $user->getDomain()->getName()]);
                } elseif ($this->isGranted('ROLE_MANAGER')) {
                    return $this->redirectToRoute('manage_user_index');
                } else {
                    return $this->redirectToRoute('user_self_index');
                }
            } else {
                return $this->redirectToRoute($origin);
            }
        }

        return $this->render('reply/_form.html.twig', array(
            'user' => $user,
            'form' => $form,
            'PREFIX' => self::PREFIX,
        ));
    }
}
