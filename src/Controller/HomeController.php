<?php
// src/Controller/HomeController.php
// ─────────────────────────────────────────────────────────────────
// Route racine — retourne un 200 avec un message de statut.
// Nécessaire pour que les headers de sécurité HTTP soient présents
// sur les scans de sécurité (securityheaders.com, etc.)
// ─────────────────────────────────────────────────────────────────

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'status'  => 'ok',
            'app'     => 'Vite & Gourmand API',
            'version' => '1.0.0',
        ]);
    }
}
