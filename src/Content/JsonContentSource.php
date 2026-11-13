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

/**
 * Liest die Inhalte aus den JSON-Dateien in data/ und baut daraus Domain-Objekte.
 * Jede Datei wird pro Anfrage nur einmal gelesen.
 */
final class JsonContentSource implements ContentSource
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $cache = [];

    public function __construct(
        private readonly string $dataDir,
    ) {
    }

    public function trips(): array
    {
        return array_map(
            static fn (array $row): Trip => new Trip(
                self::string($row, 'id'),
                self::string($row, 'title'),
                self::optionalString($row, 'startDate'),
                self::optionalString($row, 'endDate'),
            ),
            $this->read('trips'),
        );
    }

    public function topics(): array
    {
        return array_map(
            static fn (array $row): Topic => new Topic(self::string($row, 'id'), self::string($row, 'name')),
            $this->read('topics'),
        );
    }

    public function keywords(): array
    {
        return array_map(
            static fn (array $row): Keyword => new Keyword(self::string($row, 'id'), self::string($row, 'name')),
            $this->read('keywords'),
        );
    }

    public function mediaItems(): array
    {
        return array_map(
            static fn (array $row): MediaItem => new MediaItem(
                self::string($row, 'id'),
                MediaKind::from(self::string($row, 'kind')),
                self::string($row, 'filePath'),
                self::optionalString($row, 'date'),
                self::optionalString($row, 'place'),
                self::stringList($row, 'topicIds'),
                self::stringList($row, 'keywordIds'),
            ),
            $this->read('media'),
        );
    }

    public function entries(): array
    {
        return array_map(
            static fn (array $row): Entry => new Entry(
                self::string($row, 'id'),
                self::string($row, 'title'),
                self::optionalString($row, 'date'),
                self::optionalString($row, 'tripId'),
                self::stringList($row, 'topicIds'),
                array_map(self::block(...), self::rows($row, 'blocks')),
            ),
            $this->read('entries'),
        );
    }

    /**
     * Neue Blocktypen werden hier ergänzt.
     *
     * @param array<string, mixed> $row
     */
    private static function block(array $row): Block
    {
        $id = self::string($row, 'id');

        return match (self::string($row, 'type')) {
            'text' => new TextBlock($id, self::string($row, 'content')),
            'image' => new ImageBlock($id, self::string($row, 'mediaId'), self::optionalString($row, 'caption')),
            'video' => new VideoBlock($id, self::string($row, 'mediaId'), self::optionalString($row, 'caption')),
            'gallery' => new GalleryBlock($id, self::stringList($row, 'mediaIds')),
            default => throw new \UnexpectedValueException(\sprintf('Unbekannter Blocktyp "%s" in Block "%s"', self::string($row, 'type'), $id)),
        };
    }

    /** @return list<array<string, mixed>> */
    private function read(string $name): array
    {
        if (!isset($this->cache[$name])) {
            $path = $this->dataDir.'/'.$name.'.json';
            $json = file_get_contents($path);
            if (false === $json) {
                throw new \RuntimeException(\sprintf('Datei "%s" kann nicht gelesen werden.', $path));
            }
            $data = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
            if (!\is_array($data) || !array_is_list($data)) {
                throw new \UnexpectedValueException(\sprintf('"%s" muss eine Liste enthalten.', $path));
            }
            /** @var list<array<string, mixed>> $data */
            $this->cache[$name] = $data;
        }

        return $this->cache[$name];
    }

    /** @param array<string, mixed> $row */
    private static function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!\is_string($value)) {
            throw new \UnexpectedValueException(\sprintf('Feld "%s" fehlt oder ist kein Text.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private static function optionalString(array $row, string $key): ?string
    {
        return isset($row[$key]) ? self::string($row, $key) : null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<string>
     */
    private static function stringList(array $row, string $key): array
    {
        $value = $row[$key] ?? [];
        if (!\is_array($value)) {
            throw new \UnexpectedValueException(\sprintf('Feld "%s" muss eine Liste sein.', $key));
        }

        return array_values(array_map('strval', $value));
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return list<array<string, mixed>>
     */
    private static function rows(array $row, string $key): array
    {
        $value = $row[$key] ?? [];
        if (!\is_array($value)) {
            throw new \UnexpectedValueException(\sprintf('Feld "%s" muss eine Liste sein.', $key));
        }

        /** @var list<array<string, mixed>> */
        return array_values($value);
    }
}
