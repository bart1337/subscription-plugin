<?php
/**
 * Created by Michał Szargut <michal.szargut@contelizer.pl>.
 * File Name: AdminOrderShowMenuListener.php
 * Date: 27.07.2018
 * Time: 14:08
 */

namespace Acme\SyliusExamplePlugin\EventListener\Menu;


use Sylius\Bundle\AdminBundle\Event\OrderShowMenuBuilderEvent;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class AdminOrderShowMenuListener
{
    public function addAdminOrderMenuItems(OrderShowMenuBuilderEvent $event)
    {
        $menu = $event->getMenu();

        dump($menu);die;
    }
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $sales = $menu->getChildren()['sales'];

        $sales->addChild('subscriptions', [
            'route' => 'sylius_admin_order_fulfill',
        ])
            ->setName('Subskrypcje')
            ->setAttribute('icon', 'times')
        ;
    }
}