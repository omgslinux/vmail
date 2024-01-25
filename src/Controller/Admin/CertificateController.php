<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\User;
use App\Form\CertCommonType;
use App\Utils\Certificate;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

/**
 * Domain controller.
 */
#[Route(path: '/admin/certificate', name: 'admin_certificate_')]
class CertificateController extends AbstractController
{


    /**
     * Lists all domain entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, REPO $domainRepo): Response
    {
        $entity = new Domain();
        $form = $this->createForm(CertCommonType::class, null, ['domain' => $entity]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
            dump($form);
            $data = Certificate::getFormData($form->getData());
            dump($data);
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('domain/_certform.html.twig',
          [
              'title' => 'Domain list',
              'form' => $form->createView(),
          ]
        );
    }


}
