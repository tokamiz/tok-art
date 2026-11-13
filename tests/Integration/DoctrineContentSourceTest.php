<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Content\DoctrineContentSource;
use App\Content\JsonContentSource;
use App\Domain\ContentSource;
use App\Domain\Model\Block\Block;
use App\Domain\Model\Block\ImageBlock;
use App\Domain\Model\Block\TextBlock;
use App\Domain\Model\Block\VideoBlock;
use App\Domain\Model\Entry;
use App\Domain\Model\Keyword;
use App\Domain\Model\MediaItem;
use App\Domain\Model\Topic;
use App\Domain\Model\Trip;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Nach dem Import (tests/bootstrap.php) liefert die Datenbank dieselben Inhalte wie die JSON-Dateien.
 * Damit ist gesichert, dass Import und DoctrineContentSource nichts verlieren oder verändern.
 * Verglichen werden die Inhalte, nicht die Reihenfolge der Listen.
 */
final class DoctrineContentSourceTest extends KernelTestCase
{
    private DoctrineContentSource $db;
    private JsonContentSource $json;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->db = self::getContainer()->get(DoctrineContentSource::class);
        $this->json = self::getContainer()->get(JsonContentSource::class);
    }

    /**
     * Fasst die Inhalte einer Quelle als sortierbare Arrays zusammen.
     *
     * @return array<string, list<array<mixed>>>
     */
    private static function summary(ContentSource $source): array
    {
        $sorted = static function (array $rows): array {
            usort($rows, static fn (array $a, array $b): int => strcmp((string) $a[0], (string) $b[0]));

            return $rows;
        };
        $set = static function (array $ids): array {
            sort($ids);

            return $ids;
        };

        return [
            'trips' => $sorted(array_map(static fn (Trip $t): array => [$t->id, $t->title, $t->startDate, $t->endDate], $source->trips())),
            'topics' => $sorted(array_map(static fn (Topic $t): array => [$t->id, $t->name], $source->topics())),
            'keywords' => $sorted(array_map(static fn (Keyword $k): array => [$k->id, $k->name], $source->keywords())),
            'media' => $sorted(array_map(static fn (MediaItem $m): array => [
                $m->id, $m->kind->value, $m->filePath, $m->date, $m->place, $set($m->topicIds), $set($m->keywordIds),
            ], $source->mediaItems())),
            // Block-IDs unterscheiden sich (JSON "b1", Datenbank "b17"); Reihenfolge und Inhalt müssen gleich sein
            'entries' => $sorted(array_map(static fn (Entry $e): array => [
                $e->id, $e->title, $e->date, $e->tripId, $set($e->topicIds),
                array_map(static fn (Block $b): array => [
                    $b->type(),
                    $b->mediaIds(),
                    $b instanceof TextBlock ? $b->content : null,
                    $b instanceof ImageBlock || $b instanceof VideoBlock ? $b->caption : null,
                ], $e->blocks),
            ], $source->entries())),
        ];
    }

    public function testDatenbankLiefertDieselbenInhalteWieDieJsonDateien(): void
    {
        $json = self::summary($this->json);
        $db = self::summary($this->db);

        foreach (array_keys($json) as $part) {
            self::assertSame($json[$part], $db[$part], $part);
        }
    }

    public function testEintraegeBehaltenDieReihenfolgeIhrerBloecke(): void
    {
        $entry = array_values(array_filter($this->db->entries(), static fn (Entry $e): bool => 'helsinki-am-hafen' === $e->id))[0];

        self::assertSame(['text', 'image', 'text'], array_map(static fn (Block $b): string => $b->type(), $entry->blocks));
    }
}
