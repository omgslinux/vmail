<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\Autoreply;
use VmailBundle\Entity\AutoreplyCache;

/**
 * AutoreplyCache controller.
 *
 * @Route("/user/autoreplycache", name="user_autoreplycache_")
 */
class AutoreplyCacheController extends Controller
{

    /**
     * Creates a new Domain entity.
     *
     * @Route("/new/{id}", name="new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $sender, $recipient, $body)
    {
        //$body=file_get_contents('php://STDIN');
        $t=explode('@', $recipient);

        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository('VmailBundle:Domain')->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository('VmailBundle:User')->findOneBy(['domain' => $domain, 'user' => $t[0]]);
        $reply=$em->getRepository('VmailBundle:Autoreply')->findOneBy(['user' => $user, 'active' => true]);

        $deliverreply=false;
        $date=new \DateTime();
        if ($request->get('demo')) {
            $deliverreply=true;
        } else {
            $this->get('vmail:deliver')->manualDeliver($sender, $recipient, $body);
            if (!empty($reply)) {
                $date=new \DateTime();
                if ($date>$reply->getStartDate() && $date<$reply->getEndDate()) {
                    $lastcache=$em->getRepository('VmailBundle:AutoreplyCache')->findBy(
                        [
                            'user' => $user
                        ],
                        [
                            'order' => 'DESC'
                        ],
                        1
                    );
                    $config=$this->get('vmail:config');
                    $delay=$config->findParameter('autoreply_delay');
                    $lastcache->modify('+'.$delay.' h');
                    if ($lastcache->format('Y-m-d H:i:s')>$date) {
                        $cache=new AutoreplyCache;
                        $cache->setReply($reply);
                        $cache->setSender($sender);
                        $em->persist($cache);
                        $em->flush();

                        $deliverreply=true;
                    }
                }
            }
        }

        if ($deliverreply===true) {
            $this->sendReply($reply, $sender);
        }
        return $this->render(
            '@vmail/reply/show.html.twig',
            [
                'item' => $reply
            ]
        );


        return $this->redirectToRoute('user_autoreply_show');
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="show", methods={"GET"})
     */
    public function showAction(AutoreplyCache $cache)
    {
        $user=$this->getUser();

        return $this->render('@vmail/reply/show.html.twig', array(
            'item' => $cache,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, AutoreplyCache $cache)
    {
        $editForm = $this->createForm('VmailBundle\Form\AutoreplyType', $cache);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($cache);
            $em->flush();
            return $this->redirectToRoute('user_autoreplycache_show');
        }

        return $this->render('@vmail/reply/edit.html.twig', array(
            'item' => $cache,
            'form' => $editForm->createView(),
        ));
    }
}
