<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Used to find a newly created Subscription or to renew it
     */
    public function findBySubscriptionIdJoinAll(string $stripeSubscriptionId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->select('s', 'c', 'price', 'prod')
            ->innerJoin('s.customer', 'c')
            ->innerJoin('s.price', 'price')
            ->innerJoin('price.product', 'prod')
            ->andWhere('s.stripeSubscriptionId = :stripeSubscriptionId')
            ->setParameter('stripeSubscriptionId', $stripeSubscriptionId)
            ->getQuery()
            ->getOneOrNullResult()            
        ;
    }

    /**
     * Return given Subscription with joined datas
     */
    public function joinAll(Subscription $subscription): ?Subscription
    {
        return $this->createQueryBuilder('s')
        ->select('s', 'c', 'price', 'prod')
        ->innerJoin('s.customer', 'c')
        ->innerJoin('s.price', 'price')
        ->innerJoin('price.product', 'prod')
        ->andWhere('s.id = :subscriptionId')
        ->setParameter('subscriptionId', $subscription->getId())
        ->getQuery()
        ->getOneOrNullResult()            
        ;
    }


    /**
     * Used for Customer current Subscriptions list
     * @return Subscription[]
     */
    public function findAllByCustomerJoinAll(?Customer $customer): array
    {
        return $this->createQueryBuilder('s')
        ->select('s', 'c', 's', 'prod', 'price')
        ->innerJoin('s.customer', 'c')
        ->innerJoin('s.price', 'price')
        ->innerJoin('price.product', 'prod')
        ->andWhere('s.customer = :customer')
        ->setParameter('customer', $customer)
        ->getQuery()
        ->getResult();   
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Subscription $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Subscription $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
