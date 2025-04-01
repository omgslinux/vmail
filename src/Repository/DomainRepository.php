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
        $oldEntity = $this->find($entity->getId());
        if ($add) {
            $this->add($entity, true);
        }
        $base=$this->config->findParameter('virtual_mailbox_base');
        if (null!=$oldEntity) {
            $oldname=$oldEntity->getName();
            $newName = $entity->getName();
            if ($oldname!=$newName) {
                system("cd $base;mv $oldname $newName;ln -sf " . $entity->getId() . " " . $newName);
            }
        } else {
            mkdir($base.'/'.$entity->getId());
            system("cd $base;ln -sf " . $entity->getId() . " " . $entity->getName());
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
        $this->add($entity, true);
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
        $this->add($user, true);
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
