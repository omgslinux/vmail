<?php

namespace App\Controller\Admin;

use App\Entity\Config;
use App\Form\ConfigType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Config controller.
 */
#[Route(path: '/admin/config', name: 'config_')]
class ConfigController extends AbstractController
{
    /**
     * Lists all config entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $configs = $em->getRepository(Config::class)->findAll();

        return $this->render('config/index.html.twig', [
            'configs' => $configs,
        ]);
    }

    /**
     * Creates a new config entity.
     */
    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    public function newAction(Request $request)
    {
        $config = new Config();
        $form = $this->createForm(ConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($config);
            $em->flush($config);

            return $this->redirectToRoute('config_show', array('id' => $config->getId()));
        }

        return $this->render('config/new.html.twig', array(
            'config' => $config,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a config entity.
     */
    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function showAction(Config $config)
    {
        $deleteForm = $this->createDeleteForm($config);

        return $this->render('config/show.html.twig', array(
            'config' => $config,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing config entity.
     */
    #[Route(path: '/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Config $config)
    {
        $deleteForm = $this->createDeleteForm($config);
        $editForm = $this->createForm(ConfigType::class, $config);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('config_edit', array('id' => $config->getId()));
        }

        return $this->render('config/edit.html.twig', array(
            'config' => $config,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a config entity.
     */
    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
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
