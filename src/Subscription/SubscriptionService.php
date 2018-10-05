<?php


namespace Acme\SyliusExamplePlugin\Subscription;


use Acme\SyliusExamplePlugin\Entity\Order;
use Acme\SyliusExamplePlugin\Entity\Subscription;
use Acme\SyliusExamplePlugin\Entity\SubscriptionStates;
use Doctrine\ORM\EntityManagerInterface;
use SM\Factory\Factory;
use Sylius\Bundle\OrderBundle\NumberAssigner\OrderNumberAssignerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Factory\AddressFactoryInterface;
use Sylius\Component\Core\Factory\CartItemFactory;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\TokenAssigner\UniqueIdBasedOrderTokenAssigner;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\OrderTransitions;
use Sylius\Component\Order\Processor\CompositeOrderProcessor;
use Sylius\Component\Payment\Factory\PaymentFactoryInterface;
use Sylius\Component\Promotion\Action\PromotionApplicator;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\Services\BlueMedia;


class SubscriptionService
{
    public const PAID_AHEAD = 'PAID_AHEAD';

    private $localeContext;
    private $channelContext;
    private $customerRepository;
    private $tokenStorage;
    private $entityManager;
    private $itemQuantityModifier;
    private $compositeOrderProcessor;
    private $numberAssigner;
    private $tokenAssigner;
    private $paymentFactory;
    private $orderFactory;
    private $orderItemFactory;
    private $orderRepository;
    private $shipmentFactory;
    private $orderManager;
    private $stateMachineFactory;
    private $blueMediaService;
    private $adjustmentFactory;

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
        , PaymentFactoryInterface $paymentFactory
        , FactoryInterface $orderFactory
        , CartItemFactory $orderItemFactory
        , OrderRepositoryInterface $orderRepository
        , FactoryInterface $shipmentFactory
        , EntityManagerInterface $orderManager
        , Factory $stateMachineFactory
        , BlueMedia $blueMediaService
        , AdjustmentFactoryInterface $adjustmentFactory
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
        $this->paymentFactory = $paymentFactory;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderRepository = $orderRepository;
        $this->shipmentFactory = $shipmentFactory;
        $this->orderManager = $orderManager;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->blueMediaService = $blueMediaService;
        $this->adjustmentFactory = $adjustmentFactory;
    }

    /**
     * @param Order $order
     * @return bool
     */
    public function createSubscriptionAndAddOrder(Order $order)
    {
        try {
            //more than one item or product is not subscribable
            if ($order->countItems() != 1 || !$order->getItems()[0]->getProduct()->isSubscribable()) {
                //Sylius sometimes uses previously created order, make sure to nullify subscription if so
                if ($order->isSubscriptionType()) {
                    $order->setSubscription(null);
                    $this->entityManager->persist($order);
                    $this->entityManager->flush();
                }
                return false;
            }
            //order already has subscription
            if ($order->isSubscriptionType()) {
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
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
        return true;
    }

    public function splitSubscriptionOrders(Order $order)
    {
        //TODO: Test with coupons,
        //TODO: Adjust payments (pay once for all orders, set order_payment_status = paid)
        //Check if subscription order
        $subscription = $order->getSubscription();
        if (!$order->isSubscriptionType()) {
            return false;
        }

        if ($order->countItems() != 1) {
            //more than one item? shouldn't happen!
            throw new Exception('Something is not right :(');
        }

        //Get item quantity
        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()[0];
        $quantity = $orderItem->getQuantity();

        if ($quantity != $subscription->getCycles()) {
            //somebody changed quantity during checkout process
            $subscription->setCycles($quantity);
        }
        if($order->toBePaidAhead()){
            $subscription->setPaidAhead(true);
        }


        /** @var PaymentInterface $basePayment */
        $basePayment = $order->getPayments()->first();
        /** @var PaymentMethodInterface $basePaymentMethod */
        $basePaymentMethod = $basePayment->getMethod();
        /** @var string $baseCurrencyCode */
        $baseCurrencyCode = $basePayment->getCurrencyCode();

        $this->itemQuantityModifier->modify($orderItem, 1);
        $this->entityManager->persist($orderItem);
        $order->getPayments()->clear();
        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setMethod($basePaymentMethod);
        $payment->setCurrencyCode($baseCurrencyCode);
        $order->addPayment($payment);
        $order->setValidFrom(new \DateTime());


        if($subscription->isPaidAhead()){
            //adding adjustments for other months
            $totalPrice = $order->getTotal();
            for ($i = 1; $i < $quantity; ++$i) {
                /** @var AdjustmentInterface $adjustment */
                $adjustment = $this->adjustmentFactory->createNew();
                $adjustment->setNeutral(false);
                $adjustment->setAmount($totalPrice);
                $adjustment->setLabel(sprintf('Płatność z góry za miesiąc #%s', $i+1));
                $adjustment->setType($this::PAID_AHEAD);
                $order->addAdjustment($adjustment);
            }
        }

        $this->compositeOrderProcessor->process($order);
        $payment->setState('new');
        $this->entityManager->remove($basePayment);
        $this->entityManager->persist($payment);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $date = new \DateTime();
        $date->modify('midnight first day of next month')->modify(sprintf('+%d days', $subscription->getDayOfTheMonth() - 1));


        for ($i = 1; $i < $quantity; ++$i) {
            $newOrder = clone $order;
            $newOrder->setNumber(null);
            $this->numberAssigner->assignNumber($newOrder);
            $this->tokenAssigner->assignTokenValue($newOrder);
            /** @var PaymentInterface $payment */
            $payment = $this->paymentFactory->createNew();
            $payment->setMethod($basePaymentMethod);
            $payment->setCurrencyCode($baseCurrencyCode);
            $newOrder->addPayment($payment);
            $variant = $orderItem->getVariant();
            /** @var OrderItemInterface $newOrderItem */
            $newOrderItem = $this->orderItemFactory->createNew();
            $newOrderItem->setVariant($variant);
            $newOrderItem->setVariantName($variant->getName());
            $newOrderItem->setProductName($variant->getProduct()->getName());
            $this->itemQuantityModifier->modify($newOrderItem, 1);
            $newOrder->addItem($newOrderItem);


            /** @var ShipmentInterface $newShipment */
            $newShipment = $this->shipmentFactory->createNew();
            /** @var ShipmentInterface $shipment */
            $shipment = $order->getShipments()->first();
            $newShipment->setMethod($shipment->getMethod());
            $newOrder->addShipment($newShipment);
            $newOrder->setValidFrom(clone $date);
            $newOrder->recalculateAdjustmentsTotal();
            $this->compositeOrderProcessor->process($newOrder);

            if($subscription->isPaidAhead()){
                /** @var AdjustmentInterface $adjustment */
                $adjustment = $this->adjustmentFactory->createNew();
                $adjustment->setNeutral(false);
                $adjustment->setAmount(-$totalPrice);
                $adjustment->setLabel(sprintf('Subskrypcja opłacona z góry', $i));
                $adjustment->setType($this::PAID_AHEAD);
                $newOrder->addAdjustment($adjustment);
                $newOrder->getPayments()->clear();
            }else{
                $this->entityManager->persist($payment);
            }

            $this->entityManager->persist($newOrderItem);
            $this->entityManager->persist($newOrder);
            $this->entityManager->persist($newShipment);
            $this->entityManager->flush();
            $date->modify("+1 month");
        }

        $subscription->setState(SubscriptionStates::STATE_IN_PROGRESS);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

    /**
     * @param Subscription $subscription
     * @return int - liczba anulowanych zamówień
     * @author Damian Frańczuk <damian.franczuk@contelizer.pl>
     * @author Bartosz Nejman
     */
    public function cancelSubscription(Subscription $subscription)
    {
        $orders = $subscription->getOrders();
        $counter = 0;
        foreach ($orders as $order) {
            $now = new \DateTime();
            $validFrom = $order->getValidFrom();
            $paymentState = $order->getPaymentState();
            if($validFrom > $now &&
                (
                    $paymentState === OrderPaymentStates::STATE_AWAITING_PAYMENT
                || $paymentState === OrderPaymentStates::STATE_CART
                )){
                try {
                    $this->bluemediaService->deactivateRecurring($order);
                }catch(\Exception $e){

                }
                $stateMachineOrder = $this->stateMachineFactory->get($order, OrderTransitions::GRAPH);
                $stateMachineOrder->apply(OrderTransitions::TRANSITION_CANCEL);
                $this->orderManager->flush();
                $counter++;
            }
        }
        $subscription->setState(SubscriptionStates::STATE_CANCELLED);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
        return $counter;
    }

    /**
     * @param AddressInterface $originalAddress
     * @return Address
     * Almost same as __clone however this should be safer.
     */
    public function createAddress(AddressInterface $originalAddress)
    {

        /** @var Address $address */
        $address = $this->addressFactory->createNew();
        $address->setFirstName($originalAddress->getFirstName());
        $address->setLastName($originalAddress->getLastName());
        $address->setPhoneNumber($originalAddress->getPhoneNumber());
        $address->setStreet($originalAddress->getStreet());
        $address->setCompany($originalAddress->getCompany());
        $address->setCity($originalAddress->getCity());
        $address->setPostcode($originalAddress->getPostcode());
        $address->setCountryCode($originalAddress->getCountryCode());
        $address->setProvinceCode($originalAddress->getProvinceCode());
        $address->setProvinceName($originalAddress->getProvinceName());
        if(method_exists($originalAddress, 'getInpostCode') && method_exists($address, 'setInpostCode')){
            $address->setInpostCode($originalAddress->getInpostCode());
        }
        return $address;
    }

}