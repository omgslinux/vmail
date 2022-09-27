<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Form\UserType;

/**
 * Alias controller.
 *
 * @Route("/manage/alias", name="manage_alias_")
 */
class AliasController extends AbstractController
{
    const PREFIX = 'manage_alias_';
    /**
     * Lists all alias entities.
     *
     * @Route("/index/{id}", name="index", methods={"GET"})
     */
    public function indexAction(Domain $domain)
    {
        if (!($this->isGranted('ROLE_ADMIN')) && $domain->getId()===0) {
            return $this->redirectToRoute(
                'manage_domain_alias_index',
                [
                    'id' => $this->getUser()->getDomain()->getId(),
                    'PREFIX' => self::PREFIX,
                ]
            );
        }

        $em = $this->getDoctrine()->getManager();
        $aliases = $em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 1]);

        return $this->render('alias/index.html.twig', array(
            'items' => $aliases,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Lists all alias entities.
     *
     * @Route("/index/{id}", name="domain_index", methods={"GET"})
     */
    public function domainIndexAction(Domain $domain)
    {
        if ($domain->getId==0) {
            return $this->redirectToRoute(self::PREFIX . 'index', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $aliases = $em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 1]);

        return $this->render('alias/index.html.twig', array(
            'domain' => $domain,
            'items' => $aliases,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new/{id}", name="new", methods={"GET", "POST"})
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
            UserType::class,
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

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $alias->getId()));
        }

        return $this->render(
            'alias/edit.html.twig',
            [
                'domain' => $domain,
                'form' => $form->createView(),
                'title' => 'Alias creation',
                'PREFIX' => self::PREFIX,
            ]
        );
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new/{id}", name="domain_new", methods={"GET", "POST"})
     */
    public function domainNewAction(Request $request, Domain $domain)
    {
        if ($domain->getId()===0) {
            return $this->redirectToRoute(self::PREFIX . 'new', ['id' => $this->getUser()->getDomain()->getId()]);
        }

        $alias = new User();
        $alias
        ->setDomain($domain)
        ->setList(true)
        ->setPassword(false)
        ;
        $form = $this->createForm(
            UserType::class,
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

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $alias->getId()));
        }

        return $this->render('alias/edit.html.twig', array(
            'domain' => $domain,
            'form' => $form->createView(),
            'title' => 'Alias creation',
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/show/{id}", name="show", methods={"GET"})
     */
    public function showAction(User $alias)
    {

        return $this->render('alias/show.html.twig', array(
            'item' => $alias,
        ));
    }

    /**
     * Displays a form to edit an existing alias entity.
     *
     * @Route("/{id}/edit", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, User $alias)
    {
        $domain=$alias->getDomain();
        $deleteForm = $this->createDeleteForm($alias);
        $editForm = $this->createForm(
            UserType::class,
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

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $alias->getId()));
        }

        return $this->render('alias/edit.html.twig', array(
            'domain' => $domain,
            'title' => 'Alias edit',
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'jsfieldname' => 'alias',
            'jsfieldlabel' => 'correo',
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/delete/{id}", name="delete", methods={"DELETE"})
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

        return $this->redirectToRoute(self::PREFIX . 'index');
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
            ->setAction($this->generateUrl('delete', array('id' => $alias->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
