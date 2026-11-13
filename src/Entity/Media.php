<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ein Foto oder Video. Die Datei liegt in public/media/; gespeichert wird nur ihr Name.
 */
#[ORM\Entity]
class Media
{
    public const KIND_IMAGE = 'image';
    public const KIND_VIDEO = 'video';

    #[ORM\Id]
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(Patterns::ID, message: Patterns::ID_MESSAGE)]
    private ?string $id = null;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: [self::KIND_IMAGE, self::KIND_VIDEO])]
    private string $kind = self::KIND_IMAGE;

    /** Dateiname in public/media/, z. B. "athen.svg" */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Bitte eine Datei hochladen.')]
    private ?string $file = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex(Patterns::DATE, message: Patterns::DATE_MESSAGE)]
    private ?string $date = null;

    /** Ortsname als Text, z. B. "Bergen, Norwegen" – keine Koordinaten. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $place = null;

    /** @var Collection<int, Topic> */
    #[ORM\ManyToMany(targetEntity: Topic::class)]
    #[ORM\JoinTable(name: 'media_topic')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $topics;

    /** @var Collection<int, Keyword> */
    #[ORM\ManyToMany(targetEntity: Keyword::class)]
    #[ORM\JoinTable(name: 'media_keyword')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $keywords;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->keywords = new ArrayCollection();
    }

    public function __toString(): string
    {
        return \sprintf('%s (%s)', $this->place ?? $this->id, $this->file);
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

    public function getKind(): string
    {
        return $this->kind;
    }

    public function setKind(string $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): static
    {
        $this->file = $file;
        // Die Art ergibt sich aus der Datei – nicht von Hand wählbar.
        if (null !== $file) {
            $this->kind = 'mp4' === strtolower(pathinfo($file, \PATHINFO_EXTENSION)) ? self::KIND_VIDEO : self::KIND_IMAGE;
        }

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
    {
        $this->date = '' === $date ? null : $date;

        return $this;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(?string $place): static
    {
        $this->place = '' === $place ? null : $place;

        return $this;
    }

    /** @return Collection<int, Topic> */
    public function getTopics(): Collection
    {
        return $this->topics;
    }

    public function addTopic(Topic $topic): static
    {
        if (!$this->topics->contains($topic)) {
            $this->topics->add($topic);
        }

        return $this;
    }

    public function removeTopic(Topic $topic): static
    {
        $this->topics->removeElement($topic);

        return $this;
    }

    /** @return Collection<int, Keyword> */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword): static
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);
        }

        return $this;
    }

    public function removeKeyword(Keyword $keyword): static
    {
        $this->keywords->removeElement($keyword);

        return $this;
    }
}
