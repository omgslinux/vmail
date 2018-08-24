<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use VmailBundle\Entity\Alias;
use VmailBundle\Entity\Domain;

/**
 * Alias controller.
 *
 * @Route("/manage/alias")
 */
class AliasController extends Controller
{
    /**
     * Lists all alias entities.
     *
     * @Route("/index/{id}", name="manage_alias_index")
     * @Method("GET")
     */
    public function indexAction(Domain $domain)
    {
        if (!($this->isGranted('ROLE_ADMIN')) && $domain->getId()===0) {
            return $this->redirectToRoute(
                'manage_domain_alias_index',
                [
                    'id' => $this->getUser()->getDomain()->getId()
                ]
            );
        }

        $em = $this->getDoctrine()->getManager();
        $aliases = $em->getRepository('VmailBundle:User')->findBy(['domain' => $domain, 'list' => 1]);

        return $this->render('@vmail/alias/index.html.twig', array(
            'items' => $aliases,
        ));
    }

    /**
     * Lists all alias entities.
     *
     * @Route("/index/{id}", name="manage_domain_alias_index")
     * @Method("GET")
     */
    public function domainindexAction(Domain $domain)
    {
        if ($domain->getId==0) {
            return $this->redirectToRoute('manage_alias_index', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $aliases = $em->getRepository('VmailBundle:User')->findBy(['domain' => $domain, 'list' => 1]);

        return $this->render('@vmail/alias/index.html.twig', array(
            'domain' => $domain,
            'items' => $aliases,
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new/{id}", name="manage_alias_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Domain $domain)
    {
        if (!($this->isGranted('ROLE_ADMIN')) && $domain->getId()===0) {
            return $this->redirectToRoute('manage_domain_alias_new', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $em = $this->getDoctrine()->getManager();

        $alias = new User();
        $alias
          ->setDomain($domain)
          ->setList(true)
          ->setPassword(false)
        ;
        $form = $this->createForm(
            'VmailBundle\Form\UserType',
            $alias,
            [
                'domain' => $domain->getId(),
                'showList' => true
            ]
        )
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($alias);
            $em->flush();

            return $this->redirectToRoute('manage_alias_show', array('id' => $alias->getId()));
        }

        return $this->render(
            '@vmail/alias/edit.html.twig',
            [
                'domain' => $domain,
                'form' => $form->createView(),
                'title' => 'Alias creation'
            ]
        );
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new/{id}", name="manage_domain_alias_new")
     * @Method({"GET", "POST"})
     */
    public function domainnewAction(Request $request, Domain $domain)
    {
        if ($domain->getId()===0) {
            return $this->redirectToRoute('manage_alias_new', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $alias = new User();
        $alias
        ->setDomain($domain)
        ->setList(true)
        ->setPassword(false)
        ;
        $form = $this->createForm(
            'VmailBundle\Form\UserType',
            $alias,
            [
                'domain' => $domain->getId(),
                'showList' => true,
            ]
        )
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($alias);
            $em->flush();

            return $this->redirectToRoute('manage_alias_show', array('id' => $alias->getId()));
        }

        return $this->render('@vmail/alias/edit.html.twig', array(
            'domain' => $domain,
            'form' => $form->createView(),
            'title' => 'Alias creation'
        ));
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/show/{id}", name="manage_alias_show")
     * @Method("GET")
     */
    public function showAction(User $alias)
    {

        return $this->render('@vmail/alias/show.html.twig', array(
            'item' => $alias,
        ));
    }

    /**
     * Displays a form to edit an existing alias entity.
     *
     * @Route("/{id}/edit", name="manage_alias_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $alias)
    {
        $domain=$alias->getDomain();
        $deleteForm = $this->createDeleteForm($alias);
        $editForm = $this->createForm(
            'VmailBundle\Form\UserType',
            $alias,
            [
                'domain' => $domain->getId(),
                'showAlias' => true,
                'showList' => true
            ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em=$this->getDoctrine()->getManager();
            $em->persist($alias);
            $em->flush();

            return $this->redirectToRoute('manage_alias_show', array('id' => $alias->getId()));
        }

        return $this->render('@vmail/alias/edit.html.twig', array(
            'domain' => $domain,
            'title' => 'Alias edit',
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'jsfieldname' => 'alias',
            'jsfieldlabel' => 'correo'
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/delete/{id}", name="manage_alias_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $alias)
    {
        $form = $this->createDeleteForm($alias);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($alias);
            $em->flush();
        }

        return $this->redirectToRoute('manage_alias_index');
    }

    /**
     * Creates a form to delete an alias entity.
     *
     * @param User $alias The alias
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $alias)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('manage_alias_delete', array('id' => $alias->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
