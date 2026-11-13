<?php

declare(strict_types=1);

namespace App\Media;

/**
 * Bereitet hochgeladene Fotos für die Website vor:
 *
 * 1. dreht sie richtig herum (Handys speichern die Drehung nur als Angabe in den Metadaten),
 * 2. verkleinert sie auf höchstens MAX_EDGE Pixel an der langen Seite,
 * 3. speichert sie neu – dabei fallen ALLE Metadaten weg: GPS-Koordinaten, Kameramodell, Aufnahmezeit.
 *
 * Die Metadaten werden nur gelesen, um die Drehung zu kennen, und danach verworfen – nie gespeichert
 * oder angezeigt. Videos lassen sich in PHP nicht bereinigen; sie müssen vorher ohne Ortsangaben
 * exportiert werden (siehe VERWALTUNG.md).
 */
final class ImageSanitizer
{
    public const MAX_EDGE = 2000;
    private const QUALITY = 85;

    /** Dateiendungen, die bereinigt werden. */
    public const HANDLED = ['jpg', 'jpeg', 'png', 'webp'];

    public function sanitize(string $path): void
    {
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));
        if (!\in_array($extension, self::HANDLED, true)) {
            return;
        }

        $image = match ($extension) {
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };
        if (false === $image) {
            throw new \RuntimeException(\sprintf('Die Bilddatei "%s" konnte nicht gelesen werden.', basename($path)));
        }

        $image = $this->orient($image, $path, $extension);
        $image = $this->shrink($image);

        $saved = match ($extension) {
            'png' => imagepng($image, $path, 6),
            'webp' => imagewebp($image, $path, self::QUALITY),
            default => imagejpeg($image, $path, self::QUALITY),
        };
        imagedestroy($image);

        if (!$saved) {
            throw new \RuntimeException(\sprintf('Die Bilddatei "%s" konnte nicht gespeichert werden.', basename($path)));
        }
    }

    /** Dreht das Bild entsprechend der Orientierungsangabe aus den EXIF-Daten (nur JPEG). */
    private function orient(\GdImage $image, string $path, string $extension): \GdImage
    {
        if (!\in_array($extension, ['jpg', 'jpeg'], true) || !\function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path);
        $orientation = \is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return false === $rotated ? $image : $rotated;
    }

    private function shrink(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longEdge = max($width, $height);
        if ($longEdge <= self::MAX_EDGE) {
            return $image;
        }

        $factor = self::MAX_EDGE / $longEdge;
        $resized = imagescale($image, max(1, (int) round($width * $factor)), max(1, (int) round($height * $factor)), \IMG_BICUBIC);

        return false === $resized ? $image : $resized;
    }
}
