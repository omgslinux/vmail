<?php

namespace VmailBundle\Controller\Admin;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use VmailBundle\Entity\Domain;
use VmailBundle\Utils\UserForm;

/**
 * User controller.
 *
 * @Route("/manage/user", name="manage_user_")
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     * @Route("/", name="index", methods={"GET"})
     */
    public function indexAction()
    {

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
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, UserForm $u)
    {
        $user = new User();
        $showDomain=($this->isGranted('ROLE_ADMIN'));
        $user
        ->setDomain($this->getUser()->getDomain())
        ->setSendEmail(true)
        ->setActive(true)
        ;

        $form = $this->createForm('VmailBundle\Form\UserType', $user, ['showDomain' => $showDomain]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u->setUser($user);
            $u->formSubmit($form);

            return $this->redirectToRoute('manage_user_show', array('id' => $user->getId()));
        }

        return $this->render('@vmail/user/form.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/domain/{id}", name="domain_show", methods={"GET"})
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
     * @Route("/show/byemail/{email}", name="show_byemail", methods={"GET"})
     */
    public function showByEmailAction($email)
    {
        $t=explode('@', $email);
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository(Domain::class)->findOneBy(['name' => $t[1]]);
        $user=$em->getRepository(User::class)->findOneBy(['domain' => $domain, 'name' => $t[0]]);
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('@vmail/user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET"})
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
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, UserForm $u, User $user, $domain = false)
    {
        $deleteForm = $this->createDeleteForm($user);
        $form = $this->createForm(
            'VmailBundle\Form\UserType',
            $user,
            [
                'showDomain'  => $this->isGranted('ROLE_ADMIN'),
                'showAutoreply' => null!==$user->getReply(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u->setUser($user);
            $u->formSubmit($form);

            if ($domain) {
                return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getId()));
            } else {
                return $this->redirectToRoute('manage_user_edit', array('id' => $user->getId()));
            }
        }

        return $this->render('@vmail/user/form.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     * @Route("/{id}", name="delete", methods={"DELETE"})
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
