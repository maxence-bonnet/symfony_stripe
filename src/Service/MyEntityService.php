<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Price;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Subscription;
use App\Entity\TestClock;
use App\Exception\SubscriptionNotFoundException;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class MyEntityService
{
    public function __construct (
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
    )
    {

    }

    public function add(bool $flush, object ...$objects)
    {
        foreach ($objects as $object) {
            $this->entityManager->persist($object);
        }
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /** CUSTOMER */

    public function createCustomer(User $user, \Stripe\Customer $stripeCustomer, ?TestClock $testClock = null)
    {
        return (new Customer)
            ->setUser($user)
            ->setStripeCustomerId($stripeCustomer->id)
            ->setTestClock($testClock)
            ;
    }
    /** /CUSTOMER */

    /** SUBSCRIPTION */

    public function createSubscription(Customer $customer, Price $price, \Stripe\Subscription $stripeSubscription): Subscription
    {
        return (new Subscription())
            ->setCustomer($customer)
            ->setPrice($price)
            ->setStripeSubscriptionId($stripeSubscription->id)
            ->setLastInvoiceId($stripeSubscription->latest_invoice->id)
            ->setStatus('incomplete')
            ;
    }

    /**
     * @param string $subscriptionId from query paramters
     * @throws SubscriptionNotFoundException
     */
    public function getSubscriptionFromStripeId(?string $subscriptionId): Subscription
    {
        if (!$subscriptionId) {
            throw new SubscriptionNotFoundException('Missing subscriptionId parameter');
        }

        $subscription = $this->subscriptionRepository->findBySubscriptionIdJoinAll($subscriptionId);

        if (null === $subscription) {
            throw new SubscriptionNotFoundException('Unknown subscription : ' . $subscriptionId);
        }

        return $subscription;
    }
    /** /SUBSCRIPTION */

    public function entityIsModified(object $prievious, object $new): bool
    {
        if ($new instanceof Product) {
            return $this->productIsModified($prievious, $new);
        }
        return false; // ERROR NOT HANDLED
    }

    private function productIsModified(Product $previous, Product $new): bool
    {
        if ($previous->getName() !== $new->getName()) {
            return true;
        }
        if ($previous->getDescription() !== $new->getDescription()) {
            return true;
        }
        return false;
    }
}