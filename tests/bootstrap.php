<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

/*
 * Testdatenbank (tokart_test) vor jedem Lauf frisch aufbauen:
 * Schema über die Migrationen (prüft sie gleich mit), Beispielinhalte aus data/, ein Testkonto.
 */
$console = static function (string $command): void {
    $line = sprintf('php %s %s --env=test --no-interaction --quiet', escapeshellarg(dirname(__DIR__).'/bin/console'), $command);
    passthru($line, $exitCode);
    if (0 !== $exitCode) {
        fwrite(STDERR, "Vorbereitung der Testdatenbank fehlgeschlagen: {$command}\n");
        exit(1);
    }
};

$console('doctrine:database:create --if-not-exists');
$console('doctrine:schema:drop --force --full-database');
$console('doctrine:migrations:migrate');
$console('app:import-json');
$console(sprintf('app:admin:create %s --password=%s', App\Tests\TestAdmin::EMAIL, App\Tests\TestAdmin::PASSWORD));
