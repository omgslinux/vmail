<?php

namespace App\Repository;

use App\Entity\Domain as Entity;
use App\Utils\ReadConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domain>
 *
 * @method Domain|null find($id, $lockMode = null, $lockVersion = null)
 * @method Domain|null findOneBy(array $criteria, array $orderBy = null)
 * @method Domain[]    findAll()
 * @method Domain[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DomainRepository extends ServiceEntityRepository
{
    private $config;

    public function __construct(ManagerRegistry $registry, ReadConfig $config)
    {
        $this->config = $config;

        parent::__construct($registry, Entity::class);
    }

    public function save(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Entity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function manageMaildir(Entity $entity)
    {
        $base=$this->config->findParameter('virtual_mailbox_base');

        if (null==$entity->getId()) {
            $this->save($entity, true);
            mkdir($base.'/'.$entity->getId());
            system("cd $base;ln -sf " . $entity->getId() . " " . $entity->getName());
        } else {
            $unitOfWork = $this->getEntityManager()->getUnitOfWork();
            $unitOfWork->computeChangeSets(); // Calcula cambios pendientes
            $changes = $unitOfWork->getEntityChangeSet($entity);

            if (isset($changes['name'])) {
                [$oldName, $newName] = $changes['name'];
                // Ya sabes si ha cambiado
                //dd($oldName, $newName);
                if ($oldName!=$newName) {
                    system("cd $base;mv $oldName $newName");
                }
            }
            $this->save($entity, true);
        }
    }

    public function rawsql($rawsql, bool $flush = false): void
    {
        $conn=$this->getEntityManager()->getConnection();
        $conn->exec($rawsql);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function updateCAIndex(Entity $entity, $indexData): void
    {
        $caCertData = $entity->getCertData();
        foreach ($indexData as $key => $value) {
            $caCertData['index'][$key] = $value;
        }
        $caCertData['serial'] = ++$value['serialNumber'];
        $entity->setCertData($caCertData);
        //dd($entity, $indexData);
        $this->save($entity, true);
    }

    public function certificateSubmit($form)
    {
        $user = $form->getData();
        if (null==$user->getDomain() && $form->get('domain')) {
            $domain = $this->dr->find($form->get('domain'));
            $user->setDomain($domain);
        }
        $plainPassword = $user->getPlainpassword();
        if (!empty($plainPassword)) {
            $user->setPassword($this->encodePassword($plainPassword));
            $this->RS->getSession()->getFlashBag()->add('success', 'Password successfully modified');
        }
        $this->save($user, true);
        if ($user->getSendEmail()) {
            $this->sendWelcomeMail($user);
        }
    }

//    /**
//     * @return Domain[] Returns an array of Domain objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Domain
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
