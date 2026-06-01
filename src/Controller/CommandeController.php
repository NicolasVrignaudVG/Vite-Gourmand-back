<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandePlat;
use App\Entity\Plat;
use App\Entity\SuiviCommande;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Service\DeliveryService;
use App\Service\MailerService;
use App\Service\MongoService;
use Doctrine\ORM\EntityManagerInterface;
use MongoDB\BSON\UTCDateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/commandes')]
#[IsGranted('ROLE_USER')]
class CommandeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MenuRepository         $menuRepo,
        private CommandeRepository     $cmdRepo,
        private MailerService          $mailer,
        private SerializerInterface    $serializer,
        private MongoService           $mongo,
        private DeliveryService        $delivery,
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
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $menu = $this->menuRepo->find($data['menu_id'] ?? 0);
        if (!$menu || !$menu->isActif()) {
            return $this->json(['error' => 'Menu introuvable ou inactif.'], 404);
        }

        $nb = max($menu->getNombrePersonneMinimum(), (int) ($data['nombre_personnes'] ?? 1));

        if ($menu->getQuantiteRestante() <= 0) {
            return $this->json(['error' => 'Ce menu n\'est plus disponible.'], 400);
        }

        // Calcul prix menu
        $prix = $menu->calculerPrix($nb);

        // Calcul frais livraison avec distance réelle
        $livraisonData = $this->delivery->calculerFrais(
            $data['adresse_livraison'] ?? '',
            $data['ville_livraison']   ?? '',
            $data['cp_livraison']      ?? ''
        );
        $prixLivraison = $livraisonData['frais'];

        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setMenu($menu);
        $commande->setNombrePersonnes($nb);
        $commande->setDatePrestation(new \DateTime($data['date_prestation']));
        $commande->setAdresseLivraison($data['adresse_livraison'] ?? '');
        $commande->setVilleLivraison($data['ville_livraison'] ?? '');
        $commande->setCpLivraison($data['cp_livraison'] ?? '');
        $commande->setPrixMenu($prix['prix_total']);
        $commande->setPrixLivraison($prixLivraison);
        $commande->setPrixTotal($prix['prix_total'] + $prixLivraison);
        $commande->setRemise($prix['remise_pct']);

        // Suivi initial
        $suivi = new SuiviCommande();
        $suivi->setCommande($commande);
        $suivi->setStatut('en_attente');
        $suivi->setCommentaire('Commande reçue');
        $commande->addSuivi($suivi);

        // Décrémente le stock
        $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);

        $this->em->persist($commande);
        $this->em->persist($suivi);

        // Stocker les plats choisis
        foreach ($data['plats_choisis'] ?? [] as $platId) {
            $plat = $this->em->getRepository(Plat::class)->find($platId);
            if ($plat) {
                $cp = new CommandePlat();
                $cp->setCommande($commande);
                $cp->setPlat($plat);
                $this->em->persist($cp);
            }
        }

        $this->em->flush();

        // Enregistrement dans MongoDB
        $this->mongo->upsertCommande([
            'commande_id'      => $commande->getId(),
            'menu_id'          => $menu->getId(),
            'menu_titre'       => $menu->getTitre(),
            'prix_total'       => $commande->getPrixTotal(),
            'nombre_personnes' => $commande->getNombrePersonnes(),
            'statut'           => $commande->getStatut(),
            'date_prestation'  => new UTCDateTime(
                $commande->getDatePrestation()->getTimestamp() * 1000
            ),
            'created_at' => new UTCDateTime(),
        ]);

        // Mail de confirmation
        $this->mailer->sendConfirmationCommande($commande);

        $json = $this->serializer->serialize($commande, 'json', ['groups' => 'commande:read']);
        return new JsonResponse($json, 201, [], true);
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
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Commande $commande, Request $request): JsonResponse
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }
        if (!$commande->canBeModified()) {
            return $this->json(['error' => 'Cette commande ne peut plus être modifiée.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['date_prestation']))   $commande->setDatePrestation(new \DateTime($data['date_prestation']));
        if (isset($data['adresse_livraison'])) $commande->setAdresseLivraison($data['adresse_livraison']);

        if (isset($data['nombre_personnes'])) {
            $nb   = max($commande->getMenu()->getNombrePersonneMinimum(), (int) $data['nombre_personnes']);
            $prix = $commande->getMenu()->calculerPrix($nb);
            $commande->setNombrePersonnes($nb);
            $commande->setPrixMenu($prix['prix_total']);
            $commande->setPrixTotal($prix['prix_total'] + $commande->getPrixLivraison());
            $commande->setRemise($prix['remise_pct']);
        }

        // Recalcul livraison si adresse modifiée
        if (isset($data['adresse_livraison']) || isset($data['ville_livraison']) || isset($data['cp_livraison'])) {
            if (isset($data['ville_livraison'])) $commande->setVilleLivraison($data['ville_livraison']);
            if (isset($data['cp_livraison']))    $commande->setCpLivraison($data['cp_livraison']);

            $livraisonData = $this->delivery->calculerFrais(
                $commande->getAdresseLivraison(),
                $commande->getVilleLivraison(),
                $commande->getCpLivraison()
            );
            $commande->setPrixLivraison($livraisonData['frais']);
            $commande->setPrixTotal($commande->getPrixMenu() + $livraisonData['frais']);
        }

        $this->em->flush();

        $this->mongo->upsertCommande([
            'commande_id'      => $commande->getId(),
            'prix_total'       => $commande->getPrixTotal(),
            'nombre_personnes' => $commande->getNombrePersonnes(),
            'date_prestation'  => new UTCDateTime(
                $commande->getDatePrestation()->getTimestamp() * 1000
            ),
        ]);

        $json = $this->serializer->serialize($commande, 'json', ['groups' => 'commande:read']);
        return new JsonResponse($json, 200, [], true);
    }

    // DELETE /api/commandes/{id} — annuler (si en_attente)
    #[Route('/{id}', methods: ['DELETE'])]
    public function cancel(Commande $commande): JsonResponse
    {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }
        if (!$commande->canBeCancelled()) {
            return $this->json(['error' => 'Cette commande ne peut plus être annulée.'], 400);
        }

        $commande->setStatut('annulee');
        $commande->getMenu()->setQuantiteRestante($commande->getMenu()->getQuantiteRestante() + 1);

        $suivi = new SuiviCommande();
        $suivi->setCommande($commande);
        $suivi->setStatut('annulee');
        $suivi->setCommentaire('Annulée par l\'utilisateur');
        $this->em->persist($suivi);
        $this->em->flush();

        $this->mongo->upsertCommande([
            'commande_id' => $commande->getId(),
            'statut'      => 'annulee',
        ]);

        return $this->json(['message' => 'Commande annulée.']);
    }

    // PATCH /api/commandes/{id}/statut — employé met à jour le statut
    #[Route('/{id}/statut', methods: ['PATCH'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function updateStatut(Commande $commande, Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $statut = $data['statut'] ?? '';

        if (!in_array($statut, Commande::STATUTS)) {
            return $this->json(['error' => 'Statut invalide.'], 400);
        }

        if ($statut === 'annulee') {
            if (empty($data['motif']) || empty($data['mode_contact'])) {
                return $this->json(['error' => 'Motif et mode de contact obligatoires pour annuler.'], 400);
            }
            $commande->setMotifAnnulation($data['motif']);
            $commande->setModeContact($data['mode_contact']);
        }

        $commande->setStatut($statut);

        $suivi = new SuiviCommande();
        $suivi->setCommande($commande);
        $suivi->setStatut($statut);
        $suivi->setCommentaire($data['commentaire'] ?? null);
        $this->em->persist($suivi);

        if ($statut === 'retour_materiel') {
            $this->mailer->sendRetourMateriel($commande);
        }
        if ($statut === 'terminee') {
            $this->mailer->sendCommandeTerminee($commande);
        }

        $this->em->flush();

        $this->mongo->upsertCommande([
            'commande_id' => $commande->getId(),
            'statut'      => $statut,
        ]);

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
