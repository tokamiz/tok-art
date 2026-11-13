<?php

/*
 * Router für den eingebauten PHP-Server (nur Entwicklung, siehe Dockerfile).
 *
 * Vorhandene Dateien in public/ (Medien, gebaute Assets) liefert der Server direkt aus.
 * Alles andere geht an Symfony – auch Adressen mit Punkt im Namen, die der eingebaute Server
 * sonst als fehlende Datei mit 404 beantworten würde.
 */
$publicDir = dirname(__DIR__).'/public';
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH);

if ('/' !== $path && is_file($publicDir.$path)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $publicDir.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicDir.'/index.php';
