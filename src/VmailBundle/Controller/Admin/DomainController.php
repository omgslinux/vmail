<?php

namespace VmailBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use VmailBundle\Entity\Domain;
use VmailBundle\Entity\User;
use VmailBundle\Form\DomainType;

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
     * @Route("/", name="admin_domain_index")
     * @Method({"GET", "POST"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $domains = $em->getRepository('VmailBundle:Domain')->findAll();

        return $this->render('@vmail/domain/index.html.twig', array(
            'domains' => $domains,
        ));
    }

    /**
     * Creates a new User in a Domain entity.
     *
     * @Route("/user/new/{id}", name="admin_domain_user_new")
     * @Method({"GET", "POST"})
     */
    public function newUserAction(Request $request, Domain $domain)
    {
        //$em = $this->getDoctrine()->getManager();
        //$fundbanks = $em->getRepository('VmailBundle:FundBanks')->find($fund);
        $user = new User();
        $user->setDomain($domain)->setSendEmail(true)->setActive(true);
        $form = $this->createForm('VmailBundle\Form\UserType', $user,['showAutoreply' => false] );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $u=$this->get('vmail.userform');
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
     * @Route("/new", name="admin_domain_new")
     * @Method({"GET"})
     */
    public function newDomainAction(Request $request)
    {
        $domain = new Domain();
        $form = $this->createDomainForm($domain);

        return $this->render('@vmail/domain/new.html.twig', array(
            'domain' => $domain,
            'action' => $this->get('translator')->trans('Create a new domain'),
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new Demo entity.
     *
     * @Route("/new", name="admin_domain_create")
     * @Method("POST")
     *
     */
    public function createDomainAction(Request $request)
    {
        //This is optional. Do not do this check if you want to call the same action using a regular request.
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => 'You can access this only using Ajax!'), 400);
        }

        $entity = new Domain();
        $form = $this->createDomainForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return new JsonResponse(array('message' => 'Success!'), 200);
        }

        $response = new JsonResponse(
            [
                'message' => 'Error',
                'form' => $this->renderView(
                    '@vmail/user/form.html.twig',
                    [
                        'entity' => $entity,
                        'form' => $form->createView(),
                    ]
                ),
            ],
            400
        );
dump($response);
        return $response;
    }

    /**
     * Creates a form to create a Demo entity.
     *
     * @param Demo $entity The entity
     *
     * @return SymfonyComponentFormForm The form
     */
    private function createDomainForm(Domain $entity)
    {
        $form = $this->createForm(
            DomainType::class,
            $entity,
            [
                'action' => $this->generateUrl('admin_domain_create'),
                'method' => 'POST',
            ]
        );

        return $form;
    }


    /**
     * Creates a form to edit a Domain entity.
     *
     * @Route("/edit/{id}", name="admin_domain_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Domain $domain)
    {
        $deleteForm = $this->createDeleteForm($domain);
        $editform = $this->createForm('VmailBundle\Form\DomainType', $domain);
        $editform->handleRequest($request);

        if ($editform->isSubmitted() && $editform->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($domain);
            $em->flush();
            $config=$this->get('vmail.config');
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
     * @Route("/delete/{id}", name="admin_domain_delete")
     * @Method({"GET", "DELETE"})
     */
    public function deleteAction(Request $request, Domain $domain)
    {
        $form = $this->createDeleteForm($domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $config=$this->get('vmail.config');
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
     * @Route("/show/byname/{name}", name="admin_domain_showbyname")
     * @Method({"GET", "POST"})
     */
    public function showByNameAction(Request $request, $name)
    {
        $em = $this->getDoctrine()->getManager();
        $domain=$em->getRepository('VmailBundle:Domain')->findOneBy(['name' => $name]);
        $users=$em->getRepository('VmailBundle:User')->findBy(['domain' => $domain, 'list' => 0]);
        $lists=$em->getRepository('VmailBundle:User')->findBy(['domain' => $domain, 'list' => 1]);
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
     * @Route("/show/byid/{id}", name="admin_domain_show")
     * @Method({"GET", "POST"})
     */
    public function showAction(Request $request, Domain $domain)
    {
        $deleteForm = $this->createDeleteForm($domain);
        $em = $this->getDoctrine()->getManager();
        $users=$em->getRepository('VmailBundle:User')->findBy(['domain' => $domain]);

        return $this->redirectToRoute('admin_domain_showbyname', ['name' => $domain->getName()]);

        return $this->render('@vmail/domain/show.html.twig', array(
            'domain' => $domain,
            'delete_form' => $deleteForm->createView(),
            'users' => $users,
        ));
    }


}
