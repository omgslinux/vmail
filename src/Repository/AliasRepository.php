<?php

namespace App\Repository;

use App\Entity\Alias as Entity;
use App\Utils\ReadConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alias>
 *
 * @method Alias|null find($id, $lockMode = null, $lockVersion = null)
 * @method Alias|null findOneBy(array $criteria, array $orderBy = null)
 * @method Alias[]    findAll()
 * @method Alias[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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

    public function findVirtualByDomain($domain)
    {
        //$qb = $this->get('VmailBundle:User');
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u')
            ->from('App:User')
            ->where('u.domain = :domain')
            ->andWhere('u.list = 1')
            ->setParameter('domain', $domain)
        ;
        $query = $qb->getQuery();
        return $query->getResult();
    }

    public function findAliasByDomain($domain)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u')
            ->from('App:User')
            ->where('u.domain = :domain')
            ->andWhere('u.list = 1')
            ->setParameter('domain', $domain)
        ;
        $query = $qb->getQuery();
        return $query->getResult();
    }

//    /**
//     * @return Booking[] Returns an array of Alias objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Alias
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
