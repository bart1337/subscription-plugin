<?php

namespace Acme\SyliusExamplePlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Core\Model\Order as BaseOrder;

class Order extends BaseOrder
{
    /**
     * @var Subscription|null
     */
    private $subscription;

    /**
     * @var \DateTimeInterface|null
     */
    private $valid_from;

    /**
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    /**
     * @param Subscription|null $subscription
     */
    public function setSubscription(?Subscription $subscription): void
    {
        $this->subscription = $subscription;
    }

    public function isSubscriptionType() : bool
    {
        return null !== $this->subscription;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->valid_from;
    }

    /**
     * @param \DateTimeInterface|null $valid_from
     */
    public function setValidFrom(?\DateTimeInterface $valid_from): void
    {
        $this->valid_from = $valid_from;
    }


    public function resetItemsCollection()
    {
        $this->items = new ArrayCollection();
    }

    function __clone()
    {
        $this->items = new ArrayCollection();
        $this->shipments = new ArrayCollection();
        $this->adjustments = new ArrayCollection();
//        $this->promotions = new ArrayCollection();
        $this->setShippingAddress(clone $this->getShippingAddress());
        $this->setBillingAddress(clone $this->getBillingAddress());
        $this->setCreatedAt(new \DateTime());
//        $newOrder->setShippingAddress(clone $baseShippingAddress);
//        $newOrder->setBillingAddress(clone $baseBillingAddress);
    }









}
