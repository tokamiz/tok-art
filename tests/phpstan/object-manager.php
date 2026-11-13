<?php

/*
 * Für PHPStan (phpstan-doctrine): liefert den EntityManager, damit PHPStan die Doctrine-Entitäten kennt –
 * z. B. dass die Datenbank IDs vergibt. Wird nur von PHPStan geladen, nicht von den Tests.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__, 2).'/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
