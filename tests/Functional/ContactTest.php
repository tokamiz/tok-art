<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Kontaktformular ohne JavaScript (normales POST). Nachrichten landen in der Datenbank.
 */
final class ContactTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em()->createQuery('DELETE FROM '.Message::class)->execute();
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    /** @return list<Message> */
    private function saved(): array
    {
        $this->em()->clear();

        return $this->em()->getRepository(Message::class)->findAll();
    }

    public function testGueltigeNachrichtWirdGespeichertUndBestaetigt(): void
    {
        $this->client->request('GET', '/contact');
        $this->client->submitForm('Senden', [
            'name' => 'Anna',
            'email' => 'anna@example.org',
            'message' => 'Schöne Fotos aus Island!',
        ]);

        self::assertResponseRedirects('/contact?gesendet=1');
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Danke für deine Nachricht!');
        self::assertSelectorTextContains('main', 'Neue Nachricht schreiben');

        $saved = $this->saved();
        self::assertCount(1, $saved);
        self::assertSame('Anna', $saved[0]->getName());
        self::assertSame('Schöne Fotos aus Island!', $saved[0]->getMessage());
    }

    public function testUngueltigeEingabenZeigenFehlerJeFeldUndBehaltenDieEingaben(): void
    {
        $this->client->request('GET', '/contact');
        $this->client->submitForm('Senden', ['name' => 'Anna', 'email' => 'kaputt', 'message' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#email-error', 'gültige E-Mail-Adresse');
        self::assertSelectorTextContains('#message-error', 'Nachricht');
        self::assertSelectorNotExists('#name-error');
        self::assertInputValueSame('name', 'Anna');
        self::assertSame([], $this->saved());
    }

    public function testOhneGueltigesCsrfTokenWirdNichtsGespeichert(): void
    {
        $this->client->request('POST', '/contact', [
            '_token' => 'falsch',
            'name' => 'Anna',
            'email' => 'anna@example.org',
            'message' => 'Hallo',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->saved());
    }

    public function testBotsImUnsichtbarenFeldWerdenStillVerworfen(): void
    {
        $this->client->request('GET', '/contact');
        $this->client->submitForm('Senden', [
            'website' => 'https://spam.example',
            'name' => 'Bot',
            'email' => 'bot@example.org',
            'message' => 'Spam',
        ]);

        self::assertResponseRedirects('/contact?gesendet=1');
        self::assertSame([], $this->saved());
    }
}
