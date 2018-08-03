<?php

namespace Acme\SyliusExamplePlugin\Repository;

use Acme\SyliusExamplePlugin\Entity\Subscription;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use SyliusLabs\AssociationHydrator\AssociationHydrator;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends EntityRepository implements RepositoryInterface
{
    /**
     * @var AssociationHydrator
     */
    private $associationHydrator;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $entityManager, Mapping\ClassMetadata $class)
    {
        parent::__construct($entityManager, $class);

        $this->associationHydrator = new AssociationHydrator($entityManager, $class);
    }


    public function findOneByIdAndCustomer($id, CustomerInterface $customer): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.customer = :customer')
            ->andWhere('s.id = :id')
            ->setParameter('customer', $customer)
            ->setParameter('id', (int)$id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
