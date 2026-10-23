<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BomItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Pozycja BOM (Bill of Materials): ile danego materiału zużywa produkt.
 */
#[ORM\Entity(repositoryClass: BomItemRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_bom_item_product_material', columns: ['product_id', 'material_id'])]
#[UniqueEntity(
    fields: ['product', 'material'],
    message: 'Ten materiał jest już w BOM tego produktu – zmień ilość istniejącej pozycji.',
    errorPath: 'material',
)]
class BomItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bomItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Product $product = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Wybierz materiał.')]
    private ?Material $material = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3)]
    #[Assert\NotBlank(message: 'Ilość jest wymagana.')]
    #[Assert\Positive(message: 'Ilość musi być większa od zera.')]
    private ?string $quantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getMaterial(): ?Material
    {
        return $this->material;
    }

    public function setMaterial(?Material $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
