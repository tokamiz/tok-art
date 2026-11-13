<?php

declare(strict_types=1);

namespace App\Tests\Unit\Content;

use App\Content\JsonContentSource;
use App\Domain\Lookup;
use PHPUnit\Framework\TestCase;

/**
 * Datenintegrität der Beispieldaten in data/: Jeder Verweis zeigt auf etwas Vorhandenes.
 */
final class JsonContentSourceTest extends TestCase
{
    private JsonContentSource $source;

    protected function setUp(): void
    {
        $this->source = new JsonContentSource(\dirname(__DIR__, 3).'/data');
    }

    public function testJedesMediumVerweistAufEineVorhandeneDatei(): void
    {
        $publicDir = \dirname(__DIR__, 3).'/public';

        foreach ($this->source->mediaItems() as $item) {
            self::assertFileExists($publicDir.$item->filePath, $item->id);
        }
    }

    public function testJederBlockVerweistAufVorhandeneMedien(): void
    {
        $media = Lookup::byId($this->source->mediaItems());

        foreach ($this->source->entries() as $entry) {
            foreach ($entry->blocks as $block) {
                foreach ($block->mediaIds() as $mediaId) {
                    self::assertArrayHasKey($mediaId, $media, \sprintf('%s/%s', $entry->id, $block->id()));
                }
            }
        }
    }

    public function testJederEintragMitReiseVerweistAufEineVorhandeneReise(): void
    {
        $trips = Lookup::byId($this->source->trips());

        foreach ($this->source->entries() as $entry) {
            if (null !== $entry->tripId) {
                self::assertArrayHasKey($entry->tripId, $trips, $entry->id);
            }
        }
    }

    public function testJedesThemaIstVorhanden(): void
    {
        $topics = Lookup::byId($this->source->topics());

        foreach ($this->source->entries() as $entry) {
            foreach ($entry->topicIds as $topicId) {
                self::assertArrayHasKey($topicId, $topics, $entry->id);
            }
        }
        foreach ($this->source->mediaItems() as $item) {
            foreach ($item->topicIds as $topicId) {
                self::assertArrayHasKey($topicId, $topics, $item->id);
            }
        }
    }

    public function testJedesSchlagwortAnEinemMediumIstVorhanden(): void
    {
        $keywords = Lookup::byId($this->source->keywords());

        foreach ($this->source->mediaItems() as $item) {
            foreach ($item->keywordIds as $keywordId) {
                self::assertArrayHasKey($keywordId, $keywords, $item->id);
            }
        }
    }

    public function testIdsSindEindeutig(): void
    {
        foreach (['trips', 'topics', 'entries', 'mediaItems', 'keywords'] as $method) {
            $ids = array_map(static fn (object $item): string => $item->id, $this->source->{$method}());
            self::assertSame(array_unique($ids), $ids, $method);
        }
    }
}
