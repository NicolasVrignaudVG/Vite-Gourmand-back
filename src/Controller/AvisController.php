<?php namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// ═══════════════════════════════════════════════════════════
// AVIS
// ═══════════════════════════════════════════════════════════
#[Route('/api/avis')]
class AvisController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AvisRepository $avisRepo) {}

    // GET /api/avis — avis validés (public)
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $avis = $this->avisRepo->findBy(['statut' => 'valide'], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($a) => [
            'id'          => $a->getId(),
            'note'        => $a->getNote(),
            'description' => $a->getDescription(),
            // RGPD : pseudonyme stocké par l'utilisateur, ou "Client vérifié" par défaut.
            // Ne jamais exposer le nom/prénom réel sur une route publique.
            'auteur'      => $a->getUtilisateur()->getPseudonyme() ?: 'Client vérifié',
            'date'        => $a->getCreatedAt()->format('d/m/Y'),
        ], $avis));
    }

    // POST /api/avis — laisser un avis (utilisateur connecté)
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $commande = $this->em->getRepository(Commande::class)->find($data['commande_id'] ?? 0);
        $note     = (int) ($data['note'] ?? 0);

        $validationError = $this->validateAvisData($commande, $note);
        if ($validationError !== null) {
            return $validationError;
        }

        $avis = new Avis();
        $avis->setUtilisateur($this->getUser());
        $avis->setCommande($commande);
        $avis->setNote($note);
        $avis->setDescription($data['description'] ?? null);

        $this->em->persist($avis);
        $this->em->flush();

        return $this->json(['message' => 'Avis envoyé, en attente de validation.'], 201);
    }

    private function validateAvisData(?Commande $commande, int $note): ?JsonResponse
    {
        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Commande introuvable.'], 404);
        }
        if ($commande->getStatut() !== 'terminee') {
            return $this->json(['error' => 'Vous ne pouvez laisser un avis que sur une commande terminée.'], 400);
        }
        if ($note < 1 || $note > 5) {
            return $this->json(['error' => 'La note doit être entre 1 et 5.'], 400);
        }
        return null;
    }

    // PATCH /api/avis/{id}/valider — employé valide un avis
    #[Route('/{id}/valider', methods: ['PATCH'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function valider(Avis $avis): JsonResponse
    {
        $avis->setStatut('valide');
        $this->em->flush();
        return $this->json(['message' => 'Avis validé.']);
    }

    // PATCH /api/avis/{id}/refuser — employé refuse un avis
    #[Route('/{id}/refuser', methods: ['PATCH'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function refuser(Avis $avis): JsonResponse
    {
        $avis->setStatut('refuse');
        $this->em->flush();
        return $this->json(['message' => 'Avis refusé.']);
    }

    // GET /api/avis/pending — avis en attente (employé)
    #[Route('/pending', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function pending(): JsonResponse
    {
        $avis = $this->avisRepo->findBy(['statut' => 'en_attente'], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($a) => [
            'id'          => $a->getId(),
            'note'        => $a->getNote(),
            'description' => $a->getDescription(),
            'auteur'      => $a->getUtilisateur()->getPrenom() . ' ' . $a->getUtilisateur()->getNom(),
            'date'        => $a->getCreatedAt()->format('d/m/Y'),
        ], $avis));
    }
}
