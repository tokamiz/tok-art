<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Die JSON-Routen für die Vue-Komponenten.
 */
final class ApiTest extends WebTestCase
{
    /** @return array<string, mixed> */
    private static function json(KernelBrowser $client): array
    {
        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $gallery
     *
     * @return array<string, mixed>
     */
    private static function field(array $gallery, string $name): array
    {
        self::assertIsArray($gallery['filters']);
        foreach ($gallery['filters'] as $field) {
            self::assertIsArray($field);
            if ($field['name'] === $name) {
                /** @var array<string, mixed> $field */
                return $field;
            }
        }
        self::fail(\sprintf('Filter "%s" fehlt.', $name));
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return list<string>
     */
    private static function available(array $field): array
    {
        self::assertIsArray($field['options']);

        return array_values(array_map(
            static fn (array $o): string => (string) $o['value'],
            array_filter($field['options'], static fn (array $o): bool => true === $o['available']),
        ));
    }

    public function testFotosLiefertBilderUndFacettenAlsJson(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/photos?reise=italien');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        $data = self::json($client);

        self::assertTrue($data['active']);
        self::assertIsArray($data['photos']);
        self::assertCount(3, $data['photos']);
        self::assertSame(['reise', 'thema', 'jahr', 'schlagwort'], array_column($data['filters'], 'name'));
        self::assertSame('italien', self::field($data, 'reise')['value']);
        $keywords = self::available(self::field($data, 'schlagwort'));
        sort($keywords);
        self::assertSame(['kaffee', 'museum'], $keywords);
        self::assertSame(['2018'], self::available(self::field($data, 'jahr')));
    }

    public function testFotoEnthaeltAdressenFuerEintragReiseUndThemen(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/photos?schlagwort=theater&jahr=2021');

        $photos = self::json($client)['photos'];
        self::assertIsArray($photos);
        self::assertSame([[
            'id' => 'athen',
            'src' => '/media/athen.svg',
            'alt' => 'Athen, Griechenland',
            'place' => 'Athen, Griechenland',
            'entryUrl' => '/entries/athen',
            'trip' => ['title' => 'Griechenland', 'url' => '/trips/griechenland'],
            'topics' => [
                ['name' => 'Kultur', 'url' => '/topics/kultur'],
                ['name' => 'Städte', 'url' => '/topics/staedte'],
            ],
        ]], $photos);
    }

    public function testFotosSeiteGibtVueDieselbenStartdatenMit(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/photos?reise=italien');

        $props = json_decode((string) $crawler->filter('[data-vue-component="PhotoGallery"]')->attr('data-props'), true);
        self::assertIsArray($props);
        self::assertSame('/api/photos', $props['endpoint']);

        $client->request('GET', '/api/photos?reise=italien');
        self::assertSame(self::json($client), $props['initial']);
    }

    /** Leert die Nachrichten und liefert eine Funktion, die sie zählt. */
    private static function messages(): \Closure
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Message::class)->execute();

        return static fn (): int => $em->getRepository(Message::class)->count([]);
    }

    private function csrfToken(KernelBrowser $client): string
    {
        $crawler = $client->request('GET', '/contact');
        $props = json_decode((string) $crawler->filter('[data-vue-component="ContactForm"]')->attr('data-props'), true);
        self::assertIsArray($props);
        self::assertIsString($props['csrfToken']);

        return $props['csrfToken'];
    }

    /** @param array<string, string> $body */
    private static function postJson(KernelBrowser $client, array $body): void
    {
        $client->request('POST', '/api/contact', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($body, \JSON_THROW_ON_ERROR));
    }

    public function testKontaktSpeichertGueltigeNachricht(): void
    {
        $client = self::createClient();
        $count = self::messages();
        $token = $this->csrfToken($client);

        self::postJson($client, ['_token' => $token, 'name' => 'Anna', 'email' => 'anna@example.org', 'message' => 'Hallo!', 'website' => '']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(['ok' => true], self::json($client));
        self::assertSame(1, $count());
    }

    public function testKontaktLiefertFehlerJeFeld(): void
    {
        $client = self::createClient();
        $token = $this->csrfToken($client);

        self::postJson($client, ['_token' => $token, 'name' => '', 'email' => 'kaputt', 'message' => 'Hallo']);

        self::assertResponseStatusCodeSame(422);
        $errors = self::json($client)['errors'];
        self::assertIsArray($errors);
        self::assertSame(['name', 'email'], array_keys($errors));
    }

    public function testKontaktOhneGueltigesTokenWirdAbgelehnt(): void
    {
        $client = self::createClient();
        $count = self::messages();

        self::postJson($client, ['_token' => 'falsch', 'name' => 'Anna', 'email' => 'anna@example.org', 'message' => 'Hallo']);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('message', self::json($client));
        self::assertSame(0, $count());
    }

    public function testKontaktMitUngueltigemJsonLiefert400(): void
    {
        $client = self::createClient();
        $client->request('POST', '/api/contact', server: ['CONTENT_TYPE' => 'application/json'], content: '{kaputt');

        self::assertResponseStatusCodeSame(400);
    }
}
