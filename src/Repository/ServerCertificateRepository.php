<?php

namespace App\Repository;

use App\Entity\ServerCertificate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerCertificate>
 *
 * @method ServerCertificate|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServerCertificate|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServerCertificate[]    findAll()
 * @method ServerCertificate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServerCertificateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerCertificate::class);
    }

//    /**
//     * @return ServerCertificate[] Returns an array of ServerCertificate objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ServerCertificate
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
