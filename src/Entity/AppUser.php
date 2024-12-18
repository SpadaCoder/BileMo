<?php

namespace App\Entity;

use App\Repository\AppUserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "app_api_detail_user",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"show_users"})
 * )
 *
 * * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "app_api_delete_user",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups={"show_users"}, excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 */
#[ORM\Entity(repositoryClass: AppUserRepository::class)]
class AppUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['show_users'])]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Groups(['show_users'])]
    #[Assert\NotBlank(message: "Le prénom est obligatoire")]
    private ?string $firstname = null;

    #[ORM\Column(length: 30)]
    #[Groups(['show_users'])]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['show_users'])]
    #[Assert\NotBlank(message: "Le mail est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le mail doit faire au moins {{ limit }} caractères", maxMessage: "Le mail ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $email = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'users', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['show_users'])]
    private ?Customer $customer = null;

    public function __contruct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
}
