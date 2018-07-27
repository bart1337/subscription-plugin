<?php
/**
 * Created by Michał Szargut <michal.szargut@contelizer.pl>.
 * File Name: ProductType.php
 * Date: 26.07.2018
 * Time: 12:03
 */

namespace Acme\SyliusExamplePlugin\Form\Type;


use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductType extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('subscribable', CheckboxType::class, [
            'required' => false,
            'label' => 'Dostępny w subskrybcji',
        ]);
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return \Sylius\Bundle\ProductBundle\Form\Type\ProductType::class;
    }
}