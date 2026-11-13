<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Ein Block eines Eintrags. Eine Tabelle für alle Blocktypen; welche Felder gelten,
 * bestimmt `type` (siehe validateForType()).
 */
#[ORM\Entity]
class Block
{
    /** Beschriftung in der Verwaltung → gespeicherter Wert */
    public const TYPES = ['Text' => 'text', 'Bild' => 'image', 'Video' => 'video', 'Galerie' => 'gallery'];
    public const TYPE_VALUES = ['text', 'image', 'video', 'gallery'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Entry::class, inversedBy: 'blocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Entry $entry = null;

    /** Reihenfolge im Eintrag: 1, 2, 3 … */
    #[ORM\Column]
    private int $position = 1;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::TYPE_VALUES)]
    private string $type = 'text';

    /** Nur Textblock */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    /** Nur Bild- und Videoblock. Ein Medium, das noch benutzt wird, lässt sich nicht löschen. */
    #[ORM\ManyToOne(targetEntity: Media::class)]
    #[ORM\JoinColumn(onDelete: 'RESTRICT')]
    private ?Media $media = null;

    /** Nur Bild- und Videoblock: Bildunterschrift für diese Verwendung */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $caption = null;

    /**
     * Nur Galerieblock: IDs der Fotos in ihrer Reihenfolge.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $galleryMediaIds = [];

    #[Assert\Callback]
    public function validateForType(ExecutionContextInterface $context): void
    {
        $missing = match ($this->type) {
            'text' => null === $this->content || '' === trim($this->content) ? ['content', 'Bitte einen Text eingeben.'] : null,
            'image', 'video' => null === $this->media ? ['media', 'Bitte ein Foto oder Video wählen.'] : null,
            'gallery' => [] === $this->galleryMediaIds ? ['galleryMediaIds', 'Bitte mindestens ein Foto wählen.'] : null,
            default => null,
        };
        if (null !== $missing) {
            $context->buildViolation($missing[1])->atPath($missing[0])->addViolation();
        }
    }

    public function __toString(): string
    {
        return \sprintf('%d. %s', $this->position, array_search($this->type, self::TYPES, true) ?: $this->type);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntry(): ?Entry
    {
        return $this->entry;
    }

    public function setEntry(?Entry $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 1;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type ?? 'text';

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = '' === $caption ? null : $caption;

        return $this;
    }

    /** @return list<string> */
    public function getGalleryMediaIds(): array
    {
        return $this->galleryMediaIds;
    }

    /** @param array<array-key, string>|null $ids Formulare liefern auch Arrays mit Lücken in den Schlüsseln */
    public function setGalleryMediaIds(?array $ids): static
    {
        $this->galleryMediaIds = array_values($ids ?? []);

        return $this;
    }
}
