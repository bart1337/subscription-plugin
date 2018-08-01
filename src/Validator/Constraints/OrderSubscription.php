<?php

namespace Acme\SyliusExamplePlugin\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

class OrderSubscription extends Constraint
{
    /**
     * @var string
     */
    public $message = 'contelizer.order.subscription_not_valid';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return get_class($this).'Validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}