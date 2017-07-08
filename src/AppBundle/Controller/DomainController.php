<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Domain;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Domain controller.
 *
 * @Route("domain")
 */
class DomainController extends Controller
{
    /**
     * Lists all domain entities.
     *
     * @Route("/", name="domain_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $domains = $em->getRepository('AppBundle:Domain')->findAll();

        return $this->render('domain/index.html.twig', array(
            'domains' => $domains,
        ));
    }

    /**
     * Creates a new domain entity.
     *
     * @Route("/new", name="domain_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $domain = new Domain();
        $form = $this->createForm('AppBundle\Form\DomainType', $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush($domain);

            return $this->redirectToRoute('domain_show', array('id' => $domain->getId()));
        }

        return $this->render('domain/new.html.twig', array(
            'domain' => $domain,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a domain entity.
     *
     * @Route("/{id}", name="domain_show")
     * @Method("GET")
     */
    public function showAction(Domain $domain)
    {
        $deleteForm = $this->createDeleteForm($domain);

        return $this->render('domain/show.html.twig', array(
            'domain' => $domain,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing domain entity.
     *
     * @Route("/{id}/edit", name="domain_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Domain $domain)
    {
        $deleteForm = $this->createDeleteForm($domain);
        $editForm = $this->createForm('AppBundle\Form\DomainType', $domain);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('domain_edit', array('id' => $domain->getId()));
        }

        return $this->render('domain/edit.html.twig', array(
            'domain' => $domain,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a domain entity.
     *
     * @Route("/{id}", name="domain_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Domain $domain)
    {
        $form = $this->createDeleteForm($domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($domain);
            $em->flush($domain);
        }

        return $this->redirectToRoute('domain_index');
    }

    /**
     * Creates a form to delete a domain entity.
     *
     * @param Domain $domain The domain entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Domain $domain)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('domain_delete', array('id' => $domain->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
