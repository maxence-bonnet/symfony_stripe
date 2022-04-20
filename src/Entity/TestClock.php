<?php

namespace App\Entity;

use App\Repository\TestClockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Form\Extension\Validator\Constraints\Form;

/**
 * Direct relation with Stripe Test Clock https://stripe.com/docs/api/test_clocks
 * 
 * Only for developpement and test purpose.
 * 
 * We can manipulate time through test clocks to test subscriptions behaviours.
 * A clock can be linked to a Customer at the time of its creation, 
 * everything that will be associated with it afterwards will also be linked to its clock.
 * 
 * https://stripe.com/docs/billing/testing/test-clocks?dashboard-or-api=api#create-clock
 * 
 * Note :
 * The time to advance the test clock must be after the test clock’s current frozen time.
 * Cannot be more than two intervals in the future from the shortest subscription in this test clock. 
 * If there are no subscriptions in this test clock, it cannot be more than two years in the future.
 */
#[ORM\Entity(repositoryClass: TestClockRepository::class)]
class TestClock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $stripeTestClockId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $name = 'TestClock';

    #[ORM\Column(type: 'datetime')]
    private $frozenTime;

    #[ORM\OneToMany(mappedBy: 'testClock', targetEntity: Customer::class, cascade: ['persist', 'remove'])]
    private $customers;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
        $this->frozenTime = (new \DateTime(date('Y-m-d H:i')));
    }

    public function __toString()
    {
        return "{$this->name} ({$this->frozenTime->format('Y-m-d H:i:s')}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripeTestClockId(): ?string
    {
        return $this->stripeTestClockId;
    }

    public function setStripeTestClockId(string $stripeTestClockId): self
    {
        $this->stripeTestClockId = $stripeTestClockId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFrozenTime(): ?\DateTime
    {
        return $this->frozenTime;
    }

    public function setFrozenTime(\DateTime $frozenTime): self
    {
        $this->frozenTime = $frozenTime;

        return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }

    public function addCustomer(Customer $customer): self
    {
        if (!$this->customers->contains($customer)) {
            $this->customers[] = $customer;
            $customer->setTestClock($this);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): self
    {
        if ($this->customers->removeElement($customer)) {
            // set the owning side to null (unless already changed)
            if ($customer->getTestClock() === $this) {
                $customer->setTestClock(null);
            }
        }

        return $this;
    }
}
