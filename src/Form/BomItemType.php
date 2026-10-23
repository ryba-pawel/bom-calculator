<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\BomItem;
use App\Entity\Material;
use App\Repository\MaterialRepository;
use App\Twig\NumberFormatExtension;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<BomItem>
 */
final class BomItemType extends AbstractType
{
    public function __construct(
        private readonly NumberFormatExtension $numberFormat,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('material', EntityType::class, [
                'class' => Material::class,
                'label' => 'Materiał',
                'placeholder' => '— wybierz materiał —',
                'query_builder' => static fn (MaterialRepository $repository): QueryBuilder => $repository
                    ->createQueryBuilder('m')
                    ->orderBy('m.name', \SortDirection::Ascending),
                'choice_label' => fn (Material $material): string => sprintf(
                    '%s – %s / %s',
                    $material->getName(),
                    $this->numberFormat->formatPln($material->getUnitPrice()),
                    $material->getUnit()?->value,
                ),
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Ilość',
                'input' => 'string',
                'scale' => 3,
                'html5' => true,
                'attr' => ['step' => '0.001'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BomItem::class,
        ]);
    }
}
