<?php

declare(strict_types=1);

namespace App\Content;

use App\Domain\ContentSource;
use App\Domain\Model\Block\Block;
use App\Domain\Model\Block\GalleryBlock;
use App\Domain\Model\Block\ImageBlock;
use App\Domain\Model\Block\TextBlock;
use App\Domain\Model\Block\VideoBlock;
use App\Domain\Model\Entry;
use App\Domain\Model\Keyword;
use App\Domain\Model\MediaItem;
use App\Domain\Model\MediaKind;
use App\Domain\Model\Topic;
use App\Domain\Model\Trip;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Liest die Inhalte aus MariaDB (gepflegt in der Verwaltung /admin) und übersetzt die
 * Doctrine-Entitäten in Domain-Objekte. Seiten, API und Vue merken keinen Unterschied
 * zu JsonContentSource.
 */
final class DoctrineContentSource implements ContentSource
{
    /** Öffentlicher Pfad der Mediendateien, entspricht public/media/ */
    public const MEDIA_PATH = '/media/';

    /** @var list<Entry>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Auswahllisten (z. B. Fotos-Filter) erscheinen alphabetisch. */
    public function trips(): array
    {
        return array_map(
            static fn (Entity\Trip $t): Trip => new Trip((string) $t->getId(), (string) $t->getTitle(), $t->getStartDate(), $t->getEndDate()),
            $this->em->getRepository(Entity\Trip::class)->findBy([], ['title' => 'ASC']),
        );
    }

    public function topics(): array
    {
        return array_map(
            static fn (Entity\Topic $t): Topic => new Topic((string) $t->getId(), (string) $t->getName()),
            $this->em->getRepository(Entity\Topic::class)->findBy([], ['name' => 'ASC']),
        );
    }

    public function keywords(): array
    {
        return array_map(
            static fn (Entity\Keyword $k): Keyword => new Keyword((string) $k->getId(), (string) $k->getName()),
            $this->em->getRepository(Entity\Keyword::class)->findBy([], ['name' => 'ASC']),
        );
    }

    public function mediaItems(): array
    {
        /** @var list<Entity\Media> $media */
        $media = $this->em->createQueryBuilder()
            ->select('m', 't', 'k')
            ->from(Entity\Media::class, 'm')
            ->leftJoin('m.topics', 't')
            ->leftJoin('m.keywords', 'k')
            ->orderBy('m.id')
            ->getQuery()
            ->getResult();

        return array_map(static fn (Entity\Media $m): MediaItem => new MediaItem(
            (string) $m->getId(),
            MediaKind::from($m->getKind()),
            self::MEDIA_PATH.$m->getFile(),
            $m->getDate(),
            $m->getPlace(),
            self::ids($m->getTopics()),
            self::ids($m->getKeywords()),
        ), $media);
    }

    public function entries(): array
    {
        // Einträge werden pro Anfrage mehrfach gebraucht (z. B. Galerie und Facetten) → einmal laden
        return $this->entries ??= $this->loadEntries();
    }

    /** @return list<Entry> */
    private function loadEntries(): array
    {
        /** @var list<Entity\Entry> $entries */
        $entries = $this->em->createQueryBuilder()
            ->select('e', 'tr', 't', 'b', 'm')
            ->from(Entity\Entry::class, 'e')
            ->leftJoin('e.trip', 'tr')
            ->leftJoin('e.topics', 't')
            ->leftJoin('e.blocks', 'b')
            ->leftJoin('b.media', 'm')
            ->orderBy('e.id')
            ->getQuery()
            ->getResult();

        return array_map(static fn (Entity\Entry $e): Entry => new Entry(
            (string) $e->getId(),
            (string) $e->getTitle(),
            $e->getDate(),
            $e->getTrip()?->getId(),
            self::ids($e->getTopics()),
            array_values(array_map(self::block(...), $e->getBlocks()->toArray())),
        ), $entries);
    }

    private static function block(Entity\Block $b): Block
    {
        $id = 'b'.$b->getId();
        $mediaId = (string) $b->getMedia()?->getId();

        return match ($b->getType()) {
            'image' => new ImageBlock($id, $mediaId, $b->getCaption()),
            'video' => new VideoBlock($id, $mediaId, $b->getCaption()),
            'gallery' => new GalleryBlock($id, $b->getGalleryMediaIds()),
            default => new TextBlock($id, (string) $b->getContent()),
        };
    }

    /**
     * @param iterable<Entity\Topic|Entity\Keyword> $items
     *
     * @return list<string>
     */
    private static function ids(iterable $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = (string) $item->getId();
        }

        return $ids;
    }
}
