<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    private function getReferer(Request $request, $activetab = 0)
    {
        $referer = $request->headers->get('referer');
        $origin = $_redirect = $request->headers->get('origin');

        if ($referer && $origin && str_starts_with($referer, $origin)) {
            // Quitar el origin de la URL
            $_redirect = substr($referer, strlen($origin));
        }
        $pos = strcspn($_redirect, "?#");
        $redirect = substr($_redirect, 0, $pos) ?: '/';

        //return $this->redirect($redirect. ($activetab > 0 ? "?activetab=$activetab" : ''));
        return $redirect . ($activetab > 0 ? "?activetab=$activetab" : '');
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
        $render = [
            'user' => $user,
            'form' => $form,
            'PREFIX' => self::PREFIX,
        ]
        ;

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $user->setReply($reply);
                $ur->add($user, true);
                /*
                $redirectUrl = $origin;
                if (null==$origin) {
                    if ($this->isGranted('ROLE_ADMIN')) {
                        $redirectUrl = $this->generateUrl('admin_domain_showbyname', ['name' => $user->getDomain()->getName()]);
                    } elseif ($this->isGranted('ROLE_MANAGER')) {
                        $redirectUrl = $this->generateUrl('manage_user_index');
                    } else {
                        $redirectUrl = $this-generateUrl('user_self_index');
                    }
                }
                */
                $redirectUrl = $this->getReferer($request);
                return new JsonResponse([
                    'success' => true,
                    'redirectUrl' => $redirectUrl
                ]);
            }
            return $this->render(
                'reply/_form.html.twig',
                $render,
                new Response(null, 422)
            );
        }

        return $this->render(
            'reply/_form.html.twig',
            $render
        );
    }
}
