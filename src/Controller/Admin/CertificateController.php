<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use App\Dto\CertDto;
use App\Entity\Domain;
use App\Entity\ServerCertificate;
use App\Entity\User;
use App\Form\CertType;
use App\Form\CertDownloadType;
//use App\Form\CertCommonType;
use App\Utils\Certificate;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route(path: '/{id}/cadownload', name: 'cadownload', methods: ['GET', 'POST'])]
    public function cadownload(Request $request, Domain $domain): Response
    {
        return $this->util
        ->certDownload('ca', [$domain, 'pem']);
    }



    #[Route(path: '/{id}/ca', name: 'ca', methods: ['GET', 'POST'])]
    public function ca(Request $request, Domain $domain): Response
    {
        $dto = new CertDto();
        $dto->setDownload(false)
        ->setCertType('ca')
        ->setCAInherit(false)
        ;
        $title = 'Create CA certificate';
        if (null!=$certData=$domain->getCertData()) {
            $certout = $certData['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $certInterval = [
                'NotBefore' => $this->util::convertUTCTime2Date($cert['validFrom']),
                'NotAfter'  => $this->util::convertUTCTime2Date($cert['validTo']),
            ];
            $dto->setSubject($cert['subject'])
            ->setNew(false)
            ->setCAInherit(true)
            ->setInterval($certInterval)
            ->setDownload(true);
            ;
            $title = 'Manage CA certificate';
        }
        $dto->setDomain($domain)

        ;
        $form = $this->createForm(
            CertType::class,
            $dto,
            [
                'dto' => $dto,
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'ca', ['id' => $domain->getId()]),

            ]
        );
        $form->handleRequest($request);

        $render = [
            'template' => 'certificates/_form.html.twig',
            'args' => [
                'title' => $title,
                'modalTitle' => $title,
                'form' => $form,
                'entity' => $domain,
                'VARS' => self::VARS,
            ]
        ];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if (null==$dto->getSubject()) {
                    $files = $request->files->all();
                    $csvcontents = null;
                    foreach ($files as $file) {
                        $csvfile = $file['common']['customFile'];
                        if (null!=$csvfile) {
                            $csvcontents = file_get_contents($csvfile);
                        }
                    }
                    $formData = $form->getData();
                    //dump($formData);
                    $certData = $this->util->createCACert($formData, $csvcontents);
                    //dd($certData);
                }
                if (!empty($certData['error'])) {
                    $this->addFlash('error', $certData['error']);
                } else {
                    $domain->setCertData($certData);
                    $this->repo->save($domain, true);
                    $this->addFlash('success', 'Se creó el certificado de la CA '. $domain->getName());
                }


                $redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $domain->getName() ]);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                $render['template'],
                $render['args'],
                new Response(null, 422)
            );
        }

        return $this->render(
            $render['template'],
            $render['args']
        );
    }

    #[Route(path: '/{id}/client/new/{new}', name: 'client_new', methods: ['GET', 'POST'])]
    public function clientNew(Request $request, UR $userRepo, User $user, int $new = 1): Response
    {
        $dto = new CertDto();
        $domain = $user->getDomain();
        if ($new) {
            $caCertData=$domain->getCertData();
            $caCertout = $caCertData['certdata']['cert'];
            $caCert = openssl_x509_parse($caCertout, false);
            $subject = $caCert['subject'];
            $subject['emailAddress'] = $user->getEmail();
            $subject['commonName'] = $user->getFullname();
        } else {
            $certdata = $user->getCertData();
            $certout = $certdata['certdata']['cert'];
            $cert = openssl_x509_parse($certout, false);
            $subject = $cert['subject'];
        }
        $dto->setDownload(false)
        ->setNew((bool) $new)
        ->setSubject($subject)
        ->setCertType('client')
        ->setDuration('5 years')
        ;

        $form = $this->createForm(
            CertType::class,
            $dto,
            [
                'dto' => $dto,
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'client_new', ['id' => $user->getId()]),
            ]
        );
        $form->handleRequest($request);

        $render = [
            'template' => 'certificates/_form.html.twig',
            'args' => [
                'title' => ($new?'Create':'View'). ' client certificate',
                'form' => $form,
                'entity' => $domain,
            ]
        ];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $formData = $form->getData();
                $this->util->setDomain($domain);
                $certData = $this->util->createClientCert($formData);
                $indexData = $this->util->addToIndex($certData['certdata']['cert']);
                //dd($certData, $indexData);
                $user->setCertData($certData);
                $userRepo->add($user, true);
                $this->repo->updateCAIndex($domain, $indexData);
                $this->addFlash('success', 'Se creo el certificado para '. $user->getFullname() . ' ('.$user->getEmail() . ')');

                $redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $redirectUrl = $this->generateUrl('manage_user_index');
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }
            return $this->render(
                $render['template'],
                $render['args'],
                new Response(null, 422)
            );
        }

        return $this->render(
            $render['template'],
            $render['args']
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
    public function serverNew(Request $request, Domain $domain, ValidatorInterface $validator): Response
    {
        $caCertData=$domain->getCertData();
        $caCertout = $caCertData['certdata']['cert'];
        $caCert = openssl_x509_parse($caCertout, false);
        $dto = new CertDto();
        $dto->setDownload(false)
        ->setSubject($caCert['subject'])
        ->setDomain($domain)
        ->setCertType('server')
        ->setDuration('10 years')
        ->setNew(true)
        ->getCommon()->setCommonName(null)
        ;

        $form = $this->createForm(
            CertType::class,
            $dto,
            [
                'dto' => $dto,
                'action' => $this->generateUrl(self::VARS['PREFIX'] . 'server_new', ['id' => $domain->getId()]),
            ]
        );

        $form->handleRequest($request);
        $render = [
            'template' => 'certificates/_form.html.twig',
            'args' => [
                'title' => 'Create server certificate',
                'form' => $form,
                'entity' => $domain,
            ]
        ];

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $formData = $form->getData();
                $this->util->setDomain($domain);
                $certData = $this->util->createServerCert($formData);
                //dump($formData, $certData);
                $d = $formData->getCommon()->getCommonName();
                $serverCertificate = new ServerCertificate();
                $serverCertificate->setDomain($domain)
                ->setDescription($d!='*'?$d:'wildcard')
                ->setCertData($certData);

                $errors = $validator->validate($serverCertificate);
                if (count($errors)>0) {
                    $form->addError(new FormError($errors[0]->getMessage()));
                    return $this->render(
                        $render['template'],
                        $render['args'],
                        new Response(null, 422)
                    );
                }
                $domain->addServerCertificate($serverCertificate);
                $indexData = $this->util->addToIndex($certData['certdata']['cert']);
                //dd($indexData);
                //$this->repo->add($domain, true);
                $this->repo->updateCAIndex($domain, $indexData);
                $this->addFlash('success', "Se creo el certificado de servidor '".$d."'");


                $redirectUrl = $this->generateUrl('admin_domain_showbyname', [ 'name' => $domain->getName() ]);
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $redirectUrl = $this->generateUrl('manage_user_index');
                }

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirectUrl' => $redirectUrl
                    ]);
                }
                return $this->redirect($redirectUrl);
            }

            return $this->render(
                $render['template'],
                $render['args'],
                new Response(null, 422)
            );
        }

        return $this->render(
            $render['template'],
            $render['args']
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
            return $this->util
            ->certDownload('server', [$certificate, $dtype]);
        } else {
            $this->addFlash('error', "Opción incorrecta $dtype");
        }
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
    }
}
