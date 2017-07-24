<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Domain;

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
        $em = $this->getDoctrine()->getManager();

        $user=$this->getUser();

        $users = $em->getRepository('AppBundle:User')->findBy(['domain' => $user->getDomain()]);

        return $this->render('user/index.html.twig', array(
            'users' => $users,
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
        $form = $this->createForm('AppBundle\Form\UserType', $user);
        if (!$this->isGranted('ROLE_ADMIN')) {
            $usert=$this->getUser();
            $user->setDomain($usert->getDomain());
            $form->remove('domain');
        }
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

        return $this->render('user/new.html.twig', array(
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

        return $this->render('user/show.html.twig', array(
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
        dump($email);
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository('AppBundle:Domain')->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository('AppBundle:User')->findOneBy(['domain' => $domain, 'user' => $t[0]]);
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/self", name="user_self_show")
     * @Method("GET")
     */
    public function showSelfAction()
    {
        $user=$this->getUser();
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
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

        return $this->render('user/show.html.twig', array(
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
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        if (!$this->isGranted('ROLE_ADMIN')) {
            $usert=$this->getUser();
            $user->setDomain($usert->getDomain());
            $editForm->remove('domain');
        }
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $plainPassword = $editForm->get('plainpassword')->getData();
            if (!empty($plainPassword)) {
                $encoder = $this->get('security.password_encoder');
                $encodedPassword = $encoder->encodePassword($user, $user->getPlainpassword());
                $encodedPassword = $encoder->encodePassword($user, $editForm->get('plainpassword')->getData());
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

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
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
