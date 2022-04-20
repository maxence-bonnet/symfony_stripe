<?php

namespace App\Entity;

use App\Repository\SubscriptionScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionScheduleRepository::class)]
class SubscriptionSchedule
{
    const END_BEHAVIOUR = [
        'release' => 'release',
        'cancel' => 'cancel',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    
    /**
     * Configures how the subscription schedule behaves when it ends. 
     * Possible values are 'release' or 'cancel' with the default being 'release' :
     *  - 'release' will end the subscription schedule and keep the underlying subscription running.
     *  - 'cancel' will end the subscription schedule and cancel the underlying subscription.
     */
    #[ORM\Column(type: 'string', length: 20)]
    private $endBehaviour;

    #[ORM\ManyToMany(targetEntity: SchedulePhase::class, mappedBy: 'subscriptionSchedules')]
    private $schedulePhases;

    public function __construct()
    {
        $this->schedulePhases = new ArrayCollection();
    }
    
    /**
     * Each Phase (SchedulePhase) defines the payment to be made and the number of iterations.
     * In our case there will probably be only one phase (12 x 1 month for example)
     * but the possibility of linking several phases brings a lot of flexibility.
     */

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection<int, SchedulePhase>
     */
    public function getSchedulePhases(): Collection
    {
        return $this->schedulePhases;
    }

    public function addSchedulePhase(SchedulePhase $schedulePhase): self
    {
        if (!$this->schedulePhases->contains($schedulePhase)) {
            $this->schedulePhases[] = $schedulePhase;
            $schedulePhase->addSubscriptionSchedule($this);
        }

        return $this;
    }

    public function removeSchedulePhase(SchedulePhase $schedulePhase): self
    {
        if ($this->schedulePhases->removeElement($schedulePhase)) {
            $schedulePhase->removeSubscriptionSchedule($this);
        }

        return $this;
    }
}
