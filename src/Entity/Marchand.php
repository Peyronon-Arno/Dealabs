<?php

namespace App\Entity;

use App\Repository\MarchandRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MarchandRepository::class)]
class Marchand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\OneToMany(mappedBy: 'marchand', targetEntity: Promo::class)]
    private ?Collection $promos = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getPromos(): ?Collection
    {
        return $this->promos;
    }

    public function setPromo(Promo $promos): self
    {
        // set the owning side of the relation if necessary
        if ($promos->getMarchand() !== $this) {
            $promos->setMarchand($this);
        }

        $this->promos = $promos;

        return $this;
    }
}
