<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/upload')]
#[IsGranted('ROLE_EMPLOYE')]
class UploadController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private string           $uploadDir,
    ) {}

    #[Route('/image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        // Vérification type MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['error' => 'Format non autorisé. Utilisez JPG, PNG ou WebP.'], 400);
        }

        // Taille max 5 Mo
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Image trop lourde (max 5 Mo).'], 400);
        }

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }

        // Nom de fichier sécurisé
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalName);
        $newFilename  = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacer le fichier
        $file->move($this->uploadDir, $newFilename);

        return $this->json([
            'url'      => 'images/' . $newFilename,
            'filename' => $newFilename,
        ], 201);
    }
}
