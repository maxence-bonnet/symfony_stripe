<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Direct relation with Stripe Price https://stripe.com/docs/api/prices
 */
#[ORM\Entity(repositoryClass: PriceRepository::class)]
class Price
{
    const CURRENCY = [
        'euros [€]' => 'eur',
        'US dollars [$]' => 'usd',
    ];

    const TYPE = [
        'recurring' => 'recurring',
        'onetime' => 'onetime',
    ];

    const RECURRING_INTERVAL = [
        'day' => 'day',
        'week' => 'week',
        'month' => 'month',
        'year' => 'year',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * Example : price_1KlrEqGl9zFk4v1kVZmKHL0I
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $stripePriceId;

    #[ORM\Column(type: 'string', length: 60)]
    private $type;

    #[ORM\Column(type: 'float')]
    private $price;

    #[ORM\Column(type: 'string', length: 10)]
    private $currency;
    
    /**
     * Type of interval between each payment requirement (recurring payments only)
     */
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private $recurringInterval;

    /**
     * Number of interval type between each payment requirement (recurring payments only)
     * Example : 
     *  recurringInterval = month;
     *  recurringCount = 3;
     * => one payment every 3 months;
     * 
     * Doc : 
     * The number of intervals between subscription billings. 
     * For example, interval=month and interval_count=3 bills every 3 months.
     * 
     * Note : Maximum of one year interval allowed (1 year, 12 months, or 52 weeks).
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $recurringCount;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'prices', cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    private $product;

    public function __construct()
    {
    
    }

    /** QueryBuilder needed for n+1 issues */
    public function __toString()
    {
        return "{$this->getProduct()->getName()} : {$this->price} € / {$this->type} {$this->recurringCount} {$this->recurringInterval}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(string $stripePriceId): self
    {
        $this->stripePriceId = $stripePriceId;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getRecurringInterval(): ?string
    {
        return $this->recurringInterval;
    }

    public function setRecurringInterval(?string $recurringInterval): self
    {
        $this->recurringInterval = $recurringInterval;

        return $this;
    }

    public function getRecurringCount(): ?int
    {
        return $this->recurringCount;
    }

    public function setRecurringCount(?int $recurringCount): self
    {
        $this->recurringCount = $recurringCount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }
}
