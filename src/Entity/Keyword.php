<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Keyword
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(Patterns::ID, message: Patterns::ID_MESSAGE)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;

        return $this;
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
}
