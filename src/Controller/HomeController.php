<?php
// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $response = $this->json([
            'status'  => 'ok',
            'app'     => 'Vite & Gourmand API',
            'version' => '1.0.0',
        ]);

        // Header HSTS ajouté manuellement — forced_ssl cause des boucles
        // de redirection sur Render car le proxy Render reçoit les requêtes en HTTP.
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        return $response;
    }
}
