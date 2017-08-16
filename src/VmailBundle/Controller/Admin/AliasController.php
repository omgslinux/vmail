<?php

namespace VmailBundle\Controller\Admin;

use VmailBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use VmailBundle\Entity\Domain;

//use \Doctrine\ORM\EntityRepository;

/**
 * Alias controller.
 *
 * @Route("/admin/alias")
 */
class AliasController extends Controller
{
    /**
     * Lists all alias entities.
     *
     * @Route("/", name="admin_alias_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $aliases = $em->getRepository('VmailBundle:User')->findBy(['domain' => 0, 'list' => 1]);


        return $this->render('alias/index.html.twig', array(
            'items' => $aliases,
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new", name="admin_alias_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $domain = $em->getRepository('VmailBundle:Domain')->findOneBy(['id' => 0]);

        $alias = new User();
        $alias
          ->setDomain($domain)
          ->setList(true)
          ->setPassword(false)
        ;
        $form = $this->createForm('VmailBundle\Form\UserType', $alias, [
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

            return $this->redirectToRoute('admin_alias_show', array('id' => $alias->getId()));
        }

        return $this->render('alias/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/show/{id}", name="admin_alias_show")
     * @Method("GET")
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
     * @Route("/{id}/edit", name="admin_alias_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $alias)
    {
        $domain=$alias->getDomain();
        $deleteForm = $this->createDeleteForm($alias);
        $editForm = $this->createForm('VmailBundle\Form\UserType', $alias,
          [
          'domain' => $domain->getId(),
          'showList' => true
          ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('admin_alias_edit', array('id' => $alias->getId()));
        }

        //die(dump($editForm));

        return $this->render('alias/edit.html.twig', array(
            'domain' => $domain,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'jsfieldname' => 'alias',
            'jsfieldlabel' => 'correo'
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/delete/{id}", name="admin_alias_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $alias)
    {
        $domain=$alias->getDomain();
        $form = $this->createDeleteForm($alias);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($alias);
            $em->flush();
        }

        return $this->redirectToRoute('admin_alias_index');
    }

    /**
     * Creates a form to delete a virtual entity.
     *
     * @param User $virtual The virtual user
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $alias)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_alias_delete', array('id' => $alias->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
