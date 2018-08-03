<?php

namespace Acme\SyliusExamplePlugin\Menu;


use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AccountMenuListener
{
    public function addAccountMenuItems(MenuBuilderEvent $event)
    {
        $menu = $event->getMenu();
        $menu
            ->addChild('new', ['route' => 'contelizer_shop_account_subscription_index'])
            ->setLabel('app.ui.my_subscriptions')
            ->setLabelAttribute('icon', 'star')
        ;
    }
}