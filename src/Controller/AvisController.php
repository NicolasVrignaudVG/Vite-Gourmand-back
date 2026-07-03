<?php namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// ═══════════════════════════════════════════════════════════
// AVIS
// ═══════════════════════════════════════════════════════════
#[OA\Tag(name: 'Avis clients')]
#[Route('/api/avis')]
class AvisController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private AvisRepository $avisRepo) {}

    #[OA\Get(path: '/api/avis', summary: 'Lister les avis validés (public)',
        responses: [new OA\Response(response: 200, description: 'Liste des avis validés')])]
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $avis = $this->avisRepo->findBy(['statut' => 'valide'], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($a) => [
            'id'          => $a->getId(),
            'note'        => $a->getNote(),
            'description' => $a->getDescription(),
            'auteur'      => $a->getUtilisateur()->getPseudonyme() ?: 'Client vérifié',
            'date'        => $a->getCreatedAt()->format('Y-m-d'),
        ], $avis));
    }

    #[OA\Post(path: '/api/avis', summary: "Soumettre un avis (ROLE_USER)",
        security: [['cookieAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['commande_id','note'],
            properties: [
                new OA\Property(property: 'commande_id',  type: 'integer'),
                new OA\Property(property: 'note',         type: 'integer', minimum: 1, maximum: 5),
                new OA\Property(property: 'description',  type: 'string'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: "Avis soumis, en attente de validation"),
            new OA\Response(response: 400, description: 'Commande invalide ou non terminée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ])]
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

    // GET /api/avis/all — tous les avis (admin)
    // Route littérale déclarée avant /{id} : aucune ambiguïté possible.
    #[OA\Get(path: '/api/avis/all', summary: 'Tous les avis (ROLE_EMPLOYE)',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Tous les avis')])]
    #[Route('/all', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function all(): JsonResponse
    {
        $avis = $this->avisRepo->findBy([], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($a) => [
            'id'          => $a->getId(),
            'note'        => $a->getNote(),
            'description' => $a->getDescription(),
            'auteur'      => $a->getUtilisateur()->getPrenom() . ' ' . $a->getUtilisateur()->getNom(),
            'statut'      => $a->getStatut(),
            'date'        => $a->getCreatedAt()->format('Y-m-d'),
        ], $avis));
    }

    // GET /api/avis/pending — avis en attente (employé)
    // Route littérale déclarée avant /{id} : bonne pratique (chemins fixes avant chemins paramétrés).
    #[OA\Get(path: '/api/avis/pending', summary: 'Avis en attente (ROLE_EMPLOYE)',
        security: [['cookieAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Avis en attente')])]
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
            'date'        => $a->getCreatedAt()->format('Y-m-d'),
        ], $avis));
    }

    // DELETE /api/avis/{id} — supprimer un avis (admin)
    // requirements: ['id' => '\d+'] empêche définitivement que "all" ou "pending"
    // soient un jour interceptés par ce placeholder générique, quel que soit l'ordre de déclaration.
    // ParamConverter (EntityValueResolver Symfony 6.2+) : {id} est résolu automatiquement
    // en Avis $avis via un appel implicite à Avis::find($id), car le nom du paramètre
    // de route "id" correspond au nom conventionnel attendu pour une entité Avis.
    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function delete(Avis $avis): JsonResponse
    {
        $this->em->remove($avis);
        $this->em->flush();
        return $this->json(['message' => 'Avis supprimé.']);
    }

    // PATCH /api/avis/{id} — modifier note/description (admin)
    #[Route('/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function update(Avis $avis, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['note'])) {
            $note = (int) $data['note'];
            if ($note < 1 || $note > 5) return $this->json(['error' => 'Note invalide.'], 400);
            $avis->setNote($note);
        }
        if (array_key_exists('description', $data)) {
            $avis->setDescription(strip_tags($data['description'] ?? ''));
        }
        $this->em->flush();
        return $this->json(['message' => 'Avis modifié.']);
    }

    // PATCH /api/avis/{id}/valider — employé valide un avis
    #[Route('/{id}/valider', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function valider(Avis $avis): JsonResponse
    {
        $avis->setStatut('valide');
        $this->em->flush();
        return $this->json(['message' => 'Avis validé.']);
    }

    // PATCH /api/avis/{id}/refuser — employé refuse un avis
    #[Route('/{id}/refuser', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function refuser(Avis $avis): JsonResponse
    {
        $avis->setStatut('refuse');
        $this->em->flush();
        return $this->json(['message' => 'Avis refusé.']);
    }
}
