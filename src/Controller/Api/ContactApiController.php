<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Contact\ContactHandler;
use App\Controller\ContactController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactApiController extends AbstractController
{
    /**
     * Nimmt eine Nachricht als JSON an (vom Vue-Formular).
     * 201: gespeichert · 422: Fehler je Feld oder ungültiges CSRF-Token.
     */
    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function create(Request $request, ContactHandler $handler): JsonResponse
    {
        $input = $request->toArray();

        $token = $input['_token'] ?? '';
        if (!\is_string($token) || !$this->isCsrfTokenValid(ContactController::CSRF_ID, $token)) {
            return $this->json(
                ['message' => 'Die Nachricht konnte nicht gesendet werden. Bitte lade die Seite neu und versuch es noch einmal.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $result = $handler->handle($input);
        if (!$result->isAccepted()) {
            return $this->json(['errors' => $result->errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true], Response::HTTP_CREATED);
    }
}
