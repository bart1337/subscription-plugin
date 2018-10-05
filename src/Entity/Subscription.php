<?php

namespace Acme\SyliusExamplePlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Webmozart\Assert\Assert;
class Subscription implements ResourceInterface
{
    use TimestampableTrait;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $notes;


    /**
     * @var ChannelInterface
     */
    protected $channel;

    /**
     * @var string
     */
    protected $localeCode;

    /**
     * @var CustomerInterface
     */
    protected $customer;

    /**
     * @var Collection|Order[]
     */
    protected $orders;

    /**
     * @var string|null
     */
    private $paymentToken;

    /**
     * @var integer|null
     */
    private $cycles;

    /**
     * @var string
     */
    private $state = SubscriptionStates::STATE_CART;


    /**
     * @var integer
     */
    private $dayOfTheMonth = 5;

    /**
     * @var boolean
     */
    private $paidAhead = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->orders = new ArrayCollection();
    }

    public function addOrder(Order $order): void
    {
        if (!$this->hasOrder($order)) {
            $this->orders[] = $order;
            $order->setSubscription($this);
        }
    }

    public function removeOrder(Order $order): void
    {
        $this->orders->removeElement($order);
        $order->setSubscription(null);
    }

    public function hasOrder(Order $order): bool
    {
        return $this->orders->contains($order);
    }


    /**
     * @return Collection
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return null|string
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param null|string $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }


    /**
     * {@inheritdoc}
     */
    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocaleCode(?string $localeCode): void
    {
        Assert::string($localeCode);

        $this->localeCode = $localeCode;
    }

    /**
     * @return CustomerInterface|null
     */
    public function getCustomer(): ?CustomerInterface
    {
        return $this->customer;
    }

    /**
     * @param CustomerInterface $customer
     */
    public function setCustomer(?CustomerInterface $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return null|string
     */
    public function getPaymentToken(): ?string
    {
        return $this->paymentToken;
    }

    /**
     * @param null|string $paymentToken
     */
    public function setPaymentToken(?string $paymentToken): void
    {
        $this->paymentToken = $paymentToken;
    }

    /**
     * @return int
     */
    public function getDayOfTheMonth(): int
    {
        return $this->dayOfTheMonth;
    }

    /**
     * @param int $dayOfTheMonth
     */
    public function setDayOfTheMonth(int $dayOfTheMonth): void
    {
        $this->dayOfTheMonth = $dayOfTheMonth;
    }

    /**
     * @return int|null
     */
    public function getCycles(): ?int
    {
        return $this->cycles;
    }

    /**
     * @param int|null $cycles
     */
    public function setCycles(?int $cycles): void
    {
        $this->cycles = $cycles;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }


    /**
     * @return bool
     */
    public function isPaidAhead(): bool
    {
        return $this->paidAhead;
    }

    /**
     * @param bool $paidAhead
     * @return Subscription
     */
    public function setPaidAhead(bool $paidAhead): self
    {
        $this->paidAhead = $paidAhead;
        return $this;
    }

}

