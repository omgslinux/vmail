<?php

namespace VmailBundle\Controller;

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
 * @Route("/manage/virtuals")
 */
class AliasController extends Controller
{
    /**
     * Lists all alias entities.
     *
     * @Route("/", name="manage_virtuals_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user=$this->getUser();
        $domain=$user->getDomain();

        return $this->redirectToRoute('manage_domain_virtuals_index', ['id' => $domain->getId()]);
    }

    /**
     * Lists all alias entities.
     *
     * @Route("/index/{id}", name="manage_domain_virtuals_index")
     * @Method("GET")
     */
    public function domainindexAction(Domain $domain)
    {
        $user=$domain->getUser();

        //$aliases = $em->getRepository('VmailBundle:User')->findByDomain($domain);
        $qb=$em->createQueryBuilder();
        $qb
            ->select('u')
            ->from('VmailBundle:User', 'u')
            ->where('u.domain = :domain')
            ->andWhere('u.list = 1')
            ->setParameter('domain', $domain)
        ;
        $aliases = $qb->getQuery()->getResult();
        //return $query->getResult();


        return $this->render('alias/index.html.twig', array(
            'aliases' => $aliases,
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new/{id}", name="manage_domain_virtuals_new")
     * @Method({"GET", "POST"})
     */
    public function domainnewAction(Request $request, Domain $domain=null)
    {
        if (is_null($domain)) {
            $domain=$this->getUser()->getDomain();
        }

        $vuser = new User();
        $vuser
          ->setDomain($domain)
          ->setList(true)
          ->setPassword(false)
        ;
        $form = $this->createForm('VmailBundle\Form\UserType', $vuser, [
          'showVirtual' => true,
          'domain' => $domain->getId(),
          ]
        )
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($vuser);
            $em->flush();

            return $this->redirectToRoute('manage_virtuals_show', array('id' => $vuser->getId()));
        }

        return $this->render('alias/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Creates a new alias entity.
     *
     * @Route("/new", name="manage_virtuals_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {

        $domain=$this->getUser()->getDomain();

        return $this->redirectToRoute('manage__domain_virtuals_new', ['id' => $domain->getId()]);
    }

    /**
     * Finds and displays a alias entity.
     *
     * @Route("/show/{id}", name="manage_virtuals_show")
     * @Method("GET")
     */
    public function showAction(User $vuser)
    {

        return $this->render('alias/show.html.twig', array(
            'item' => $vuser,
        ));
    }

    /**
     * Displays a form to edit an existing alias entity.
     *
     * @Route("/{id}/edit", name="manage_virtuals_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, User $vuser)
    {
        $domain=$vuser->getDomain();
        $deleteForm = $this->createDeleteForm($vuser);
        $editForm = $this->createForm('VmailBundle\Form\UserType', $vuser,
          [
          'showVirtual' => true,
          'domain' => $domain->getId(),
          ]
        );
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('manage_virtuals_edit', array('id' => $vuser->getId()));
        }

        return $this->render('alias/edit.html.twig', array(
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'jsfieldname' => 'virtual',
            'jsfieldlabel' => 'correo'
        ));
    }

    /**
     * Deletes a alias entity.
     *
     * @Route("/delete/{id}", name="manage_virtuals_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $vuser)
    {
        $domain=$vuser->getDomain();
        $form = $this->createDeleteForm($vuser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($vuser);
            $em->flush();
        }

        return $this->redirectToRoute('manage_domain_virtuals_index', ['id' => $domain]);
    }

    /**
     * Creates a form to delete a virtual entity.
     *
     * @param User $virtual The virtual user
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $vuser)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('manage_virtuals_delete', array('id' => $vuser->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
