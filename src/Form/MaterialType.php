<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Material;
use App\Enum\Unit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Material>
 */
final class MaterialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'attr' => ['placeholder' => 'np. Kantówka sosnowa 90×90 mm'],
            ])
            ->add('unit', EnumType::class, [
                'class' => Unit::class,
                'label' => 'Jednostka',
                'choice_label' => static fn (Unit $unit): string => $unit->label(),
                'placeholder' => '— wybierz jednostkę —',
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Cena jednostkowa (PLN)',
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Material::class,
        ]);
    }
}
