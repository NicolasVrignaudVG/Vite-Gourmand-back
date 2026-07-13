<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/upload')]
#[IsGranted('ROLE_EMPLOYE')]
class UploadController extends AbstractController
{
    /** Types MIME réellement autorisés (vérifiés sur le contenu du fichier, pas sur son nom). */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /** Taille maximale : 5 Mo. */
    private const MAX_SIZE = 5 * 1024 * 1024;

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

        // Le fichier a-t-il été correctement transmis ?
        if (!$file->isValid()) {
            return $this->json(['error' => "Le transfert du fichier a échoué."], 400);
        }

        // Vérification du type MIME RÉEL (lu dans les octets du fichier via finfo,
        // et non dans l'en-tête Content-Type envoyé par le client, qui est falsifiable).
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return $this->json(
                ['error' => 'Format non autorisé. Formats acceptés : JPG, PNG, WebP, GIF.'],
                400
            );
        }

        // Taille maximale
        if ($file->getSize() > self::MAX_SIZE) {
            return $this->json(['error' => 'Image trop lourde (max 5 Mo).'], 400);
        }

        // Création du dossier de destination si nécessaire
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            return $this->json(['error' => "Le dossier de destination n'a pas pu être créé."], 500);
        }

        // Nom de fichier sécurisé :
        //  - le nom d'origine est nettoyé (slug) : plus de caractères spéciaux ni de "../"
        //  - un identifiant unique rend l'URL imprévisible
        //  - l'extension est DÉDUITE DU CONTENU réel du fichier (guessExtension),
        //    ce qui neutralise les attaques par double extension (ex : shell.php.jpg)
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalName);
        $newFilename  = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacement du fichier
        try {
            $file->move($this->uploadDir, $newFilename);
        } catch (FileException) {
            return $this->json(["error" => "L'enregistrement du fichier a échoué."], 500);
        }

        return $this->json([
            'url'      => 'images/' . $newFilename,
            'filename' => $newFilename,
        ], 201);
    }
}
