<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ContentSource;
use App\Domain\Lookup;
use App\Domain\Photo\PhotoFilter;
use App\Domain\Photo\PhotoGallery;
use App\Presentation\PhotoGalleryPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PhotoController extends AbstractController
{
    public function __construct(
        private readonly ContentSource $content,
        private readonly PhotoGalleryPresenter $presenter,
    ) {
    }

    /**
     * Alle Fotos mit vier kombinierbaren Filtern. Die Auswahl steht in der Adresse
     * (/photos?reise=italien&schlagwort=kaffee).
     *
     * Twig rendert die Seite vollständig (funktioniert ohne JavaScript). Die Vue-Komponente
     * PhotoGallery bekommt dieselben Daten als Startwerte und übernimmt danach.
     */
    #[Route('/photos', name: 'photo_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = PhotoGalleryPresenter::filterFromRequest($request);
        $gallery = PhotoGallery::build($this->content->mediaItems(), $this->content->entries());

        return $this->render('photo/index.html.twig', [
            'wide' => true,
            'vueProps' => [
                'initial' => $this->presenter->present($filter),
                'endpoint' => $this->generateUrl('api_photos'),
            ],
            'filter' => $filter,
            'photos' => $gallery->visible($filter),
            'years' => $gallery->years(),
            'trips' => $this->content->trips(),
            'topics' => $this->content->topics(),
            'keywords' => $this->content->keywords(),
            'tripMap' => Lookup::byId($this->content->trips()),
            'topicMap' => Lookup::byId($this->content->topics()),
            'availableTrips' => $gallery->availableTrips($filter),
            'availableTopics' => $gallery->availableTopics($filter),
            'availableYears' => $gallery->availableYears($filter),
            'availableKeywords' => $gallery->availableKeywords($filter),
            'noDate' => PhotoFilter::NO_DATE,
        ]);
    }
}
