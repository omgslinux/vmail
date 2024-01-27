<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\User;
use App\Form\CertType;
//use App\Form\CertCommonType;
use App\Utils\Certificate;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

/**
 * Domain controller.
 */
#[Route(path: '/admin/certificate', name: 'admin_certificate_')]
class CertificateController extends AbstractController
{

    const TABS = [
        [
          'n' => 'ca',
          't' => 'CA',
        ],
        [
          'n' => 'client',
          't' => 'Client',
        ],
        [
          'n' => 'server',
          't' => 'Server',
        ],
      ];

    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => 'admin_certificate_',
        'included' => 'certificates/_form',
        'tdir' => 'certificates',
        'BASEDIR' => 'certificates/',
        'modalId' => 'certs',
    ];

    private $util;

    private REPO $repo;

    public function __construct(Certificate $util, REPO $repo)
    {
        $this->util = $util;
        $this->repo = $repo;
    }

    /**
     * Lists all domain entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {

        $form = $this->createForm(CertType::class, null, ['duration' => '10 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/index.html.twig',
            [
                'title' => 'Certificates management',
                'form' => $form->createView(),
                'entities' => $this->repo->findAll(),
                'VARS' => self::VARS,
            ]
        );
    }

    #[Route(path: '/{id}/ca', name: 'ca', methods: ['GET', 'POST'])]
    public function ca(Request $request, Domain $domain): Response
    {
        $certSubject = null;
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certSubject = $cert['subject'];
            dump($certData, $cert);
            dump($cert['subject']);
        }
        $form = $this->createForm(CertType::class, null, ['domain' => $domain, 'subject' => $certSubject, 'certtype' => 'CA', 'duration' => '10 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
            dump($form);
            $formData = $form->getData();
            $certData = $this->util->createCACert($formData);
            $domain->setCertData($certData);
            $this->repo->add($domain, true);
            $this->addFlash('success', 'Se creo el certificado');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create CA certificate',
              'form' => $form->createView(),
              'emtity' => $domain,
          ]
        );
    }

    #[Route(path: '/{id}/client', name: 'client', methods: ['GET', 'POST'])]
    public function client(Request $request, REPO $domainRepo, Domain $domain): Response
    {
        $form = $this->createForm(CertType::class, null, ['domain' => $domain, 'duration' => '5 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
            $formData = $form->getData();
            dump($formData['common']);
            $certData = $this->util->createClientCert($formData);
            dd($certData);
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create client certificate',
              'form' => $form->createView(),
              'emtity' => $domain,
          ]
        );
    }

    #[Route(path: '/{id}/server', name: 'server', methods: ['GET', 'POST'])]
    public function server(Request $request, REPO $domainRepo, Domain $domain): Response
    {
        $form = $this->createForm(CertType::class, null, ['domain' => $domain, 'certtype' => 'server', 'duration' => '5 years']);
        $form->handleRequest($request);

        dump($form);
        if ($form->isSubmitted() && $form->isValid()) {
            //system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
            $formData = $form->getData();
            $certData = $this->util->createServerCert($formData);
            dd($certData);
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create server certificate',
              'form' => $form->createView(),
          ]
        );
    }


}
