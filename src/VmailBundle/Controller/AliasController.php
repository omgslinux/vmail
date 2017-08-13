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
        $user->setPassword(false);
        $form = $this->createForm('VmailBundle\Form\UserType', $user, [
          'showVirtual' => true,
          'domain' => $domain,
          'showList' => true,
          ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('manage_alias_show', array('id' => $user->getId()));
        }

        return $this->render('alias/new.html.twig', array(
            'domain' => $domain,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/{id}", name="manage_alias_show")
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
     * @Route("/{id}/edit", name="manage_alias_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $alias)
    {
        $domain=$this->getUser()->getDomainName();
        $deleteForm = $this->createDeleteForm($alias);
        $editForm = $this->createForm('VmailBundle\Form\UserType', $alias, [
          'showVirtual' => true,
          'domain' => $domain,
          'showList' => true,
          ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('mangae_alias_edit', array('id' => $alias->getId()));
        }

        return $this->render('alias/edit.html.twig', array(
            'item' => $alias,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'jsfieldname' => 'virtual',
            'jsfieldlabel' => 'correo'
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/{id}", name="manage_alias_delete")
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
    private function createDeleteForm(User $alias)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('manage_alias_delete', array('id' => $alias->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
