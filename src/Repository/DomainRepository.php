<?php

namespace App\Repository;

use App\Entity\Domain as Entity;
use App\Utils\ReadConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
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

    public function add(Entity $entity, bool $flush = false): void
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

    public function makeMaildir(Entity $entity, bool $add = true)
    {
        if ($add) {
            $this->add($entity, true);
        }
        $base=$this->config->findParameter('virtual_mailbox_base');
        mkdir($base.'/'.$entity->getId());
        system("cd $base;ln -s " . $entity->getId() . " " . $entity->getName());
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
        $this->add($entity, true);
    }

//    /**
//     * @return Booking[] Returns an array of Booking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Booking
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
