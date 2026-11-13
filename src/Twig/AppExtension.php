<?php

declare(strict_types=1);

namespace App\Twig;

use App\Domain\Dates;
use App\Domain\Timeline;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Domain-Funktionen für die Vorlagen.
 */
final class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // {{ entry.date|date_de }} → "Juli 2019"
            new TwigFilter('date_de', Dates::format(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            // {{ is_active_tab('/trips', app.request.pathInfo) }} – auch auf Unterseiten wie /trips/island
            new TwigFunction('is_active_tab', self::isActiveTab(...)),
            // {% for group in group_by_year(entries) %} – Zeitleiste
            new TwigFunction('group_by_year', Timeline::groupByYear(...)),
        ];
    }

    public static function isActiveTab(string $tab, string $path): bool
    {
        if ('/' === $tab) {
            return '/' === $path;
        }

        return $path === $tab || str_starts_with($path, $tab.'/');
    }
}
