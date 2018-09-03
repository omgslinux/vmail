<?php

namespace VmailBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\User;
use VmailBundle\Utils\UserForm;
use VmailBundle\Utils\ReadConfig;

/**
 * Domain controller.
 *
 * @Route("/admin/domain", name="admin_domain_")
 */
class DomainController extends Controller
{

    /**
     * Lists all domain entities.
     *
     * @Route("/", name="index", methods={"GET"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $domains = $em->getRepository(Domain::class)->findAll();

        return $this->render('@vmail/domain/index.html.twig', array(
            'domains' => $domains,
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
            'VmailBundle\Form\UserType',
            $user,
            ['showAutoreply' => false]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u->setUser($user);
            $u->formSubmit($form);

            return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getId()));
        }

        return $this->render('@vmail/user/edit.html.twig', array(
            'user' => $user,
            'action' => $this->get('translator')->trans('Create a new user'),
            'backlink' => $this->generateUrl('admin_domain_index'),
            'backmessage' => 'Back',
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new Domain entity.
     *
     * @Route("/new", name="new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, ReadConfig $config)
    {
        $domain = new Domain();
        $form = $this->createForm('VmailBundle\Form\DomainType', $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();
            $base=$config->findParameter('virtual_mailbox_base');
            mkdir($base.'/'.$domain->getId());
            system("cd $base;ln -s " . $domain->getId() . " " . $domain->getName());

            return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getId()));
        }

        return $this->render('@vmail/domain/edit.html.twig', array(
            'domain' => $domain,
            'action' => $this->get('translator')->trans('Create a new domain'),
            'backlink' => $this->generateUrl('admin_domain_index'),
            'backmessage' => 'Back',
            'form' => $form->createView(),
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
        $editform = $this->createForm('VmailBundle\Form\DomainType', $domain);
        $editform->handleRequest($request);

        if ($editform->isSubmitted() && $editform->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();
            $base=$config->findParameter('virtual_mailbox_base')->getValue();
            system("rm -rf " . $base . "/" . $domain->getName());
            system("cd $base;ln -sf " . $domain->getId() . " " . $domain->getName());

            return $this->redirectToRoute('admin_domain_show', array('id' => $domain->getId()));
        }
        $t = $this->get('translator');

        return $this->render('@vmail/domain/edit.html.twig', array(
            'domain' => $domain,
            'action' => $t->trans('Domain edit') . ' ' . $domain->getName(),
            'backlink' => $this->generateUrl('admin_domain_show', array('id' => $domain->getId())),
            'backmessage' => 'Back',
            'form' => $editform->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
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

        return $this->redirectToRoute('admin_domain_index');
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
            ->setAction($this->generateUrl('admin_domain_delete', array('id' => $domain->getId())))
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

        return $this->render('@vmail/domain/show.html.twig', array(
            'domain' => $domain,
            'delete_form' => $deleteForm->createView(),
            'users' => $users,
            'lists' => $lists,
        ));
    }

    /**
     * Creates a form to show a FundBanks entity.
     *
     * @Route("/show/byid/{id}", name="show", methods={"GET", "POST"})
     */
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute('admin_domain_showbyname', ['name' => $domain->getName()]);
    }
}
