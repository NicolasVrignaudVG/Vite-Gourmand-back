<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\CommandeService;
use App\Service\DeliveryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/commandes')]
#[IsGranted('ROLE_USER')]
class CommandeController extends AbstractController
{
    public function __construct(
        private CommandeRepository $cmdRepo,
        private CommandeService    $commandeService,
        private DeliveryService    $delivery,
    ) {}

    // GET /api/commandes — mes commandes
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user      = $this->getUser();
        $commandes = $this->cmdRepo->findBy(['utilisateur' => $user], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($c) => $this->formatCommande($c), $commandes));
    }

    // GET /api/commandes/toutes — toutes les commandes (employé)
    #[Route('/toutes', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function toutesCommandes(): JsonResponse
    {
        $commandes = $this->cmdRepo->findBy([], ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($c) => $this->formatCommande($c), $commandes));
    }

    // POST /api/commandes — créer une commande
    // Le contrôleur se limite à décoder la requête, déléguer la logique métier
    // au CommandeService, et formater la réponse — conformément aux bonnes
    // pratiques MVC (éviter le Fat Controller).
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $commande = $this->commandeService->creerCommande($this->getUser(), $data);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        return $this->json($this->formatCommande($commande), 201);
    }

    // GET /api/commandes/livraison — calcul frais livraison
    #[Route('/livraison', methods: ['GET'])]
    public function calculerLivraison(Request $request): JsonResponse
    {
        $adresse = $request->query->get('adresse', '');
        $ville   = $request->query->get('ville',   '');
        $cp      = $request->query->get('cp',      '');

        if (!$adresse || !$ville || !$cp) {
            return $this->json(['error' => 'Adresse, ville et CP requis.'], 400);
        }

        $result = $this->delivery->calculerFrais($adresse, $ville, $cp);
        return $this->json($result);
    }

    // PUT /api/commandes/{id} — modifier (si en_attente)
    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Commande $commande, Request $request): JsonResponse
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }
        if (!$commande->canBeModified()) {
            return $this->json(['error' => 'Cette commande ne peut plus être modifiée.'], 400);
        }

        $data     = json_decode($request->getContent(), true);
        $commande = $this->commandeService->modifierCommande($commande, $data);

        return $this->json($this->formatCommande($commande));
    }

    // DELETE /api/commandes/{id} — annuler (si en_attente)
    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function cancel(Commande $commande): JsonResponse
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }
        if (!$commande->canBeCancelled()) {
            return $this->json(['error' => 'Cette commande ne peut plus être annulée.'], 400);
        }

        $this->commandeService->annulerCommande($commande);

        return $this->json(['message' => 'Commande annulée.']);
    }

    // PATCH /api/commandes/{id}/statut — employé met à jour le statut
    #[Route('/{id}/statut', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function updateStatut(Commande $commande, Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $statut = $data['statut'] ?? '';

        if (!in_array($statut, Commande::STATUTS)) {
            return $this->json(['error' => 'Statut invalide.'], 400);
        }
        if ($statut === 'annulee' && (empty($data['motif']) || empty($data['mode_contact']))) {
            return $this->json(['error' => 'Motif et mode de contact obligatoires pour annuler.'], 400);
        }

        $this->commandeService->mettreAJourStatut(
            $commande,
            $statut,
            $data['commentaire'] ?? null,
            $data['motif'] ?? null,
            $data['mode_contact'] ?? null
        );

        return $this->json(['message' => 'Statut mis à jour.']);
    }

    private function formatCommande(Commande $c): array
    {
        return [
            'id'               => $c->getId(),
            'numeroCommande'   => $c->getNumeroCommande(),
            'statut'           => $c->getStatut(),
            'datePrestation'   => $c->getDatePrestation()?->format('c'),
            'adresseLivraison' => $c->getAdresseLivraison(),
            'villeLivraison'   => $c->getVilleLivraison(),
            'cpLivraison'      => $c->getCpLivraison(),
            'nombrePersonnes'  => $c->getNombrePersonnes(),
            'prixMenu'         => $c->getPrixMenu(),
            'prixLivraison'    => $c->getPrixLivraison(),
            'prixTotal'        => $c->getPrixTotal(),
            'remise'           => $c->getRemise(),
            'createdAt'        => $c->getCreatedAt()?->format('c'),
            'menu'             => $c->getMenu() ? [
                'id'    => $c->getMenu()->getId(),
                'titre' => $c->getMenu()->getTitre(),
            ] : null,
            'menus'            => array_map(fn($cm) => [
                'id'              => $cm->getMenu()?->getId(),
                'titre'           => $cm->getMenu()?->getTitre(),
                'nombrePersonnes' => $cm->getNombrePersonnes(),
                'prixTotal'       => $cm->getPrixTotal(),
                'remise'          => $cm->getRemise(),
            ], $c->getCommandeMenus()->toArray()),
            'utilisateur'      => $c->getUtilisateur() ? [
                'id'        => $c->getUtilisateur()->getId(),
                'nom'       => $c->getUtilisateur()->getNom(),
                'prenom'    => $c->getUtilisateur()->getPrenom(),
                'email'     => $c->getUtilisateur()->getEmail(),
                'telephone' => $c->getUtilisateur()->getTelephone(),
            ] : null,
            'suivis'           => array_map(fn($s) => [
                'statut'     => $s->getStatut(),
                'commentaire'=> $s->getCommentaire(),
                'created_at' => $s->getCreatedAt()?->format('c'),
            ], $c->getSuivis()->toArray()),
        ];
    }
}
