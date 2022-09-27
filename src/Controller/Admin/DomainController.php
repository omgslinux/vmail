<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\User;
use App\Utils\UserForm;
use App\Utils\ReadConfig;
use App\Form\UserType;
use App\Form\DomainType;
use App\Repository\DomainRepository as REPO;

/**
 * Domain controller.
 *
 * @Route("/admin/domain", name="admin_domain_")
 */
class DomainController extends AbstractController
{
    const PREFIX = 'admin_domain_';
    /**
     * Lists all domain entities.
     *
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function indexAction(Request $request, REPO $repo, ReadConfig $config): Response
    {
        $entity = new Domain();
        $form = $this->createForm(
            DomainType::class,
            $entity,
            /*[
                'action' => $this->generateUrl(self::PREFIX . 'new')
            ]*/
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();*/
            $repo->add($entity, true);
            $base=$config->findParameter('virtual_mailbox_base');
            mkdir($base.'/'.$entity->getId());
            system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());

            //return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $entity->getId()));
        }


        return $this->render('domain/index.html.twig', array(
            'entities' => $repo->findAll(),
            'form' => $form->createView(),
            'PREFIX' => self::PREFIX,
        ));
    }


    /**
     * Creates a new User in a Domain entity.
     *
     * @Route("/user/new/{id}", name="user_new", methods={"GET", "POST"})
     */
    public function newUserAction(Request $request, UserForm $u, Domain $domain)
    {
        $user = new User();
        $user->setDomain($domain)->setSendEmail(true)->setActive(true);
        $form = $this->createForm(
            UserType::class,
            $user,
            [
                'action' => $this->generateUrl('user_new', ['id' => $domain->getId()]),
                'showAutoreply' => false,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u->setUser($user);
            $u->formSubmit($form);

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }

        return $this->render('user/form.html.twig', array(
            'user' => $user,
            'action' => 'Create a new user',
            'backlink' => $this->generateUrl(self::PREFIX . 'index'),
            'backmessage' => 'Back',
            'form' => $form->createView(),
            'ajax' => true,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Creates a form to edit a Domain entity.
     *
     * @Route("/edit/{id}", name="edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, ReadConfig $config, Domain $domain)
    {
        $deleteForm = $this->createDeleteForm($domain);
        $editform = $this->createForm(
            DomainType::class,
            $domain,
            [
                'action' => $this->generateUrl(self::PREFIX . 'edit', [ 'id' => $domain->getId() ])
            ]
        );
        $editform->handleRequest($request);

        if ($editform->isSubmitted() && $editform->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();
            $base=$config->findParameter('virtual_mailbox_base');
            system("rm -rf " . $base . "/" . $domain->getName());
            system("cd $base;ln -sf " . $domain->getId() . " " . $domain->getName());

            return $this->redirectToRoute(self::PREFIX . 'show', array('id' => $domain->getId()));
        }
        $t = $this->get('translator');

        return $this->render('domain/form.html.twig', array(
            'domain' => $domain,
            'action' => 'Domain edit',
            'backlink' => $this->generateUrl(self::PREFIX . 'show', array('id' => $domain->getId())),
            'backmessage' => 'Back',
            'form' => $editform->createView(),
            'delete_form' => $deleteForm->createView(),
            'ajax' => true,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"POST"})
     */
    public function delete(Request $request, Domain $entity, REPO $repo): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $REPO->remove($entity, true);
        }

        return $this->redirectToRoute(self::PREFIX . 'index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Deletes a Domain entity.
     *
     * @Route("/delete/{id}", name="delete", methods={"GET", "DELETE"})
     */
    public function deleteAction(Request $request, ReadConfig $config, Domain $domain)
    {
        $form = $this->createDeleteForm($domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $base=$config->findParameter('virtual_mailbox_base');
            system("rm -rf " . $base . "/" . $domain->getName());
            rmdir($base . "/" . $domain->getId());
            $em->remove($domain);
            $em->flush($domain);
        }

        return $this->redirectToRoute(self::PREFIX . 'index');
    }

    /**
     * Creates a form to delete a Domain entity.
     *
     * @param Domain $domain The Domain entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Domain $domain)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl(self::PREFIX . 'delete', array('id' => $domain->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/show/byname/{name}", name="showbyname", methods={"GET", "POST"})
     */
    public function showByNameAction(Request $request, $name)
    {
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository(Domain::class)->findOneBy(['name' => $name]);
        $users=$em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 0]);
        $lists=$em->getRepository(User::class)->findBy(['domain' => $domain, 'list' => 1]);
        $deleteForm = $this->createDeleteForm($domain);

        return $this->render('domain/show.html.twig', array(
            'domain' => $domain,
            'delete_form' => $deleteForm->createView(),
            'users' => $users,
            'lists' => $lists,
            'PREFIX' => self::PREFIX,
        ));
    }

    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET", "POST"})
     */
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute(self::PREFIX . 'showbyname', ['name' => $domain->getName()]);
    }
}
