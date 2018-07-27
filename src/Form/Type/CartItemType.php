<?php
/**
 * Created by Michał Szargut <michal.szargut@contelizer.pl>.
 * File Name: CartItemType.php
 * Date: 27.07.2018
 * Time: 09:57
 */

namespace Acme\SyliusExamplePlugin\Form\Type;


use Symfony\Component\Form\AbstractTypeExtension;
use Sylius\Bundle\OrderBundle\Form\Type\CartItemType as BaseCartItemType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;

class CartItemType extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                /** @var \Acme\SyliusExamplePlugin\Entity\Product $product */
                $product = $event->getData()->getVariant()->getProduct();
                $form = $event->getForm();
                if ($product->isSubscribable()) {
                    $form->add('quantity', ChoiceType::class, [
                        'choices'  => array(
                            '3' => 3,
                            '6' => 6,
                            '12' => 12,
                        ),
                        'label' => 'Ilość miesięcy',
                    ]);
                }else{
                    $form->add('quantity', IntegerType::class, [
                        'attr' => ['min' => 1],
                        'label' => 'sylius.ui.quantity',
                    ]);
                }
            });
    }

    public function getExtendedType()
    {
        return BaseCartItemType::class;
    }
}