<?php


namespace Acme\SyliusExamplePlugin\Subscription;


use Acme\SyliusExamplePlugin\Entity\Order;
use Acme\SyliusExamplePlugin\Entity\Subscription;
use Acme\SyliusExamplePlugin\Entity\SubscriptionStates;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\OrderBundle\NumberAssigner\OrderNumberAssignerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\TokenAssigner\UniqueIdBasedOrderTokenAssigner;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\CompositeOrderProcessor;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SubscriptionService
{
    private $localeContext;
    private $channelContext;
    private $customerRepository;
    private $tokenStorage;
    private $entityManager;
    private $itemQuantityModifier;
    private $compositeOrderProcessor;
    private $numberAssigner;
    private $tokenAssigner;

    public function __construct(
        LocaleContextInterface $localeContext
        , ChannelContextInterface $channelContext
        , CustomerRepositoryInterface $customerRepository
        , TokenStorageInterface $tokenStorage
        , EntityManagerInterface $entityManager
        , OrderItemQuantityModifierInterface $itemQuantityModifier
        , CompositeOrderProcessor $compositeOrderProcessor
        , OrderNumberAssignerInterface $numberAssigner
        , UniqueIdBasedOrderTokenAssigner $tokenAssigner
    )
    {
        $this->localeContext = $localeContext;
        $this->channelContext = $channelContext;
        $this->customerRepository = $customerRepository;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->itemQuantityModifier = $itemQuantityModifier;
        $this->compositeOrderProcessor = $compositeOrderProcessor;
        $this->numberAssigner = $numberAssigner;
        $this->tokenAssigner = $tokenAssigner;

    }

    /**
     * @param Order $order
     * @return bool
     */
    public function createSubscriptionAndAddOrder(Order $order)
    {
        try {
            //more than one item or product is not subscribable
            if ($order->countItems() != 1 || !$order->getItems()[0]->getProduct()->isSubscribable()){
                //Sylius sometimes uses previously created order, make sure to nullify subscription if so
                if(null !== $order->getSubscription()){
                    $order->setSubscription(null);
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();
                }
                return false;
            }
            //order already has subscription
            /** @var Subscription $subscription */
            $subscription = $order->getSubscription();
            if ($subscription !== null){
                //lets check if quantity wasn't changed

                /** @var OrderItemInterface $item */
//                $orderItem = $order->getItems()[0];
//                $quantity = $orderItem->getQuantity();
//                if($subscription->getCycles() !== $quantity){
//                    $subscription->setCycles($quantity);
//                    $this->entityManager->persist($subscription);
//                    $this->entityManager->flush();
//                }

                return false;
            }
        } catch (\Exception $exception) {
            //log it ?
            return false;
        }
        /** @var string $localeCode */
        $localeCode = $this->localeContext->getLocaleCode();
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        /** @var CustomerInterface|null $customer */
        $customer = $order->getCustomer();

        $subscription = new Subscription();
        $subscription->setCustomer($customer);
        $subscription->setChannel($channel);
        $subscription->addOrder($order);
        $subscription->setLocaleCode($localeCode);

        /** @var OrderItemInterface $item */
        $orderItem = $order->getItems()[0];
        $quantity = $orderItem->getQuantity();
        $subscription->setCycles($quantity);
//        $this->itemQuantityModifier->modify($orderItem, 1);
//        $this->compositeOrderProcessor->process($order);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
        return true;
    }

    public function splitSubscriptionOrders(Order $order)
    {
        //Check if subscription order
        $subscription = $order->getSubscription();
        if(null == $subscription){
            return false;
        }

        if ($order->countItems() != 1){
           //more than one item? shouldn't happen!
            throw new BadRequestHttpException('Something is not right :(');
        }

        //Get item quantity
        /** @var OrderItemInterface $item */
        $orderItem = $order->getItems()[0];
        $quantity = $orderItem->getQuantity();

        #TODO: validate subscription cycles == quantity


        //Duplicate orders
        $this->itemQuantityModifier->modify($orderItem, 1);
        $this->compositeOrderProcessor->process($order);
        $orderPayment = $order->getPayments()->first();
        $orderShippingAddress = $order->getShippingAddress();
        $orderBillingAddress = $order->getBillingAddress();
        $order->setValidFrom(new \DateTime());
        $this->entityManager->persist($order);
        $date = new \DateTime();
        $date->modify('midnight first day of next month')->modify(sprintf('+%d days', $subscription->getDayOfTheMonth() - 1));
        for($i = 1; $i < $quantity; ++$i){
            $newOrder = clone $order;
            $newOrder->setNumber(null);
            $this->numberAssigner->assignNumber($newOrder);
            $newOrder->setTokenValue(null);
            $this->tokenAssigner->assignTokenValueIfNotSet($newOrder);
            $newPayment = clone $orderPayment;
            $newOrder->removePayment($orderPayment);
            $newOrder->addPayment($newPayment);
            $newShippingAddress = clone $orderShippingAddress;
            $newOrder->setShippingAddress($newShippingAddress);
            $newBillingAddress = clone $orderBillingAddress;
            $newOrder->setBillingAddress($newBillingAddress);
            $newOrder->setValidFrom(clone $date);
            $date->modify("+1 month");
            $this->entityManager->persist($newOrder);

        }
        $subscription->setState(SubscriptionStates::STATE_IN_PROGRESS);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }


}