<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Eine Nachricht aus dem Kontaktformular. Wird nur angelegt (DoctrineContactInbox) und in der
 * Verwaltung gelesen oder gelöscht – nie bearbeitet.
 */
#[ORM\Entity]
class Message
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $id, string $name, string $email, string $message, \DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->createdAt = $createdAt;
    }

    public function __toString(): string
    {
        return \sprintf('%s, %s', $this->name, $this->createdAt->format('d.m.Y'));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
