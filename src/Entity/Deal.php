<?php

namespace App\Entity;

use App\Repository\DealRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\GetDealsOfTheWeekController;
use App\Controller\GetSaveDealsOfTheUserController;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new GetCollection(
            name: 'get_deals_of_the_week',
            uriTemplate: 'deals_of_the_week',
            controller: GetDealsOfTheWeekController::class,
            normalizationContext: ['groups' => ['deal:list']],
        ),
        new GetCollection(
            name: 'get_save_deals_of_the_user',
            uriTemplate: 'get_save_deals_of_the_user',
            controller: GetSaveDealsOfTheUserController::class,
            normalizationContext: ['groups' => ['deal:list']],
            security: 'is_granted("ROLE_USER")',
        ),
    ],
)]
class Deal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['deal:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['deal:list'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['deal:list'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['deal:list'])]
    private ?float $price = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    private ?User $User = null;

    #[Vich\UploadableField(mapping: 'deals', fileNameProperty: 'imageName', size: 'imageSize')]
    #[Groups(['deal:list'])]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['deal:list'])]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['deal:list'])]
    private ?int $imageSize = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['deal:list'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'Deal', targetEntity: Notation::class)]
    private Collection $notations;

    #[ORM\OneToMany(mappedBy: 'deal', targetEntity: Comment::class)]
    // #[Groups(['deal:list'])]
    private ?Collection $comments = null;

    #[ORM\Column]
    #[Groups(['deal:list'])]
    private ?int $degree = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['deal:list'])]
    private ?\DateTimeInterface $expirationDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['deal:list'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'favoriteDeals')]
    private ?Collection $userFavorite = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'signalements')]
    private Collection $userSignalements;

    #[ORM\ManyToMany(targetEntity: Alert::class, mappedBy: 'deals')]
    private Collection $alerts;

    public function __construct()
    {
        $this->notations = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->userSignalements = new ArrayCollection();
        $this->alerts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    #[Groups(['deal:list'])]
    public function getCategoryTitle(): ?string
    {
        return $this->category->getTitle();
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): self
    {
        $this->User = $User;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    /**
     * @return Collection<int, Notation>
     */
    public function getNotations(): Collection
    {
        return $this->notations;
    }

    public function addNotation(Notation $notation): self
    {
        if (!$this->notations->contains($notation)) {
            $this->notations->add($notation);
            $notation->setDeal($this);
        }

        return $this;
    }

    public function removeNotation(Notation $notation): self
    {
        if ($this->notations->removeElement($notation)) {
            // set the owning side to null (unless already changed)
            if ($notation->getDeal() === $this) {
                $notation->setDeal(null);
            }
        }

        return $this;
    }

    /**
     * Check if the user has already rated the deal
     *
     * @param User $user
     * @return boolean
     */
    public function isRatedByUser(User $user): bool
    {
        foreach ($this->getNotations() as $notation) {
            if ($notation->getUser() === $user) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate the score of the deal
     */
    public function getScore(): float
    {
        $score = 0;
        foreach ($this->getNotations() as $notation) {
            $score += $notation->getScore();
        }
        return $score;
    }

    public function getDegree(): ?int
    {
        return $this->degree;
    }

    public function setDegree(int $degree): self
    {
        $this->degree = $degree;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment); // Ajouter cette ligne pour ajouter le commentaire à la collection
            $comment->setDeal($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        $this->comments->removeElement($comment);
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTimeInterface $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUserFavorite(): Collection
    {
        return $this->userFavorite;
    }

    public function addUserFavorite(User $userFavorite): self
    {
        if (!$this->userFavorite->contains($userFavorite)) {
            $this->userFavorite->add($userFavorite);
            $userFavorite->addFavoriteDeal($this);
        }

        return $this;
    }

    public function removeUserFavorite(User $userFavorite): self
    {
        if ($this->userFavorite->removeElement($userFavorite)) {
            $userFavorite->removeFavoriteDeal($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUserSignalements(): Collection
    {
        return $this->userSignalements;
    }

    public function addUserSignalement(User $userSignalement): self
    {
        if (!$this->userSignalements->contains($userSignalement)) {
            $this->userSignalements->add($userSignalement);
            $userSignalement->addSignalement($this);
        }

        return $this;
    }

    public function removeUserSignalement(User $userSignalement): self
    {
        if ($this->userSignalements->removeElement($userSignalement)) {
            $userSignalement->removeSignalement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): self
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->addDeal($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): self
    {
        if ($this->alerts->removeElement($alert)) {
            $alert->removeDeal($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }

    #[Groups(['deal:list'])]
    public function getUserName(): string
    {
        return $this->User->getUsername();
    }
}
