<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ein Tagebucheintrag mit seinen Blöcken.
 */
#[ORM\Entity]
class Entry
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
    private ?string $date = null;

    #[ORM\ManyToOne(targetEntity: Trip::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Trip $trip = null;

    /** @var Collection<int, Topic> */
    #[ORM\ManyToMany(targetEntity: Topic::class)]
    #[ORM\JoinTable(name: 'entry_topic')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(onDelete: 'CASCADE')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $topics;

    /** @var Collection<int, Block> */
    #[ORM\OneToMany(targetEntity: Block::class, mappedBy: 'entry', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $blocks;

    public function __construct()
    {
        $this->topics = new ArrayCollection();
        $this->blocks = new ArrayCollection();
    }

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

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
    {
        $this->date = '' === $date ? null : $date;

        return $this;
    }

    public function getTrip(): ?Trip
    {
        return $this->trip;
    }

    public function setTrip(?Trip $trip): static
    {
        $this->trip = $trip;

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

    /** @return Collection<int, Block> */
    public function getBlocks(): Collection
    {
        return $this->blocks;
    }

    public function addBlock(Block $block): static
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks->add($block);
            $block->setEntry($this);
        }

        return $this;
    }

    public function removeBlock(Block $block): static
    {
        $this->blocks->removeElement($block);

        return $this;
    }
}
