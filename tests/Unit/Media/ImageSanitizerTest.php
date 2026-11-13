<?php

declare(strict_types=1);

namespace App\Tests\Unit\Media;

use App\Media\ImageSanitizer;
use PHPUnit\Framework\TestCase;

final class ImageSanitizerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/image-sanitizer-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);
    }

    /**
     * Ein JPEG mit eingebettetem EXIF-Block (APP1) inkl. Kameramodell und GPS-Kennung.
     *
     * @param positive-int $width
     * @param positive-int $height
     */
    private function jpegWithMetadata(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();

        // TIFF-Kopf (Little Endian) mit einem Eintrag: Make (0x010F) = "SecretCam"
        $tiff = "II*\x00\x08\x00\x00\x00"
            ."\x01\x00"
            ."\x0F\x01\x02\x00\x0A\x00\x00\x00\x1A\x00\x00\x00"
            ."\x00\x00\x00\x00"
            ."SecretCam\x00"
            .'GPS 52.52N 13.40E';
        $app1 = "Exif\x00\x00".$tiff;
        $segment = "\xFF\xE1".pack('n', \strlen($app1) + 2).$app1;

        $path = $this->dir.'/foto.jpg';
        file_put_contents($path, substr($jpeg, 0, 2).$segment.substr($jpeg, 2));

        return $path;
    }

    public function testEntferntMetadaten(): void
    {
        $path = $this->jpegWithMetadata(40, 30);
        self::assertStringContainsString('SecretCam', (string) file_get_contents($path));

        (new ImageSanitizer())->sanitize($path);

        $content = (string) file_get_contents($path);
        self::assertStringNotContainsString('SecretCam', $content);
        self::assertStringNotContainsString('GPS', $content);
        self::assertStringNotContainsString('Exif', $content);
        self::assertNotFalse(imagecreatefromjpeg($path), 'Bild bleibt lesbar');
    }

    public function testVerkleinertGrosseBilderAufDieLangeSeite(): void
    {
        $path = $this->jpegWithMetadata(3000, 1500);

        (new ImageSanitizer())->sanitize($path);

        $size = getimagesize($path);
        self::assertIsArray($size);
        self::assertSame([ImageSanitizer::MAX_EDGE, 1000], [$size[0], $size[1]]);
    }

    public function testLaesstKleineBilderInIhrerGroesse(): void
    {
        $path = $this->jpegWithMetadata(800, 600);

        (new ImageSanitizer())->sanitize($path);

        $size = getimagesize($path);
        self::assertIsArray($size);
        self::assertSame([800, 600], [$size[0], $size[1]]);
    }

    public function testAndereDateienBleibenUnveraendert(): void
    {
        $path = $this->dir.'/film.mp4';
        file_put_contents($path, 'kein Bild');

        (new ImageSanitizer())->sanitize($path);

        self::assertSame('kein Bild', file_get_contents($path));
    }
}
