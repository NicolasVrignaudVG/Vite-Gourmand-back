<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\MenuImage;
use App\Entity\Regime;
use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Centralise la logique métier d'hydratation d'un menu à partir des données reçues :
 * champs simples, associations thème et régime, image principale.
 * Auparavant portée par MenuController (Fat Controller), comme la logique
 * de commande l'était par CommandeController.
 * Le contrôleur se limite désormais à : décoder la requête, appeler ce service,
 * et formater la réponse JSON.
 */
class MenuService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * Hydrate un menu (création ou mise à jour) à partir du payload reçu.
     * Les champs absents du payload sont laissés inchangés.
     */
    public function hydrateMenu(Menu $menu, array $data): void
    {
        if (isset($data['titre']))                   $menu->setTitre($data['titre']);
        if (isset($data['description']))             $menu->setDescription($data['description']);
        if (isset($data['conditions']))              $menu->setConditions($data['conditions']);
        if (isset($data['prix_par_personne']))       $menu->setPrixParPersonne((float) $data['prix_par_personne']);
        if (isset($data['nombre_personne_minimum'])) $menu->setNombrePersonneMinimum((int) $data['nombre_personne_minimum']);
        if (isset($data['quantite_restante']))       $menu->setQuantiteRestante((int) $data['quantite_restante']);

        $this->associerTheme($menu, $data);
        $this->associerRegime($menu, $data);
        $this->associerImagePrincipale($menu, $data);

        // Actif par défaut à la création
        if (!$menu->getId()) {
            $menu->setActif(true);
        }
    }

    /**
     * Associe le thème demandé, en le créant s'il n'existe pas encore.
     */
    private function associerTheme(Menu $menu, array $data): void
    {
        if (empty($data['theme'])) {
            return;
        }

        $theme = $this->em->getRepository(Theme::class)->findOneBy(['libelle' => $data['theme']]);
        if (!$theme) {
            $theme = new Theme();
            $theme->setLibelle($data['theme']);
            $this->em->persist($theme);
        }
        $menu->setTheme($theme);
    }

    /**
     * Associe le régime demandé, en le créant s'il n'existe pas encore.
     */
    private function associerRegime(Menu $menu, array $data): void
    {
        if (empty($data['regime'])) {
            return;
        }

        $regime = $this->em->getRepository(Regime::class)->findOneBy(['libelle' => $data['regime']]);
        if (!$regime) {
            $regime = new Regime();
            $regime->setLibelle($data['regime']);
            $this->em->persist($regime);
        }
        $menu->setRegime($regime);
    }

    /**
     * Met à jour l'image principale si elle existe, la crée sinon.
     */
    private function associerImagePrincipale(Menu $menu, array $data): void
    {
        if (empty($data['image'])) {
            return;
        }

        foreach ($menu->getImages() as $img) {
            if ($img->isPrincipale()) {
                $img->setUrl($data['image']);
                return;
            }
        }

        $newImg = new MenuImage();
        $newImg->setUrl($data['image']);
        $newImg->setAlt($data['titre'] ?? 'Image menu');
        $newImg->setPrincipale(true);
        $newImg->setMenu($menu);
        $this->em->persist($newImg);
    }
}