<?php
/**
 * Created by Michał Szargut <michal.szargut@contelizer.pl>.
 * File Name: AdminOrderShowMenuListener.php
 * Date: 27.07.2018
 * Time: 14:08
 */

namespace Acme\SyliusExamplePlugin\Menu;


use Sylius\Bundle\AdminBundle\Event\OrderShowMenuBuilderEvent;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminOrderMenuItems(OrderShowMenuBuilderEvent $event)
    {
        $menu = $event->getMenu();

    }
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $sales = $menu->getChildren()['sales'];

        $sales->addChild('subscriptions', [
            'route' => 'app_admin_subscription_index',
        ])
            ->setName('Subskrypcje')
            ->setLabelAttribute('icon', 'sync')
        ;
    }
}