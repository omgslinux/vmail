<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Domain;
use AppBundle\Entity\Autoreply;
use AppBundle\Entity\AutoreplyCache;

/**
 * AutoreplyCache controller.
 *
 * @Route("/user/autoreplycache")
 */
class AutoreplyCacheController extends Controller
{

    /**
     * Creates a new Domain entity.
     *
     * @Route("/new/{id}", name="user_autoreplycache_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $sender, $recipient, $body)
    {
        $t=explode('@', $recipient);
        //dump($email);
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository('AppBundle:Domain')->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository('AppBundle:User')->findOneBy(['domain' => $domain, 'user' => $t[0]]);
        $reply=$em->getRepository('AppBundle:Autoreply')->findOneBy(['user' => $user, 'active' => true]);
        if (!empty($reply)) {

          $date=new \DateTime();
          if ($date>$reply->getStartDate() && $date<$reply->getEndDate()) {
              $lastcache=$em->getRepository('AppBundle:AutoreplyCache')->findBy(['user'=>$user], ['order' => 'DESC'], 1);
              $config=$this->get('app:config');
              $delay=$config->findParameter('autoreply_delay')->getValue();
              $lastcache->modify('+'.$delay.' h');
              if ($lastcache->format('Y-m-d H:i:s')>$date) {
                $cache=new AutoreplyCache;
                $cache->setReply($reply);
                $cache->setSender($sender);
                $em->persist($cache);
                $em->flush();

                $this->sendReply($reply, $sender);

                return $this->render('reply/newcache.html.twig', array(
                    'item' => $reply,
                ));

              }
          }
          return $this->render('reply/show.html.twig', [
            'item' => $reply
          ]
        );
        }

        $this->get('app:deliver')->deliverMail($sender, $recipient,$body);

        return $this->redirectToRoute('user_autoreply_show');
    }

    public function sendReply(Autoreply $reply, $body)
    {

    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show", name="user_autoreplycache_show")
     * @Method("GET")
     */
    public function showAction(AutoreplyCache $cache)
    {
        $user=$this->getUser();

        return $this->render('reply/show.html.twig', array(
            'item' => $cache,
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/edit", name="user_autoreplycache_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, AutoreplyCache $cache)
    {
        $editForm = $this->createForm('AppBundle\Form\AutoreplyType', $cache);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($cache);
            $em->flush();
            //$this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('user_autoreplycache_show');
        }

        return $this->render('reply/edit.html.twig', array(
            'item' => $reply,
            'form' => $editForm->createView(),
        ));
    }

}
