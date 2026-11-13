<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Ruft jede Seite wie ein Browser auf und prüft Status, Titel und wichtige Inhalte.
 */
final class PagesTest extends WebTestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function pages(): iterable
    {
        yield 'Über mich' => ['/', 'Foto-Tagebuch'];
        yield 'Reisen' => ['/trips', 'Reisen · Foto-Tagebuch'];
        yield 'Reise' => ['/trips/island', 'Reise: Island · Foto-Tagebuch'];
        yield 'Themen' => ['/topics', 'Themen · Foto-Tagebuch'];
        yield 'Thema' => ['/topics/staedte', 'Thema: Städte · Foto-Tagebuch'];
        yield 'Eintrag' => ['/entries/am-wasserfall', 'Am Wasserfall · Foto-Tagebuch'];
        yield 'Fotos' => ['/photos', 'Fotos · Foto-Tagebuch'];
        yield 'Kontakt' => ['/contact', 'Kontakt · Foto-Tagebuch'];
    }

    #[DataProvider('pages')]
    public function testSeiteLaedtMitEigenemTitel(string $url, string $title): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame($title);
    }

    /** @return iterable<string, array{string}> */
    public static function missing(): iterable
    {
        yield 'Reise' => ['/trips/gibt-es-nicht'];
        yield 'Thema' => ['/topics/gibt-es-nicht'];
        yield 'Eintrag' => ['/entries/gibt-es-nicht'];
    }

    #[DataProvider('missing')]
    public function testUnbekannteIdLiefert404(string $url): void
    {
        $client = self::createClient();
        $client->request('GET', $url);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAktiverTabAuchAufUnterseiten(): void
    {
        $client = self::createClient();
        $client->request('GET', '/trips/italien');

        self::assertSelectorTextSame('.site-nav__link.is-active', 'Reisen');
        self::assertSelectorCount(1, '.site-nav__link.is-active');
    }

    public function testThemenSeiteZeigtGruppeOhneThema(): void
    {
        $client = self::createClient();
        $client->request('GET', '/topics');

        self::assertSelectorTextContains('main', 'Ohne Thema');
        self::assertSelectorTextContains('main', 'Eine lose Notiz');
    }

    public function testZeitleisteGruppiertNachJahrMitOhneDatumAmEnde(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/topics/staedte');

        $years = $crawler->filter('.timeline__group .section-title')->each(static fn ($node): string => $node->text());
        self::assertSame(['2021', '2019', '2016', 'Ohne Datum'], $years);
    }

    public function testEintragZeigtAlleBlocktypen(): void
    {
        $client = self::createClient();

        $client->request('GET', '/entries/helsinki-am-hafen');
        self::assertSelectorExists('.block-text');
        self::assertSelectorTextContains('figcaption', 'Morgens am Marktplatz');

        $client->request('GET', '/entries/italien-rundreise');
        self::assertSelectorExists('.gallery img');

        $client->request('GET', '/entries/am-wasserfall');
        self::assertSelectorExists('video[controls]');
    }

    public function testFotosFilterUndFacetten(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/photos?reise=italien');

        self::assertCount(3, $crawler->filter('.photo-grid .tile'));
        self::assertSelectorTextContains('main', 'Filter zurücksetzen');
        // Italien hat kein Theater-Foto → Option ausgegraut
        self::assertSelectorExists('select[name="schlagwort"] option[value="theater"][disabled]');
        self::assertSelectorNotExists('select[name="schlagwort"] option[value="kaffee"][disabled]');
    }

    public function testFotoKachelVerlinktEintragUndZeigtInfos(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/photos?schlagwort=theater&jahr=2021');

        $tile = $crawler->filter('.photo-grid .tile');
        self::assertCount(1, $tile);
        self::assertSame('/entries/athen', $tile->filter('a')->first()->attr('href'));
        self::assertStringContainsString('Griechenland', $tile->filter('.tile-info')->text());
    }

    public function testFotosOhneTrefferZeigtHinweis(): void
    {
        $client = self::createClient();
        $client->request('GET', '/photos?reise=island');

        self::assertSelectorTextContains('main', 'Keine Fotos gefunden.');
    }
}
