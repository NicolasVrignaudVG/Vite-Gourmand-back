<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Entity\Allergene;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/plats')]
#[IsGranted('ROLE_EMPLOYE')]
class PlatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    private function formatPlat(Plat $p): array
    {
        return [
            'id'          => $p->getId(),
            'nom'         => $p->getNom(),
            'typePlat'    => $p->getTypePlat(),
            'description' => $p->getDescription(),
            'allergenes'  => array_map(
                fn($a) => ['id' => $a->getId(), 'libelle' => $a->getLibelle()],
                $p->getAllergenes()->toArray()
            ),
        ];
    }

    // GET /api/plats
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $plats = $this->em->getRepository(Plat::class)->findAll();
        return $this->json(array_map(fn($p) => $this->formatPlat($p), $plats));
    }

    // POST /api/plats
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom']) || empty($data['type'])) {
            return $this->json(['error' => 'Nom et type sont obligatoires.'], 400);
        }
        if (!in_array($data['type'], ['entree', 'plat', 'dessert'])) {
            return $this->json(['error' => 'Type invalide.'], 400);
        }

        $plat = new Plat();
        $plat->setNom($data['nom']);
        $plat->setTypePlat($data['type']);
        $plat->setDescription($data['description'] ?? null);

        foreach ($data['allergenes'] ?? [] as $libelle) {
            $allergene = $this->em->getRepository(Allergene::class)->findOneBy(['libelle' => $libelle]);
            if (!$allergene) {
                $allergene = new Allergene();
                $allergene->setLibelle($libelle);
                $this->em->persist($allergene);
            }
            $plat->addAllergene($allergene);
        }

        $this->em->persist($plat);
        $this->em->flush();

        return $this->json($this->formatPlat($plat), 201);
    }

    // PUT /api/plats/{id}
    #[Route('/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Plat $plat, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['nom']))         $plat->setNom($data['nom']);
        if (isset($data['type']))        $plat->setTypePlat($data['type']);
        if (isset($data['description'])) $plat->setDescription($data['description']);

        if (isset($data['allergenes'])) {
            foreach ($plat->getAllergenes() as $a) {
                $plat->removeAllergene($a);
            }
            foreach ($data['allergenes'] as $libelle) {
                $allergene = $this->em->getRepository(Allergene::class)->findOneBy(['libelle' => $libelle]);
                if (!$allergene) {
                    $allergene = new Allergene();
                    $allergene->setLibelle($libelle);
                    $this->em->persist($allergene);
                }
                $plat->addAllergene($allergene);
            }
        }

        $this->em->flush();
        return $this->json($this->formatPlat($plat));
    }

    // DELETE /api/plats/{id}
    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Plat $plat): JsonResponse
    {
        $this->em->remove($plat);
        $this->em->flush();
        return $this->json(['message' => 'Plat supprimé.']);
    }
}
