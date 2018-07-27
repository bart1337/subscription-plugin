<?php
/**
 * Created by Michał Szargut <michal.szargut@contelizer.pl>.
 * File Name: OrderRepository.php
 * Date: 25.07.2018
 * Time: 15:57
 */

namespace Acme\SyliusExamplePlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderRepository as BaseRepository;
use Sylius\Component\Core\Model\OrderInterface;

class OrderRepository extends BaseRepository
{
    public function createByCustomerAndChannelIdQueryBuilder($customerId, $channelId): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customer = :customerId')
            ->andWhere('o.channel = :channelId')
            ->andWhere('o.state != :state')
            ->andWhere('o.valid_from < :now_time')
            ->setParameter('now_time', new \DateTime())
            ->setParameter('customerId', $customerId)
            ->setParameter('channelId', $channelId)
            ->setParameter('state', OrderInterface::STATE_CART)
            ;
    }
}