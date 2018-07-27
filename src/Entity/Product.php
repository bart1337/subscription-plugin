<?php

namespace Acme\SyliusExamplePlugin\Entity;

use Sylius\Component\Core\Model\Product as BaseProduct;

class Product extends BaseProduct
{
    /**
     * @var boolean
     */
    private $subscribable = false;

    /**
     * @return bool
     */
    public function isSubscribable(): bool
    {
        return $this->subscribable;
    }

    /**
     * @param bool $subscribable
     */
    public function setSubscribable(bool $subscribable): void
    {
        $this->subscribable = (bool) $subscribable;
    }








}
