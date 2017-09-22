<?php

namespace VmailBundle\Controller\Admin;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use VmailBundle\Entity\Domain;

/**
 * User controller.
 *
 * @Route("/manage/user")
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="manage_user_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        //$em = $this->getDoctrine()->getManager();

        $domain=$this->getUser()->getDomain();
        $users=[];
        $lists=[];

        foreach ($domain->getUsers() as $user) {
            if ($user->isList()) {
                $lists[]=$user;
            } else {
                $users[]=$user;
            }
        }

        return $this->render('@vmail/user/index.html.twig', array(
            'domain' => $domain,
            'users' => $users,
            'lists' => $lists
        ));
    }

    /**
     * Creates a new user entity.
     *
     * @Route("/new", name="manage_user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $showDomain=($this->isGranted('ROLE_ADMIN'));
        $user->setDomain($this->getUser()->getDomain());

        $form = $this->createForm('VmailBundle\Form\UserType', $user, ['showDomain' => $showDomain]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $encoder = $this->get('security.password_encoder');
            $encodedPassword = $encoder->encodePassword($user, $user->getPlainpassword());
            $user->setPassword($encodedPassword);
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('manage_user_show', array('id' => $user->getId()));
        }

        return $this->render('@vmail/user/edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/domain/{id}", name="manage_user_domain_show")
     * @Method("GET")
     */
    public function showDomainAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('@vmail/user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
            'domain' => true,
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/byemail/{email}", name="manage_user_show_byemail")
     * @Method("GET")
     */
    public function showByEmailAction($email)
    {
        $t=explode('@', $email);
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository('VmailBundle:Domain')->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository('VmailBundle:User')->findOneBy(['domain' => $domain, 'name' => $t[0]]);
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('@vmail/user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/byid/{id}", name="manage_user_show")
     * @Method("GET")
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('@vmail/user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="manage_user_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $user, $domain=false)
    {
        $deleteForm = $this->createDeleteForm($user);
        $form = $this->createForm('VmailBundle\Form\UserType', $user,
          [
            'showDomain'  => $this->isGranted('ROLE_ADMIN'),
            'showAutoreply' => (count($user->getReply())?true:false),
          ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $encoder = $this->get('security.password_encoder');
                $encodedPassword = $encoder->encodePassword($user, $user->getPlainpassword());
                $user->setPassword($encodedPassword);
            }
            $em->persist($user);
            $em->flush();
            if ($domain) {
                return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getId()));
            } else {
                return $this->redirectToRoute('manage_user_edit', array('id' => $user->getId()));
            }
        }

        return $this->render('@vmail/user/edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="manage_user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush($user);
        }

        return $this->redirectToRoute('manage_user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('manage_user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
