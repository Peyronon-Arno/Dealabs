<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
class Alert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $temperatureMin = null;

    #[ORM\Column]
    private ?bool $notify = null;

    #[ORM\ManyToOne(inversedBy: 'alerts')]
    private ?User $user = null;

    #[ORM\ManyToMany(targetEntity: Deal::class, inversedBy: 'alerts')]
    private Collection $deals;

    #[ORM\Column]
    private ?bool $hasBeenShown = null;

    public function __construct()
    {
        $this->deals = new ArrayCollection();
    }

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

    public function getTemperatureMin(): ?int
    {
        return $this->temperatureMin;
    }

    public function setTemperatureMin(int $temperatureMin): self
    {
        $this->temperatureMin = $temperatureMin;

        return $this;
    }

    public function isNotify(): ?bool
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Deal>
     */
    public function getDeals(): Collection
    {
        return $this->deals;
    }

    public function addDeal(Deal $deal): self
    {
        if (!$this->deals->contains($deal)) {
            $this->deals->add($deal);
        }

        return $this;
    }

    public function removeDeal(Deal $deal): self
    {
        $this->deals->removeElement($deal);

        return $this;
    }

    public function containsDeal($deal)
    {
        return $this->deals->contains($deal);
    }

    public function isHasBeenShown(): ?bool
    {
        return $this->hasBeenShown;
    }

    public function setHasBeenShown(bool $hasBeenShown): self
    {
        $this->hasBeenShown = $hasBeenShown;

        return $this;
    }
}
