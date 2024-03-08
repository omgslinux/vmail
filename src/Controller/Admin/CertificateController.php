<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Domain;
use App\Entity\ServerCertificate;
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

        /*if ($form->isSubmitted() && $form->isValid()) {

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }*/

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
        $certSubject = $certInterval = null;
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certSubject = $cert['subject'];
            $certInterval = [
                'NotBefore' => $this->util::convertUTCTime2Date($cert['validFrom']),
                'NotAfter'  => $this->util::convertUTCTime2Date($cert['validTo']),
            ];
            //dump($certData, $cert, $certInterval);
        }
        $form = $this->createForm(CertType::class, null, ['domain' => $domain, 'subject' => $certSubject, 'certtype' => 'ca', 'interval' => $certInterval, 'duration' => '10 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $request->files->all();
            $csvcontents = null;
            foreach ($files as $file) {
                $csvfile = $file['common']['customFile'];
                if (null!=$csvfile) {
                    $csvcontents = file_get_contents($csvfile);
                }
            }
            $formData = $form->getData();
            $certData = $this->util->createCACert($formData, $csvcontents);
            //dd($certData);
            if (!empty($certData['error'])) {
                $this->addFlash('error', $certData['error']);
            } else {
                $domain->setCertData($certData);
                $this->repo->add($domain, true);
                $this->addFlash('success', 'Se creó el certificado');
            }
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create CA certificate',
              'form' => $form->createView(),
              'entity' => $domain,
          ]
        );
    }

    #[Route(path: '/{id}/client/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function clientNew(Request $request, UR $userRepo, Domain $domain): Response
    {
        $certSubject = null;
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certSubject = $cert['subject'];
        }

        $form = $this->createForm(CertType::class, null, ['domain' => $domain->getId(), 'subject' => $certSubject, 'certtype' => 'client', 'duration' => '5 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->util->setDomain($domain);
            $user = $formData['common']['emailAddress'];
            $certData = $this->util->createClientCert($formData);
            $indexData = $this->util->addToIndex($certData['certdata']['cert']);
            //dd($certData, $indexData);
            $user->setCertData($certData);
            $userRepo->add($user, true);
            $this->repo->updateCAIndex($domain, $indexData);
            $this->addFlash('success', 'Se creo el certificado');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create client certificate',
              'form' => $form->createView(),
              'entity' => $domain,
          ]
        );
    }

    #[Route(path: '/{id}/client/download/{dtype}', name: 'client_download', methods: ['GET', 'POST'])]
    public function clientDownload(Request $request, User $user, $dtype='pcks12'): Response
    {
        if (($dtype == 'pkcs12') || ($dtype == 'certkey')) {
            $this->addFlash('success', 'Se creo el certificado');
            return $this->util
            ->certDownload('client', [$user, $dtype]);

        } else {
            $this->addFlash('error', "Opción incorrecta $dtype");
        }
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
    }

    #[Route(path: '/{id}/server/new', name: 'server_new', methods: ['GET', 'POST'])]
    public function serverNew(Request $request, Domain $domain): Response
    {
        $certSubject = null;
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certSubject = $cert['subject'];
            // Eliminamos el commonName
            unset($certSubject['commonName']);
        }

        $form = $this->createForm(CertType::class, null, ['domain' => $domain, 'subject' => $certSubject, 'certtype' => 'server', 'duration' => '5 years']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->util->setDomain($domain);
            $certData = $this->util->createServerCert($formData);
            //dump($formData, $certData);
            $d = $formData['common']['commonName'];
            $serverCertificate = new ServerCertificate();
            $serverCertificate->setDomain($domain)
            ->setDescription($d!='*'?$d:'wildcard')
            ->setCertData($certData);
            $domain->addServerCertificate($serverCertificate);
            $indexData = $this->util->addToIndex($certData['certdata']['cert']);
            //dd($indexData);
            //$this->repo->add($domain, true);
            $this->repo->updateCAIndex($domain, $indexData);
            $this->addFlash('success', 'Se creo el certificado');
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render('certificates/_form.html.twig',
          [
              'title' => 'Create server certificate',
              'form' => $form->createView(),
              'entity' => $domain,
      ]
        );
    }

    #[Route(path: '/{id}/server/show', name: 'server_show', methods: ['GET', 'POST'])]
    public function serverShow(Request $request, Domain $domain): Response
    {
        $entities = [];
        foreach ($domain->getServerCertificates() as $certificate) {
            if (null!=$certData=$domain->getCertData()) {
                $certout = $certData['certdata']['cert'];
                $cert = openssl_x509_parse($certout, false);
                $certInterval = [
                    'notBefore' => $this->util::convertUTCTime2Date($cert['validFrom']),
                    'notAfter'  => $this->util::convertUTCTime2Date($cert['validTo']),
                ];
                //dump($certData, $cert, $certInterval);
                $data = [
                    'description' => $certificate->getDescription(),
                    'domain' => $certificate->getDomain(),
                    'certdata' => $this->util->extractX509Data($certificate),
                    'interval' => $certInterval,
                    'entity' => $certificate,
                ];
                $entities[] = $data;
            }
        }
        //dump($entities);

        return $this->render('certificates/server_show.html.twig',
          [
              'title' => 'Show server certificate',
              'domain' => $domain,
              'entities' => $entities,
              'VARS' => self::VARS,
      ]
        );
    }

    #[Route(path: '/{id}/server/download/{dtype}', name: 'server_download', methods: ['GET', 'POST'])]
    public function serverDownload(Request $request, ServerCertificate $certificate, $dtype): Response
    {
        if (($dtype == 'chain') || ($dtype == 'certkey')) {
            $this->addFlash('success', 'Se creo el certificado');
            return $this->util
            ->certDownload('server', [$certificate, $dtype]);

        } else {
            $this->addFlash('error', "Opción incorrecta $dtype");
        }
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
    }

}
