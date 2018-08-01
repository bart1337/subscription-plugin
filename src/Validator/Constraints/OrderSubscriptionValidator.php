<?php

namespace Acme\SyliusExamplePlugin\Validator\Constraints;


use Acme\SyliusExamplePlugin\Entity\Subscription;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;
use Webmozart\Assert\Assert;
class OrderSubscriptionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function validate($order, Constraint $constraint): void
    {
        /** @var OrderInterface $order */
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var OrderSubscription $constraint */
        Assert::isInstanceOf($constraint, OrderSubscription::class);

        /** @var Subscription $subscription */
        $subscription = $order->getSubscription();
        if (null != $subscription) {
            /** @var int $countItems */
            $countItems = $order->countItems();
            if ($countItems != 1) {
                $this->context->addViolation(
                    $constraint->message
                );
            }
        }


    }
}