<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\AdminUser;
use App\Entity\Block;
use App\Entity\Entry;
use App\Entity\Media;
use App\Entity\Trip;
use App\Tests\TestAdmin;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Verwaltung unter /admin: Anmeldung, alle Seiten, Anlegen, Löschschutz.
 */
final class AdminTest extends WebTestCase
{
    private function loggedIn(): KernelBrowser
    {
        $client = self::createClient();
        $user = $this->em()->getRepository(AdminUser::class)->findOneBy(['email' => TestAdmin::EMAIL]);
        self::assertNotNull($user);
        $client->loginUser($user);

        return $client;
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testOhneAnmeldungGehtEsZumLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/admin/entry');

        self::assertResponseRedirects('/login');
    }

    public function testAnmeldenMitPasswort(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Anmelden', ['_username' => TestAdmin::EMAIL, '_password' => TestAdmin::PASSWORD]);

        self::assertResponseRedirects('/admin');
        $client->followRedirect();
        self::assertResponseRedirects('/admin/entry');
    }

    public function testFalschesPasswortWirdAbgelehnt(): void
    {
        $client = self::createClient();
        $client->request('GET', '/login');
        $client->submitForm('Anmelden', ['_username' => TestAdmin::EMAIL, '_password' => 'falsch']);
        $client->followRedirect();

        self::assertSelectorExists('.alert-danger, .alert');
        $client->request('GET', '/admin/entry');
        self::assertResponseRedirects('/login');
    }

    public function testOeffentlicheSeitenSetzenKeinCookie(): void
    {
        $client = self::createClient();
        $client->request('GET', '/photos');

        self::assertResponseIsSuccessful();
        self::assertResponseNotHasCookie('PHPSESSID');
        self::assertResponseNotHasCookie('MOCKSESSID');
    }

    /** @return iterable<string, array{string}> */
    public static function adminPages(): iterable
    {
        foreach (['entry', 'trip', 'topic', 'media', 'keyword', 'message'] as $crud) {
            yield $crud.' Liste' => ["/admin/{$crud}"];
            if ('message' !== $crud) {
                yield $crud.' neu' => ["/admin/{$crud}/new"];
            }
        }
        yield 'Eintrag bearbeiten' => ['/admin/entry/italien-rundreise/edit'];
        yield 'Foto bearbeiten' => ['/admin/media/athen/edit'];
    }

    #[DataProvider('adminPages')]
    public function testVerwaltungsseiteLaedt(string $url): void
    {
        $this->loggedIn()->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public function testNachrichtenLassenSichNichtAnlegen(): void
    {
        $this->loggedIn()->request('GET', '/admin/message/new');

        self::assertResponseStatusCodeSame(403);
    }

    public function testNeueReiseErscheintAufDerWebsite(): void
    {
        $client = $this->loggedIn();
        $client->request('GET', '/admin/trip/new');
        $client->submitForm('Erstellen', [
            'Trip[title]' => 'Norwegen',
            'Trip[id]' => 'norwegen-2025',
            'Trip[startDate]' => '2025-06',
        ]);
        self::assertResponseRedirects();

        $client->request('GET', '/trips');
        self::assertSelectorTextContains('main', 'Norwegen');
        self::assertSelectorTextContains('main', 'Juni 2025');

        $this->em()->remove($this->em()->find(Trip::class, 'norwegen-2025') ?? throw new \LogicException());
        $this->em()->flush();
    }

    public function testUngueltigeKennungUndDatumWerdenAbgelehnt(): void
    {
        $client = $this->loggedIn();
        $client->request('GET', '/admin/trip/new');
        $client->submitForm('Erstellen', [
            'Trip[title]' => 'Test',
            'Trip[id]' => 'Mit Leerzeichen',
            'Trip[startDate]' => '14.06.2025',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Nur Kleinbuchstaben');
        self::assertSelectorTextContains('body', 'z. B. 2016, 2018-05 oder 2019-07-12');
    }

    public function testBlockOhnePassendenInhaltIstUngueltig(): void
    {
        $validator = self::getContainer()->get(ValidatorInterface::class);
        $block = (new Block())->setType('image');

        $violations = $validator->validate($block);

        self::assertCount(1, $violations);
        self::assertSame('media', $violations->get(0)->getPropertyPath());
    }

    public function testBenutztesFotoLaesstSichNichtLoeschen(): void
    {
        $client = $this->loggedIn();
        $crawler = $client->request('GET', '/admin/media');
        $token = $crawler->filter('input[type="hidden"][name="token"]')->attr('value');

        $client->request('POST', '/admin/media/athen/delete', ['token' => $token]);
        $client->followRedirect();

        self::assertSelectorTextContains('body', 'wird noch verwendet in: Athen und die Akropolis');
        $this->em()->clear();
        self::assertNotNull($this->em()->find(Media::class, 'athen'));
    }

    public function testLoeschenEntferntEigeneDateienAberNieBeispieldateien(): void
    {
        $client = $this->loggedIn();
        $mediaDir = \dirname(__DIR__, 2).'/public/media';
        $ownFile = 'test-eigenes-foto.svg';
        copy($mediaDir.'/rom.svg', $mediaDir.'/'.$ownFile);

        $em = $this->em();
        $em->persist((new Media())->setId('test-eigen')->setFile($ownFile));
        $em->persist((new Media())->setId('test-beispiel')->setFile('rom.svg'));
        $em->flush();

        $token = $client->request('GET', '/admin/media')->filter('input[type="hidden"][name="token"]')->attr('value');
        $client->request('POST', '/admin/media/test-eigen/delete', ['token' => $token]);
        $client->request('POST', '/admin/media/test-beispiel/delete', ['token' => $token]);

        self::assertFileDoesNotExist($mediaDir.'/'.$ownFile, 'eigene Datei wird mitgelöscht');
        self::assertFileExists($mediaDir.'/rom.svg', 'Beispieldatei bleibt für die Tests erhalten');
        $this->em()->clear();
        self::assertNull($this->em()->find(Media::class, 'test-beispiel'));
    }

    public function testLoeschenEinesEintragsEntferntSeineBloecke(): void
    {
        $em = $this->em();
        $entry = (new Entry())->setId('wegwerf')->setTitle('Wegwerf');
        $entry->addBlock((new Block())->setType('text')->setContent('Hallo'));
        $em->persist($entry);
        $em->flush();
        $blocksBefore = $em->getRepository(Block::class)->count([]);

        $em->remove($entry);
        $em->flush();

        self::assertSame($blocksBefore - 1, $em->getRepository(Block::class)->count([]));
    }
}
