<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Anmeldung für die Verwaltung. Das Formular stammt von EasyAdmin; geprüft wird es von
 * form_login (config/packages/security.yaml).
 */
final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
            'page_title' => 'Foto-Tagebuch · Verwaltung',
            'csrf_token_intention' => 'authenticate',
            'action' => $this->generateUrl('app_login'),
            'target_path' => $this->generateUrl('admin'),
            'username_label' => 'E-Mail',
            'password_label' => 'Passwort',
            'sign_in_label' => 'Anmelden',
            'favicon_path' => 'favicon.ico',
        ]);
    }

    /** Wird von der Firewall abgefangen und nie ausgeführt. */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Wird von der Firewall abgefangen.');
    }
}
