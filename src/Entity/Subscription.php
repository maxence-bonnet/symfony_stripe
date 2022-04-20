<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Direct relation with Stripe Subscription https://stripe.com/docs/api/subscriptions
 * 
 * Note : Stripe Subscriptions are fine with simple Subscriptions plans without end date, etc.
 * For Subscriptions with preset periods, it is more confortable to use the Stripe Subscription Schedule (and our associated SubscriptionSchedule)
 * 
 * Note : in our case subscription->collection_method = charge_automatically
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    const STATUS = ['incomplete', 'incomplete_expired', 'trialing', 'active', 'past_due', 'canceled', 'unpaid', 'canceling'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * Example : sub_1Ko81XGl9zFk4v1kKoV7vWbz
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $stripeSubscriptionId;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    /**
     * This value will be an estimate used to notify the user of his next charge / payment.
     * It is determined according to the creation date of the Subscription and its Payment interval.
     * The value should be similar to Stripe Subscription current_period_end, which changes at each payment interval. 
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $nextInvoiceAt;

    /**
     * Stripe Subscription possible values are :
     * ['incomplete', 'incomplete_expired', 'trialing', 'active', 'past_due', 'canceled', 'unpaid']
     * 
     * Subscription moves into 'incomplete' if the initial payment attempt fails.
     * Once the first invoice is paid, the subscription moves into an 'active' state. 
     * If the first invoice is not paid within 23 hours, the subscription transitions to 'incomplete_expired'.
     * It becomes 'past_due' when payment to renew it fails, and 'canceled' or 'unpaid' (depending on your subscriptions settings) 
     * when Stripe has exhausted all payment retry attempts.
     * 
     */
    #[ORM\Column(type: 'string', length: 30)]
    private $status = 'incomplete';

    /**
     * Price included in the current Plan (shortcut)
     */
    #[ORM\ManyToOne(targetEntity: Price::class)]
    private $price;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'subscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $customer;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $endsAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $lastInvoiceId;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (in_array($status, self::STATUS)) {
            $this->status = $status;
        } else {
            throw new \LogicException(
                sprintf(
                    'Given status for %s in not correct : "%s", available values are [%s]',
                    Subscription::class,
                    $status,
                    join(', ', self::STATUS)
                )
            );
        }

        return $this;
    }

    public function getNextInvoiceAt(): ?\DateTimeImmutable
    {
        return $this->nextInvoiceAt;
    }

    public function setNextInvoiceAt(?\DateTimeImmutable $nextInvoiceAt): self
    {
        $this->nextInvoiceAt = $nextInvoiceAt;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getLastInvoiceId(): ?string
    {
        return $this->lastInvoiceId;
    }

    public function setLastInvoiceId(?string $lastInvoiceId): self
    {
        $this->lastInvoiceId = $lastInvoiceId;

        return $this;
    }
}
