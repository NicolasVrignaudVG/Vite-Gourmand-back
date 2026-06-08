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
        return $this->json([
            'status'  => 'ok',
            'app'     => 'Vite & Gourmand API',
            'version' => '1.0.0',
        ]);
    }
}
