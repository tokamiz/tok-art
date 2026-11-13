<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ContentSource;
use App\Domain\Lookup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EntryController extends AbstractController
{
    public function __construct(
        private readonly ContentSource $content,
    ) {
    }

    /**
     * Tagebuch-Ansicht: der Eintrag mit allen Blöcken. Die Seite schlägt die Medien nach,
     * die Block-Vorlagen zeigen nur an.
     */
    #[Route('/entries/{id}', name: 'entry_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $entry = Lookup::byId($this->content->entries())[$id] ?? throw $this->createNotFoundException('Eintrag nicht gefunden');

        return $this->render('entry/show.html.twig', [
            'entry' => $entry,
            'media' => Lookup::byId($this->content->mediaItems()),
        ]);
    }
}
