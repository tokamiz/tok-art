<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Speicherform einer Reise (Doctrine). Die Seiten arbeiten mit App\Domain\Model\Trip;
 * DoctrineContentSource übersetzt zwischen beiden.
 */
#[ORM\Entity]
class Trip
{
    #[ORM\Id]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(Patterns::ID, message: Patterns::ID_MESSAGE)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex(Patterns::DATE, message: Patterns::DATE_MESSAGE)]
    private ?string $startDate = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex(Patterns::DATE, message: Patterns::DATE_MESSAGE)]
    private ?string $endDate = null;

    public function __toString(): string
    {
        return (string) $this->title;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(?string $startDate): static
    {
        $this->startDate = '' === $startDate ? null : $startDate;

        return $this;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): static
    {
        $this->endDate = '' === $endDate ? null : $endDate;

        return $this;
    }
}
