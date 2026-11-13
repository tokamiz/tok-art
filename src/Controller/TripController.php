<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ContentSource;
use App\Domain\Dates;
use App\Domain\Filters;
use App\Domain\Lookup;
use App\Domain\Model\Trip;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trips')]
final class TripController extends AbstractController
{
    public function __construct(
        private readonly ContentSource $content,
    ) {
    }

    #[Route('', name: 'trip_index', methods: ['GET'])]
    public function index(): Response
    {
        $trips = $this->content->trips();
        usort($trips, static fn (Trip $a, Trip $b): int => Dates::compare($a->startDate, $b->startDate));

        return $this->render('trip/index.html.twig', ['trips' => $trips]);
    }

    #[Route('/{id}', name: 'trip_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $trip = Lookup::byId($this->content->trips())[$id] ?? throw $this->createNotFoundException('Reise nicht gefunden');

        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
            'entries' => Filters::apply($this->content->entries(), Filters::byTrip($id)),
        ]);
    }
}
