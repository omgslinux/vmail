<?php

namespace VmailBundle\Controller\Admin;

use VmailBundle\Entity\Config;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Config controller.
 *
 * @Route("/admin/config")
 */
class ConfigController extends Controller
{
    /**
     * Lists all config entities.
     *
     * @Route("/", name="config_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $configs = $em->getRepository('VmailBundle:Config')->findAll();

        return $this->render('@vmail/config/index.html.twig', array(
            'configs' => $configs,
        ));
    }

    /**
     * Creates a new config entity.
     *
     * @Route("/new", name="config_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $config = new Config();
        $form = $this->createForm('VmailBundle\Form\ConfigType', $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($config);
            $em->flush($config);

            return $this->redirectToRoute('config_show', array('id' => $config->getId()));
        }

        return $this->render('@vmail/config/new.html.twig', array(
            'config' => $config,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a config entity.
     *
     * @Route("/{id}", name="config_show")
     * @Method("GET")
     */
    public function showAction(Config $config)
    {
        $deleteForm = $this->createDeleteForm($config);

        return $this->render('@vmail/config/show.html.twig', array(
            'config' => $config,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing config entity.
     *
     * @Route("/{id}/edit", name="config_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Config $config)
    {
        $deleteForm = $this->createDeleteForm($config);
        $editForm = $this->createForm('VmailBundle\Form\ConfigType', $config);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('config_edit', array('id' => $config->getId()));
        }

        return $this->render('@vmail/config/edit.html.twig', array(
            'config' => $config,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a config entity.
     *
     * @Route("/{id}", name="config_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Config $config)
    {
        $form = $this->createDeleteForm($config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($config);
            $em->flush($config);
        }

        return $this->redirectToRoute('config_index');
    }

    /**
     * Creates a form to delete a config entity.
     *
     * @param Config $config The config entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Config $config)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('config_delete', array('id' => $config->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
