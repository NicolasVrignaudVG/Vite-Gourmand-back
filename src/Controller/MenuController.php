<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuImage;
use App\Entity\Plat;
use App\Entity\Theme;
use App\Entity\Regime;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Menus')]
#[Route('/api/menus')]
class MenuController extends AbstractController
{
    private const PATH_ID = '/{id}';
    private const ID_REQUIREMENTS = ['id' => '\d+'];

    public function __construct(
        private EntityManagerInterface $em,
        private MenuRepository         $menuRepo,
    ) {}

    // GET /api/menus
    #[OA\Get(path: '/api/menus', summary: 'Lister les menus (filtres disponibles)',
        parameters: [
            new OA\Parameter(name: 'prix_max',      in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'prix_min',      in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'theme',         in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'regime',        in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'personnes_min',  in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Liste des menus actifs')])]
    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $qb = $this->menuRepo->createQueryBuilder('m')
            ->where('m.actif = true')
            ->leftJoin('m.theme',  't')->addSelect('t')
            ->leftJoin('m.regime', 'r')->addSelect('r')
            ->leftJoin('m.images', 'i')->addSelect('i');

        if ($v = $request->query->get('prix_max')) {
            $qb->andWhere('m.prixParPersonne <= :prixMax')->setParameter('prixMax', $v);
        }
        if ($v = $request->query->get('prix_min')) {
            $qb->andWhere('m.prixParPersonne >= :prixMin')->setParameter('prixMin', $v);
        }
        if ($v = $request->query->get('theme')) {
            $qb->andWhere('t.libelle = :theme')->setParameter('theme', $v);
        }
        if ($v = $request->query->get('regime')) {
            $qb->andWhere('r.libelle = :regime')->setParameter('regime', $v);
        }
        if ($v = $request->query->get('personnes_min')) {
            $qb->andWhere('m.nombrePersonneMinimum <= :pers')->setParameter('pers', $v);
        }

        $menus = $qb->getQuery()->getResult();

        return $this->json(array_map(fn(Menu $m) => $this->formatMenu($m), $menus));
    }

    // GET /api/menus/{id}
    #[OA\Get(path: '/api/menus/{id}', summary: "Detail d'un menu",
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Détail du menu'),
            new OA\Response(response: 404, description: 'Menu introuvable'),
        ])]
    #[Route(self::PATH_ID, methods: ['GET'], requirements: self::ID_REQUIREMENTS)]
    public function show(Menu $menu): JsonResponse
    {
        return $this->json($this->formatMenuDetail($menu));
    }

    // POST /api/menus
    #[OA\Post(path: '/api/menus', summary: "Creer un menu (ROLE_EMPLOYE)",
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Menu créé'),
            new OA\Response(response: 400, description: 'Données invalides'),
        ])]
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $menu = new Menu();
        $this->hydrateMenu($menu, $data);
        $this->em->persist($menu);
        $this->em->flush();
        return $this->json(['id' => $menu->getId(), 'message' => 'Menu créé.'], 201);
    }

    // PUT /api/menus/{id}
    #[OA\Put(path: '/api/menus/{id}', summary: "Modifier un menu (ROLE_EMPLOYE)",
        security: [['cookieAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Menu mis à jour')])]
    #[Route(self::PATH_ID, methods: ['PUT'], requirements: self::ID_REQUIREMENTS)]
    #[IsGranted('ROLE_EMPLOYE')]
    public function update(Menu $menu, Request $request): JsonResponse
    {
        $this->hydrateMenu($menu, json_decode($request->getContent(), true));
        $this->em->flush();
        return $this->json(['message' => 'Menu mis à jour.']);
    }

    // DELETE /api/menus/{id}
    #[OA\Delete(path: '/api/menus/{id}', summary: "Desactiver un menu (ROLE_EMPLOYE)",
        security: [['cookieAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Menu désactivé')])]
    #[Route(self::PATH_ID, methods: ['DELETE'], requirements: self::ID_REQUIREMENTS)]
    #[IsGranted('ROLE_EMPLOYE')]
    public function delete(Menu $menu): JsonResponse
    {
        $menu->setActif(false);
        $this->em->flush();
        return $this->json(['message' => 'Menu désactivé.']);
    }

    // GET /api/menus/{id}/prix?nb_personnes=4
    #[OA\Get(path: '/api/menus/{id}/prix', summary: "Calculer le prix pour N personnes",
        parameters: [
            new OA\Parameter(name: 'id',              in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'nombre_personnes', in: 'query', required: true,  schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Prix calculé avec remise éventuelle')])]
    #[Route('/{id}/prix', methods: ['GET'], requirements: self::ID_REQUIREMENTS)]
    public function calculerPrix(Menu $menu, Request $request): JsonResponse
    {
        $nb = max($menu->getNombrePersonneMinimum(), (int) $request->query->get('nb_personnes', 1));
        return $this->json($menu->calculerPrix($nb));
    }

    private function formatMenu(Menu $m): array
    {
        return [
            'id'                      => $m->getId(),
            'titre'                   => $m->getTitre(),
            'description'             => $m->getDescription(),
            'prix_par_personne'       => $m->getPrixParPersonne(),
            'nombre_personne_minimum' => $m->getNombrePersonneMinimum(),
            'quantite_restante'       => $m->getQuantiteRestante(),
            'theme'                   => $m->getTheme()?->getLibelle(),
            'regime'                  => $m->getRegime()?->getLibelle(),
            'image_principale'        => $this->getImagePrincipale($m),
        ];
    }

    private function formatMenuDetail(Menu $m): array
    {
        return array_merge($this->formatMenu($m), [
            'conditions' => $m->getConditions(),
            'images'     => array_map(fn($img) => [
                'id'         => $img->getId(),
                'url'        => $img->getUrl(),
                'alt'        => $img->getAlt(),
                'principale' => $img->isPrincipale(),
            ], $m->getImages()->toArray()),
            'plats' => array_map(fn($p) => [
                'id'          => $p->getId(),
                'type'        => $p->getTypePlat(),
                'nom'         => $p->getNom(),
                'description' => $p->getDescription(),
                'allergenes'  => array_map(fn($a) => $a->getLibelle(), $p->getAllergenes()->toArray()),
            ], $m->getPlats()->toArray()),
        ]);
    }

    private function getImagePrincipale(Menu $m): ?string
    {
        foreach ($m->getImages() as $img) {
            if ($img->isPrincipale()) {
                return $img->getUrl();
            }
        }
        return $m->getImages()->first() ? $m->getImages()->first()->getUrl() : null;
    }

    // POST /api/menus/{id}/plats — associer des plats au menu
    #[Route('/{id}/plats', methods: ['POST'], requirements: self::ID_REQUIREMENTS)]
    #[IsGranted('ROLE_EMPLOYE')]
    public function ajouterPlats(Menu $menu, Request $request): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $platIds = $data['plat_ids'] ?? [];

        // Vider les plats existants
        foreach ($menu->getPlats() as $plat) {
            $menu->removePlat($plat);
        }

        // Ajouter les nouveaux plats
        foreach ($platIds as $platId) {
            $plat = $this->em->getRepository(Plat::class)->find($platId);
            if ($plat) {
                $menu->addPlat($plat);
            }
        }

        $this->em->flush();
        return $this->json(['message' => 'Plats associés au menu.']);
    }

    // POST /api/menus/{id}/images — ajouter une image à la galerie
    #[Route('/{id}/images', methods: ['POST'], requirements: self::ID_REQUIREMENTS)]
    #[IsGranted('ROLE_EMPLOYE')]
    public function ajouterImage(Menu $menu, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $url  = trim($data['url'] ?? '');

        if (!$url) {
            return $this->json(['error' => 'URL manquante.'], 400);
        }

        $img = new MenuImage();
        $img->setUrl($url);
        $img->setAlt(strip_tags($data['alt'] ?? $menu->getTitre()));
        $img->setPrincipale((bool) ($data['principale'] ?? false));
        $img->setMenu($menu);

        $this->em->persist($img);
        $this->em->flush();

        return $this->json([
            'id'         => $img->getId(),
            'url'        => $img->getUrl(),
            'alt'        => $img->getAlt(),
            'principale' => $img->isPrincipale(),
        ], 201);
    }

    // DELETE /api/menus/{menuId}/images/{imgId} — supprimer une image de la galerie
    #[Route('/{menuId}/images/{imgId}', methods: ['DELETE'], requirements: ['menuId' => '\d+', 'imgId' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function supprimerImage(int $menuId, int $imgId): JsonResponse
    {
        $img = $this->em->getRepository(MenuImage::class)->find($imgId);

        if (!$img || $img->getMenu()?->getId() !== $menuId) {
            return $this->json(['error' => 'Image introuvable.'], 404);
        }
        if ($img->isPrincipale()) {
            return $this->json(['error' => 'Impossible de supprimer l\'image principale.'], 400);
        }

        $this->em->remove($img);
        $this->em->flush();

        return $this->json(['message' => 'Image supprimée.']);
    }

    private function hydrateMenu(Menu $menu, array $data): void
    {
        if (isset($data['titre']))                   $menu->setTitre($data['titre']);
        if (isset($data['description']))             $menu->setDescription($data['description']);
        if (isset($data['conditions']))              $menu->setConditions($data['conditions']);
        if (isset($data['prix_par_personne']))       $menu->setPrixParPersonne((float) $data['prix_par_personne']);
        if (isset($data['nombre_personne_minimum'])) $menu->setNombrePersonneMinimum((int) $data['nombre_personne_minimum']);
        if (isset($data['quantite_restante']))       $menu->setQuantiteRestante((int) $data['quantite_restante']);

        // Gestion thème
        if (isset($data['theme']) && $data['theme']) {
            $theme = $this->em->getRepository(Theme::class)->findOneBy(['libelle' => $data['theme']]);
            if (!$theme) {
                $theme = new Theme();
                $theme->setLibelle($data['theme']);
                $this->em->persist($theme);
            }
            $menu->setTheme($theme);
        }

        // Gestion régime
        if (isset($data['regime']) && $data['regime']) {
            $regime = $this->em->getRepository(Regime::class)->findOneBy(['libelle' => $data['regime']]);
            if (!$regime) {
                $regime = new Regime();
                $regime->setLibelle($data['regime']);
                $this->em->persist($regime);
            }
            $menu->setRegime($regime);
        }

        // Gestion image uploadée
        if (!empty($data['image'])) {
            $imagePrincipale = null;
            foreach ($menu->getImages() as $img) {
                if ($img->isPrincipale()) { $imagePrincipale = $img; break; }
            }
            if ($imagePrincipale) {
                $imagePrincipale->setUrl($data['image']);
            } else {
                $newImg = new MenuImage();
                $newImg->setUrl($data['image']);
                $newImg->setAlt($data['titre'] ?? 'Image menu');
                $newImg->setPrincipale(true);
                $newImg->setMenu($menu);
                $this->em->persist($newImg);
            }
        }

        // Actif par défaut à la création
        if (!$menu->getId()) $menu->setActif(true);
    }
}
