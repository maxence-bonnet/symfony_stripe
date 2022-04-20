<?php

namespace App\Entity;

use App\Repository\UserSubscriptionScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Direct relation with Stripe Subscription Schedule https://stripe.com/docs/api/subscription_schedules
 * 
 * This is an alternative for better planning of subscriptions.
 * https://stripe.com/docs/billing/subscriptions/subscription-schedules/use-cases
 * 
 * Note: Subscription Schedule can be initialized from an existing Stripe Subscription
 */
#[ORM\Entity(repositoryClass: UserSubscriptionScheduleRepository::class)]
class UserSubscriptionSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * Example : sub_sched_1KoPvHGl9zFk4v1kJ3y9eEDv
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $stripeSubscriptionScheduleId;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $startAt;

    /**
     * Configures how the subscription schedule behaves when it ends. 
     * Possible values are 'release' or 'cancel' with the default being 'release' :
     *  - 'release' will end the subscription schedule and keep the underlying subscription running.
     *  - 'cancel' will end the subscription schedule and cancel the underlying subscription.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private $endBehaviour = 'cancel';

    /**
     * Possible values from API :
     * ['not_started', 'active', 'completed', 'released', 'canceled']
     */
    #[ORM\Column(type: 'string', length: 30)]
    private $status;

    // #[ORM\OneToOne(mappedBy: 'schedule', targetEntity: Subscription::class, cascade: ['persist', 'remove'])]
    // private $Subscription;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getEndBehaviour(): ?string
    {
        return $this->endBehaviour;
    }

    public function setEndBehaviour(string $endBehaviour): self
    {
        $this->endBehaviour = $endBehaviour;

        return $this;
    }

    // public function getStripeSubscriptionScheduleId(): ?string
    // {
    //     return $this->stripeSubscriptionScheduleId;
    // }

    // public function setStripeSubscriptionScheduleId(string $stripeSubscriptionScheduleId): self
    // {
    //     $this->stripeSubscriptionScheduleId = $stripeSubscriptionScheduleId;

    //     return $this;
    // }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUserSubscription(): ?UserSubscription
    {
        return $this->userSubscription;
    }

    public function setUserSubscription(?UserSubscription $userSubscription): self
    {
        // unset the owning side of the relation if necessary
        if ($userSubscription === null && $this->userSubscription !== null) {
            $this->userSubscription->setSchedule(null);
        }

        // set the owning side of the relation if necessary
        if ($userSubscription !== null && $userSubscription->getSchedule() !== $this) {
            $userSubscription->setSchedule($this);
        }

        $this->userSubscription = $userSubscription;

        return $this;
    }
}
