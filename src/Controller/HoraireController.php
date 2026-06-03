<?php

namespace App\Controller;

use App\Entity\Horaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/horaires')]
class HoraireController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    // GET /api/horaires — public
    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $horaires = $this->em->getRepository(Horaire::class)->findBy([], ['jour' => 'ASC', 'service' => 'ASC']);
        return $this->json(array_map(fn($h) => [
            'id'             => $h->getId(),
            'jour'           => $h->getJour(),
            'heureOuverture' => $h->getHeureOuverture(),
            'heureFermeture' => $h->getHeureFermeture(),
            'service'        => $h->getService(),
            'ferme'          => $h->isFerme(),
        ], $horaires));
    }

    // PUT /api/horaires — employé met à jour les horaires
    #[Route('', methods: ['PUT'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $item) {
            $horaire = $this->em->getRepository(Horaire::class)->find($item['id']);
            if (!$horaire) continue;
            if (isset($item['heureOuverture'])) $horaire->setHeureOuverture($item['heureOuverture']);
            if (isset($item['heureFermeture'])) $horaire->setHeureFermeture($item['heureFermeture']);
            if (isset($item['ferme']))          $horaire->setFerme((bool) $item['ferme']);
        }

        $this->em->flush();
        return $this->json(['message' => 'Horaires mis à jour.']);
    }
}
