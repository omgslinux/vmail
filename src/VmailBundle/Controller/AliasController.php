<?php

namespace VmailBundle\Controller;

use VmailBundle\Entity\User;
use VmailBundle\Entity\Alias;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/", name="manage_alias_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user=$this->getUser();
        $domain=$user->getDomainName();

        $aliases = $em->getRepository('VmailBundle:Virtual')->findVirtualByDomain($domain);

        return $this->render('alias/index.html.twig', array(
            'aliases' => $aliases,
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new", name="manage_alias_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $domain=$this->getUser()->getDomainName();

        $user = new User();
        $user->setList(true);
        $form = $this->createForm('VmailBundle\Form\UserType', $user, [
          'showVirtual' => true,
          'domain' => $domain,
          'showList' => true,
          ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($alias);
            $em->flush($alias);

            return $this->redirectToRoute('alias_show', array('id' => $alias->getId()));
        }

        return $this->render('alias/new.html.twig', array(
            'domain' => $domain,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/{id}", name="alias_show")
     * @Method("GET")
     */
    public function showAction(Alias $alias)
    {
        $deleteForm = $this->createDeleteForm($alias);

        return $this->render('alias/show.html.twig', array(
            'alias' => $alias,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing alias entity.
     *
     * @Route("/{id}/edit", name="alias_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Alias $alias)
    {
        $deleteForm = $this->createDeleteForm($alias);
        $editForm = $this->createForm('VmailBundle\Form\AliasType', $alias);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('alias_edit', array('id' => $alias->getId()));
        }

        return $this->render('alias/edit.html.twig', array(
            'alias' => $alias,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/{id}", name="alias_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Alias $alias)
    {
        $form = $this->createDeleteForm($alias);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($alias);
            $em->flush($alias);
        }

        return $this->redirectToRoute('alias_index');
    }

    /**
     * Creates a form to delete a alias entity.
     *
     * @param Alias $alias The alias entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Alias $alias)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('alias_delete', array('id' => $alias->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
