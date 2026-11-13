<?php

declare(strict_types=1);

namespace App\Tests;

/**
 * Testkonto für die Verwaltung; tests/bootstrap.php legt es in der Testdatenbank an.
 */
final class TestAdmin
{
    public const EMAIL = 'admin@test.local';
    public const PASSWORD = 'test-passwort-123';
}
