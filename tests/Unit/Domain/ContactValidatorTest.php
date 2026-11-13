<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Contact\ContactMessage;
use App\Domain\Contact\ContactValidator;
use PHPUnit\Framework\TestCase;

final class ContactValidatorTest extends TestCase
{
    private static function valid(string $name = 'Anna', string $email = 'anna@example.org', string $message = 'Schöne Fotos aus Island!'): ContactMessage
    {
        return new ContactMessage($name, $email, $message);
    }

    public function testAkzeptiertEineVollstaendigeNachricht(): void
    {
        self::assertSame([], ContactValidator::validate(self::valid()));
    }

    public function testVerlangtNameEmailUndNachricht(): void
    {
        $errors = ContactValidator::validate(new ContactMessage('', '', ''));

        self::assertSame(['name', 'email', 'message'], array_keys($errors));
    }

    public function testZaehltLeerzeichenNichtAlsEingabe(): void
    {
        self::assertArrayHasKey('name', ContactValidator::validate(self::valid(name: '   ')));
    }

    public function testErkenntEineUngueltigeEmailAdresse(): void
    {
        self::assertArrayHasKey('email', ContactValidator::validate(self::valid(email: 'anna.example.org')));
        self::assertArrayHasKey('email', ContactValidator::validate(self::valid(email: 'anna @example.org')));
    }

    public function testBegrenztDieLaengeDesNamens(): void
    {
        self::assertSame([], ContactValidator::validate(self::valid(name: str_repeat('x', ContactValidator::MAX_NAME_LENGTH))));
        self::assertArrayHasKey('name', ContactValidator::validate(self::valid(name: str_repeat('x', ContactValidator::MAX_NAME_LENGTH + 1))));
    }

    public function testBegrenztDieLaengeDerNachricht(): void
    {
        self::assertSame([], ContactValidator::validate(self::valid(message: str_repeat('x', ContactValidator::MAX_MESSAGE_LENGTH))));
        self::assertArrayHasKey('message', ContactValidator::validate(self::valid(message: str_repeat('x', ContactValidator::MAX_MESSAGE_LENGTH + 1))));
    }

    public function testZaehltUmlauteAlsEinZeichen(): void
    {
        self::assertSame([], ContactValidator::validate(self::valid(name: str_repeat('ä', ContactValidator::MAX_NAME_LENGTH))));
    }

    public function testFromInputMachtAusJederEingabeTextOhneRandLeerzeichen(): void
    {
        $message = ContactMessage::fromInput(['name' => '  Anna ', 'email' => ['kaputt'], 'message' => 42]);

        self::assertSame('Anna', $message->name);
        self::assertSame('', $message->email);
        self::assertSame('42', $message->message);
    }
}
