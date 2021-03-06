<?php

namespace VmailBundle\Repository;

/**
 * AliasRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AliasRepository extends \Doctrine\ORM\EntityRepository
{
    public function findVirtualByDomain($domain)
    {
        //$qb = $this->get('VmailBundle:User');
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u')
            ->from('VmailBundle:User')
            ->where('u.domain = :domain')
            ->andWhere('u.list = 1')
            ->setParameter('domain', $domain)
        ;
        $query = $qb->getQuery();
        return $query->getResult();

    }

    public function findAliasByDomain($domain)
    {
        //$qb = $this->get('VmailBundle:Alias');
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u')
            ->from('VmailBundle:User')
            ->where('u.domain = :domain')
            ->andWhere('u.list = 1')
            ->setParameter('domain', $domain)
        ;
        $query = $qb->getQuery();
        return $query->getResult();

    }

}
