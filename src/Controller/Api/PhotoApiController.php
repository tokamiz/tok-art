<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Presentation\PhotoGalleryPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PhotoApiController extends AbstractController
{
    /**
     * Fotos und Filter als JSON für die Vue-Galerie. Gleiche Parameter wie /photos.
     */
    #[Route('/api/photos', name: 'api_photos', methods: ['GET'])]
    public function index(Request $request, PhotoGalleryPresenter $presenter): JsonResponse
    {
        return $this->json($presenter->present(PhotoGalleryPresenter::filterFromRequest($request)));
    }
}
