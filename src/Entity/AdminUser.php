<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Anmeldung für die Verwaltung (/admin). Angelegt über `php bin/console app:admin:create`.
 */
#[ORM\Entity]
#[ORM\Table(name: 'admin_user')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /** Nur der Hash, nie das Passwort selbst */
    #[ORM\Column]
    private string $password;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_ADMIN'];

    public function __construct(string $email)
    {
        $this->email = mb_strtolower($email);
        $this->password = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->email);

        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): static
    {
        $this->password = $hashedPassword;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }
}
