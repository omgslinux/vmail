<?php

namespace App\Repository;

use App\Entity\Autoreply as Entity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Autoreply>
 *
 * @method Autoreply|null find($id, $lockMode = null, $lockVersion = null)
 * @method Autoreply|null findOneBy(array $criteria, array $orderBy = null)
 * @method Autoreply[]    findAll()
 * @method Autoreply[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AutoreplyRepository extends ServiceEntityRepository
{
    private $config;

    public function __construct(ManagerRegistry $registry, private UserRepository $uRepo)
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

    public function manageRequest(array $request): Entity
    {
        $user = null;
        $reply = null;
dump($request['user']);
        if (null!=($email=$request['user'])) {
            $user = $this->uRepo->loadUserByUsername($email);
            if (null!=$user) {
                $reply = $user->getReply();
            }
        }
        if (null==$reply) {
            $reply = new Entity();
            if (null!=$user) {
                $reply->setUser($user);
            }
            $reply
            ->setMessage($request['message'])
            ->setStartDate(new \DateTime($request['startdate']))
            ->setEndDate(new \DateTime($request['enddate']))
            ->setActive($request['active']);
        }

        return $reply;
    }

//    /**
//     * @return Autoreply[] Returns an array of Autoreply objects
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

//    public function findOneBySomeField($value): ?Autoreply
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
