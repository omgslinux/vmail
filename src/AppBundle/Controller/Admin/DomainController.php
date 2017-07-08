<?php

namespace AppBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Domain;

/**
 * Domain controller.
 *
 * @Route("/admin/domain")
 */
class DomainController extends Controller
{

    /**
     * Lists all domain entities.
     *
     * @Route("/", name="admin_domains_index")
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
     * Creates a new Domain entity.
     *
     * @Route("/new", name="admin_domains_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        //$em = $this->getDoctrine()->getManager();
        //$fundbanks = $em->getRepository('AppBundle:FundBanks')->find($fund);
        $domain = new Domain();
        $form = $this->createForm('AppBundle\Form\DomainType', $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();

            return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getDomain()));
        }

        return $this->render('default/edit.html.twig', array(
            'domain' => $domain,
            'action' => 'Añadir dominio',
            'backlink' => $this->generateUrl('admin_domain_show', array('id' => $domain->getDomain())),
            'backmessage' => 'Volver al listado',
            'create_form' => $form->createView(),
        ));
    }

    /**
     * Creates a form to edit a Domain entity.
     *
     * @Route("/edit/{id}", name="admin_domain_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Domain $domain)
    {
//        $fund = $em->getRepository('AppBundle:Funds')->findOneBy(array('id' => $fundbanks->getFund()));
        $deleteForm = $this->createDeleteForm($domain);
        $editform = $this->createForm('AppBundle\Form\DomainType', $domain);
        $editform->handleRequest($request);

        if ($editform->isSubmitted() && $editform->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();

            return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getDomain()));
        }

        return $this->render('default/edit.html.twig', array(
            'domain' => $domain,
            'action' => 'Editar dominio ' . $domain,
            'backlink' => $this->generateUrl('admin_domain_show', array('id' => $domain->getDomain())),
            'backmessage' => 'Volver al listado',
            'edit_form' => $editform->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Domain entity.
     *
     * @Route("/delete/{id}", name="admin_domain_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Domain $domain)
    {
        $form = $this->createDeleteForm($domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($domain);
            $em->flush();
        }

        return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getDomain()));
    }

    /**
     * Creates a form to delete a FundBanks entity.
     *
     * @param FundBanks $fundbanks The FundBanks entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Domain $domain)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_domain_delete', array('id' => $domain->getDomain())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/{id}", name="admin_domain_show")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request, Domains $domain)
    {

        return $this->render('domain.html.twig', array(
            'domain' => $domain,
            'exists' => $exists,
            'securitiescount' => $securitiescount,
            'download_form' => $form
        ));
    }


}
