<?php
/**
 * Created by PhpStorm.
 * User: Laptop06
 * Date: 27.07.2018
 * Time: 09:52
 */

namespace Acme\SyliusExamplePlugin\Entity;


final class SubscriptionStates
{
    public const STATE_CART = 'cart';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETED = 'completed';
    public const STATE_CANCELLED = 'cancelled';
}