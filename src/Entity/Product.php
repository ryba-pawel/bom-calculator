<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Nazwa produktu jest wymagana.')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    /** Marża w procentach, liczona od kosztu materiałów. */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank(message: 'Marża jest wymagana.')]
    #[Assert\Range(
        notInRangeMessage: 'Marża musi mieścić się w przedziale od {{ min }}% do {{ max }}%.',
        min: 0,
        max: 999.99,
    )]
    private ?string $marginPercent = null;

    /** @var Collection<int, BomItem> */
    #[ORM\OneToMany(targetEntity: BomItem::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $bomItems;

    public function __construct()
    {
        $this->bomItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMarginPercent(): ?string
    {
        return $this->marginPercent;
    }

    public function setMarginPercent(?string $marginPercent): static
    {
        $this->marginPercent = $marginPercent;

        return $this;
    }

    /**
     * @return Collection<int, BomItem>
     */
    public function getBomItems(): Collection
    {
        return $this->bomItems;
    }

    public function addBomItem(BomItem $bomItem): static
    {
        if (!$this->bomItems->contains($bomItem)) {
            $this->bomItems->add($bomItem);
            $bomItem->setProduct($this);
        }

        return $this;
    }

    public function removeBomItem(BomItem $bomItem): static
    {
        $this->bomItems->removeElement($bomItem);

        return $this;
    }
}
