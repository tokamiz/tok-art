-- Läuft einmal beim ersten Start des Datenbank-Containers.
-- Der Benutzer tokart darf zusätzlich die Testdatenbank anlegen und benutzen (PHPUnit).
CREATE DATABASE IF NOT EXISTS tokart_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON tokart_test.* TO 'tokart'@'%';
FLUSH PRIVILEGES;
