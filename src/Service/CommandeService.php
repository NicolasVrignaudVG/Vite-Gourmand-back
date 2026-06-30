<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\CommandeMenu;
use App\Entity\CommandePlat;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\SuiviCommande;
use App\Entity\Utilisateur;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use MongoDB\BSON\UTCDateTime;

/**
 * Centralise la logique métier de création et de mise à jour des commandes,
 * auparavant portée par CommandeController (Fat Controller).
 * Le contrôleur se limite désormais à : décoder la requête, appeler ce service,
 * et formater la réponse JSON.
 */
class CommandeService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MenuRepository         $menuRepo,
        private MailerService          $mailer,
        private MongoService           $mongo,
        private DeliveryService        $delivery,
    ) {}

    /**
     * Crée une commande complète : menus, plats choisis, livraison, suivi,
     * synchronisation MongoDB et e-mail de confirmation.
     *
     * @throws \InvalidArgumentException si aucun menu valide n'est fourni
     */
    public function creerCommande(Utilisateur $user, array $data): Commande
    {
        $menusCommandes = $this->normaliserMenusCommandes($data);

        if (empty($menusCommandes)) {
            throw new \InvalidArgumentException('Au moins un menu est requis.');
        }

        $livraisonData = $this->delivery->calculerFrais(
            $data['adresse_livraison'] ?? '',
            $data['ville_livraison']   ?? '',
            $data['cp_livraison']      ?? ''
        );
        $prixLivraison = $livraisonData['frais'];

        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setDatePrestation(new \DateTime($data['date_prestation']));
        $commande->setAdresseLivraison($data['adresse_livraison'] ?? '');
        $commande->setVilleLivraison($data['ville_livraison'] ?? '');
        $commande->setCpLivraison($data['cp_livraison'] ?? '');
        $commande->setPrixLivraison($prixLivraison);

        [$prixMenuTotal, $premierMenu] = $this->ajouterMenusEtPlats($commande, $menusCommandes);

        if ($premierMenu) {
            $commande->setMenu($premierMenu);
        }
        $commande->setNombrePersonnes((int) ($data['nombre_personnes'] ?? 1));
        $commande->setPrixMenu($prixMenuTotal);
        $commande->setPrixTotal($prixMenuTotal + $prixLivraison);
        $commande->setRemise(0);

        $this->ajouterSuiviInitial($commande);

        $this->em->persist($commande);
        $this->em->flush();

        if ($premierMenu) {
            $this->synchroniserMongo($commande, $premierMenu);
        }

        $this->mailer->sendConfirmationCommande($commande);

        return $commande;
    }

    /**
     * Modifie une commande existante (adresse, date, nombre de personnes)
     * et recalcule les frais de livraison si nécessaire.
     */
    public function modifierCommande(Commande $commande, array $data): Commande
    {
        if (isset($data['date_prestation'])) {
            $commande->setDatePrestation(new \DateTime($data['date_prestation']));
        }
        if (isset($data['adresse_livraison'])) {
            $commande->setAdresseLivraison($data['adresse_livraison']);
        }

        if (isset($data['nombre_personnes'])) {
            $nb   = max($commande->getMenu()->getNombrePersonneMinimum(), (int) $data['nombre_personnes']);
            $prix = $commande->getMenu()->calculerPrix($nb);
            $commande->setNombrePersonnes($nb);
            $commande->setPrixMenu($prix['prix_total']);
            $commande->setPrixTotal($prix['prix_total'] + $commande->getPrixLivraison());
            $commande->setRemise($prix['remise_pct']);
        }

        if (isset($data['adresse_livraison']) || isset($data['ville_livraison']) || isset($data['cp_livraison'])) {
            if (isset($data['ville_livraison'])) {
                $commande->setVilleLivraison($data['ville_livraison']);
            }
            if (isset($data['cp_livraison'])) {
                $commande->setCpLivraison($data['cp_livraison']);
            }

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

        return $commande;
    }

    /**
     * Annule une commande encore en attente et restitue le stock du menu.
     */
    public function annulerCommande(Commande $commande): void
    {
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
    }

    /**
     * Met à jour le statut d'une commande (action employé), gère le motif
     * d'annulation et déclenche les e-mails associés à certains statuts.
     */
    public function mettreAJourStatut(Commande $commande, string $statut, ?string $commentaire, ?string $motif, ?string $modeContact): void
    {
        if ($statut === 'annulee') {
            $commande->setMotifAnnulation($motif);
            $commande->setModeContact($modeContact);
        }

        $commande->setStatut($statut);

        $suivi = new SuiviCommande();
        $suivi->setCommande($commande);
        $suivi->setStatut($statut);
        $suivi->setCommentaire($commentaire);
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
    }

    /**
     * Normalise le payload reçu : supporte le format multi-menus
     * (menus_commandes[]) ainsi que l'ancien format mono-menu (menu_id).
     */
    private function normaliserMenusCommandes(array $data): array
    {
        $menusCommandes = $data['menus_commandes'] ?? [];

        if (empty($menusCommandes) && isset($data['menu_id'])) {
            $menusCommandes = [[
                'menu_id'          => $data['menu_id'],
                'nombre_personnes' => $data['nombre_personnes'] ?? 1,
                'plats_choisis'    => $data['plats_choisis'] ?? [],
            ]];
        }

        return $menusCommandes;
    }

    /**
     * Ajoute chaque menu commandé (avec ses plats) à la commande,
     * décrémente le stock, et retourne [prixMenuTotal, premierMenu].
     *
     * @return array{0: float, 1: ?Menu}
     */
    private function ajouterMenusEtPlats(Commande $commande, array $menusCommandes): array
    {
        $prixMenuTotal = 0.0;
        $premierMenu   = null;

        foreach ($menusCommandes as $mc) {
            $menu = $this->menuRepo->find($mc['menu_id'] ?? 0);
            if (!$menu || !$menu->isActif()) {
                continue;
            }
            if ($menu->getQuantiteRestante() <= 0) {
                continue;
            }

            $nb   = max($menu->getNombrePersonneMinimum(), (int) ($mc['nombre_personnes'] ?? 1));
            $prix = $menu->calculerPrix($nb);

            $cm = new CommandeMenu();
            $cm->setMenu($menu);
            $cm->setNombrePersonnes($nb);
            $cm->setPrixTotal($prix['prix_total']);
            $cm->setRemise($prix['remise_pct']);
            $commande->addCommandeMenu($cm);
            $this->em->persist($cm);

            foreach ($mc['plats_choisis'] ?? [] as $platId) {
                $plat = $this->em->getRepository(Plat::class)->find($platId);
                if ($plat) {
                    $cp = new CommandePlat();
                    $cp->setCommande($commande);
                    $cp->setPlat($plat);
                    $this->em->persist($cp);
                }
            }

            $prixMenuTotal += $prix['prix_total'];
            $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);
            if (!$premierMenu) {
                $premierMenu = $menu;
            }
        }

        return [$prixMenuTotal, $premierMenu];
    }

    private function ajouterSuiviInitial(Commande $commande): void
    {
        $suivi = new SuiviCommande();
        $suivi->setCommande($commande);
        $suivi->setStatut('en_attente');
        $suivi->setCommentaire('Commande reçue');
        $commande->addSuivi($suivi);
        $this->em->persist($suivi);
    }

    private function synchroniserMongo(Commande $commande, Menu $menu): void
    {
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
    }
}
