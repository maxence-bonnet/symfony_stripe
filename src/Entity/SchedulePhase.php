<?php

namespace App\Entity;

use App\Repository\SchedulePhaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * No direct relation with Stripe
 */
#[ORM\Entity(repositoryClass: SchedulePhaseRepository::class)]
class SchedulePhase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * Defines how many times the price will be invoiced (recurring interval being defined by Subscription->Price).
     */
    #[ORM\Column(type: 'integer')]
    private $iterations;

    /**
     * Used to define the order of the phases if there are several (and so useless if only one phase is used).
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $priorityOrder = 1;

    #[ORM\ManyToOne(targetEntity: Price::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $price;

    #[ORM\ManyToMany(targetEntity: SubscriptionSchedule::class, inversedBy: 'schedulePhases')]
    private $subscriptionSchedules;

    public function __construct()
    {
        $this->subscriptionSchedules = new ArrayCollection();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIterations(): ?int
    {
        return $this->iterations;
    }

    public function setIterations(int $iterations): self
    {
        $this->iterations = $iterations;

        return $this;
    }

    public function getPriorityOrder(): ?int
    {
        return $this->priorityOrder;
    }

    public function setPriorityOrder(?int $priorityOrder): self
    {
        $this->priorityOrder = $priorityOrder;

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

    /**
     * @return Collection<int, SubscriptionSchedule>
     */
    public function getSubscriptionSchedules(): Collection
    {
        return $this->subscriptionSchedules;
    }

    public function addSubscriptionSchedule(SubscriptionSchedule $subscriptionSchedule): self
    {
        if (!$this->subscriptionSchedules->contains($subscriptionSchedule)) {
            $this->subscriptionSchedules[] = $subscriptionSchedule;
        }

        return $this;
    }

    public function removeSubscriptionSchedule(SubscriptionSchedule $subscriptionSchedule): self
    {
        $this->subscriptionSchedules->removeElement($subscriptionSchedule);

        return $this;
    }
}
