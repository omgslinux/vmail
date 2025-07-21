<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface as FFI;
use App\Entity\Alias;
use App\Entity\Domain;
use App\Entity\User;
use App\Utils\Certificate;
use App\Utils\ReadConfig;
use App\Form\AutoreplyType;
use App\Form\UserType;
use App\Form\DomainType;
use App\Repository\AutoreplyRepository as AUR;
use App\Repository\DomainRepository as REPO;
use App\Repository\UserRepository as UR;

/**
 * Domain controller.
 */
#[Route(path: '/admin/domain', name: 'admin_domain_')]
class DomainController extends AbstractController
{

    const VARS = [
        'PREFIX' => 'admin_domain_',
        'included' => 'domain/_form',
        'tdir' => 'domain',
        'BASEDIR' => 'domain/',
        'modalId' => 'domains',
    ];

    private $repo;
    public function __construct(REPO $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Lists all domain entities.
     */
    #[Route(path: '/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $entity = new Domain();
        $form = $this->createForm(DomainType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->manageMaildir($entity);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'index');
        }

        return $this->render(self::VARS['BASEDIR'] . '/index.html.twig', array(
            'entities' => $this->repo->findAll(),
            'targetPrefix' => 'domains',
            'title' => 'Domain list',
            'form' => $form->createView(),
            'VARS' => self::VARS,
        ));
    }

    #[Route(path: '/live', name: 'index_live', methods: ['GET', 'POST'])]
    public function indexLive(Request $request): Response
    {

        return $this->render(
            self::VARS['BASEDIR'] . '/index_live.html.twig',
            [
                //'entities' => $this->repo->findAll(),
                'tagPrefix' => 'admin',
                'modalId' => 'domains',
                'title' => 'Domain list',
            ]
        );
    }


    /**
     * Edit a domain entity.
     */
    #[Route(path: '/{id}/edit/', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Domain $entity, ReadConfig $config): Response
    {
        $form = $this->createForm(
            DomainType::class,
            $entity,
            [
                'action' => $this->generateUrl(self::VARS['PREFIX'] .'edit', ['id' => $entity->getId()])
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->manageMaildir($entity);

            return $this->redirectToRoute(self::VARS['PREFIX'] . 'showbyname', [ 'name' => $entity->getName() ]);
        }

        return $this->render(
            self::VARS['BASEDIR'] . '/_form.html.twig',
            [
                'entity' => $entity,
                'modalTitle' => 'Domain edit',
                'form' => $form->createView(),
                'VARS' => self::VARS,
                'ajax' => true,
                'delete_form' => true,
            ]
        );
    }


    #[Route(path: '/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Domain $entity, REPO $repo, ReadConfig $config): Response
    {
        if ($this->isCsrfTokenValid('delete'.$entity->getId(), $request->request->get('_token'))) {
            $base=$config->findParameter('virtual_mailbox_base');
            system("rm -rf " . $base . "/" . $entity->getName());
            rmdir($base . "/" . $entity->getId());
            $this->repo->remove($entity, true);
        }

        return $this->redirectToRoute(self::VARS['PREFIX'] . 'index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route(path: '/show/byname/{name}', name: 'showbyname', methods: ['GET', 'POST'])]
    public function showByName(Request $request, $name, Certificate $certUtil)
    {
        $activeTab = $request->query->get('activetab', 0);

        $reload = false;

        // Para la entidad (el dominio)
        $entity=$this->repo->findOneByName($name);
        $oldname=$entity->getName();
        $users=$aliases=[];

        foreach ($entity->getUsers() as $user) {
            if ($user->isList()) {
                $aliases[]=$user;
            } else {
                $users[]=$user;
            }
        }
        $form = $this->createForm(DomainType::class, $entity);
        // Fin de definicion de la entidad

        // Formulario de la entidad
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->repo->makeMaildir($entity, true);

            //$reload = true;
        }


        if ($reload) {
            return $this->redirectToRoute(self::VARS['PREFIX'] . 'show', ['id' => $entity->getId()]);
        }

        $VARS = [
            'origin' => $this->generateUrl(self::VARS['PREFIX'] . 'showbyname', ['name' => $name ]),
            'PREFIX' => 'admin_domain_',
            'modalId' => 'domains',
        ];

        // Populate certificate tab
        $certificates = [];
        foreach ($entity->getServerCertificates() as $certificate) {
            if (null!=$certData=$entity->getCertData()) {
                $certout = $certData['certdata']['cert'];
                $cert = openssl_x509_parse($certout, false);
                $certInterval = [
                    'notBefore' => $certUtil::convertUTCTime2Date($cert['validFrom']),
                    'notAfter'  => $certUtil::convertUTCTime2Date($cert['validTo']),
                ];
                //dump($certData, $cert, $certInterval);
                $data = [
                    'description' => $certificate->getDescription(),
                    'domain' => $certificate->getDomain(),
                    'certdata' => $certUtil->extractX509Data($certificate),
                    'interval' => $certInterval,
                    'entity' => $certificate,
                ];
                $certificates[] = $data;
            }
        }
        // End of populate certificate tab

        $tabs = [
            [
                'template' => 'tabs/users/_index.html.twig',
                'title' => 'Users',
                'context' => [
                    'users' => $users,
                    'modalSize' => 'modal-lg',
                    'VARS' => $VARS,
                ]
            ],
            [
            'template' => 'tabs/aliases/_index.html.twig',
            'title' => 'Alias',
            'context' => [
                'aliases' => $aliases,
                'modalSize' => 'modal-lg'
                ]
            ],
            [
            'template' => 'tabs/certificates/_index.html.twig',
            'title' => 'Certificates',
            'context' => [
                'entities' => $certificates,
                'modalSize' => 'modal-xl'
                ]
            ],
        ];

        return $this->render(
            self::VARS['BASEDIR'] . '/show.html.twig',
            [
                'entity' => $entity,
                'targetPrefix' => 'domains',
                'tabs' => [
                    'tabId' => 'domainTabs',
                    'activeTab' => $activeTab,
                    'tabs' => $tabs
                ],
                'form' => $form->createView(),
                'VARS' => $VARS,
            ]
        );
    }


    /**
     * Creates a form to show a entity.
     */
    #[Route(path: '/show/byid/{id}', name: 'show', methods: ['GET', 'POST'])]
    public function showAction(Request $request, Domain $domain)
    {
        return $this->redirectToRoute(self::VARS['PREFIX'] . 'showbyname', ['name' => $domain->getName()]);
    }
}
