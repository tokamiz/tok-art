<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contact\ContactHandler;
use App\Domain\Contact\ContactMessage;
use App\Domain\Contact\ContactValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Kontaktformular als HTML-Seite. Mit JavaScript übernimmt die Vue-Komponente und sendet an /api/contact;
 * ohne JavaScript funktioniert das Formular hier mit normalem POST.
 */
final class ContactController extends AbstractController
{
    public const CSRF_ID = 'contact';

    public function __construct(
        private readonly ContactHandler $handler,
    ) {
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->query->getBoolean('gesendet')) {
            return $this->render('contact/sent.html.twig');
        }

        $message = new ContactMessage('', '', '');
        $errors = [];
        $failed = false;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF_ID, $request->request->getString('_token'))) {
                $message = ContactMessage::fromInput($request->request->all());
                $failed = true;
            } else {
                $result = $this->handler->handle($request->request->all());
                if ($result->isAccepted()) {
                    // Post/Redirect/Get: Neuladen der Seite schickt die Nachricht nicht ein zweites Mal.
                    return $this->redirectToRoute('contact', ['gesendet' => 1]);
                }
                $message = $result->message;
                $errors = $result->errors;
            }
        }

        $status = [] !== $errors || $failed ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('contact/index.html.twig', [
            'form' => $message,
            'errors' => $errors,
            'failed' => $failed,
            'csrfId' => self::CSRF_ID,
            'honeypot' => ContactHandler::HONEYPOT,
            'maxName' => ContactValidator::MAX_NAME_LENGTH,
            'maxMessage' => ContactValidator::MAX_MESSAGE_LENGTH,
        ], new Response(status: $status));
    }
}
