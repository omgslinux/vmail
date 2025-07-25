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
use App\Form\CertDownloadType;
//use App\Form\CertCommonType;
use App\Utils\Certificate;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

/**
 * Domain controller.
 */
#[Route(path: '/manage/certificate', name: 'admin_certificate_')]
class CertificateController extends AbstractController
{

    const VARS = [
        'modalSize' => 'modal-md',
        'PREFIX' => 'admin_certificate_',
        'included' => 'certificates/_form',
        'tdir' => 'certificates',
        'BASEDIR' => 'certificates/',
        'modalId' => 'certs',
    ];


    public function __construct(private Certificate $util, private REPO $repo)
    {
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

        return $this->render(
            'certificates/index.html.twig',
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
        $form = $this->createForm(
            CertType::class,
            null,
            [
                'domain' => $domain,
                'subject' => $certSubject,
                'certtype' => 'ca',
                'interval' => $certInterval,
                'duration' => '10 years',
                'download' => null !=$certSubject,
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'ca', ['id' => $domain->getId()]),

            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (null==$certSubject) {
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
                    $this->repo->save($domain, true);
                    $this->addFlash('success', 'Se creó el certificado');
                }

                return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
                //return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
            } else {
                return $this->util
                ->certDownload('ca', [$domain, 'pem']);
            }
        }

        return $this->render(
            'certificates/_form.html.twig',
            [
                'ajax' => true,
                'title' => 'Create CA certificate',
                'modalTitle' => 'Create CA certificate',
                'form' => $form->createView(),
                'entity' => $domain,
                'VARS' => self::VARS,
            ]
        );
    }

    #[Route(path: '/{id}/client/new', name: 'client_new', methods: ['GET', 'POST'])]
    public function clientNew(Request $request, UR $userRepo, User $user): Response
    {
        $domain = $user->getDomain();
        $certSubject = null;
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certSubject = $cert['subject'];
        }

        $form = $this->createForm(
            CertType::class,
            null,
            [
                'domain' => $domain->getId(),
                'subject' => $certSubject,
                'certtype' => 'client',
                'duration' => '5 years',
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'client_new', ['id' => $user->getId()]),
            ]
        );
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
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
            }

            return $this->redirectToRoute('manage_user_index');
        }

        return $this->render(
            'certificates/_form.html.twig',
            [
              'title' => 'Create client certificate',
              'form' => $form->createView(),
              'entity' => $domain,
            ]
        );
    }

    #[Route(path: '/{id}/client/download/', name: 'client_download', methods: ['GET', 'POST'])]
    public function clientDownload(Request $request, User $user): Response
    {
        $form = $this->createForm(
            CertDownloadType::class,
            null,
            [
                'certtype' => 'client',
                'entity' => $user,
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'client_download', ['id' => $user->getId()])
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            return $this->util->certDownload(
                'client',
                [
                    'format' => $form->getClickedButton()->getName(),
                    'setkey' => $formData['setkey']
                ]
            );

            return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $user->getDomain()->getName() ]);
        }

        return $this->render(
            'certificates/_download.html.twig',
            [
                'modalTitle' => 'Download certificate',
                'form' => $form,
            ]
        );
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

        $form = $this->createForm(
            CertType::class,
            null,
            [
                'domain' => $domain,
                'subject' => $certSubject,
                'certtype' => 'server',
                'duration' => '5 years',
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'server_new', ['id' => $domain->getId()]),
            ]
        );
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
            return $this->redirectToRoute('admin_domain_showbyname', [ 'name' => $domain->getName(), 'activetab' =>2 ]);
        }

        return $this->render(
            'certificates/_form.html.twig',
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

        return $this->render(
            'certificates/server_show.html.twig',
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
            //$this->addFlash('success', 'Se creo el certificado');
            return $this->util
            ->certDownload('server', [$certificate, $dtype]);

        } else {
            $this->addFlash('error', "Opción incorrecta $dtype");
        }
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
    }

}
