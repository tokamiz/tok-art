<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verwaltung unter /admin (nur nach Anmeldung, siehe config/packages/security.yaml).
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // Startseite der Verwaltung: die Liste der Einträge
        return $this->redirectToRoute('admin_entry_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Foto-Tagebuch · Verwaltung')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Zur Website', 'fa fa-globe', '/');

        yield MenuItem::section('Inhalte');
        yield MenuItem::linkTo(EntryCrudController::class, 'Einträge', 'fa fa-book-open');
        yield MenuItem::linkTo(TripCrudController::class, 'Reisen', 'fa fa-route');
        yield MenuItem::linkTo(TopicCrudController::class, 'Themen', 'fa fa-tags');
        yield MenuItem::linkTo(MediaCrudController::class, 'Fotos und Videos', 'fa fa-image');
        yield MenuItem::linkTo(KeywordCrudController::class, 'Schlagwörter', 'fa fa-hashtag');

        yield MenuItem::section('Kontakt');
        yield MenuItem::linkTo(MessageCrudController::class, 'Nachrichten', 'fa fa-envelope');

        yield MenuItem::section();
        yield MenuItem::linkToLogout('Abmelden', 'fa fa-right-from-bracket');
    }
}
