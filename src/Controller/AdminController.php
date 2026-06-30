<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\MailerService;
use App\Service\MongoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UtilisateurRepository       $userRepo,
        private MailerService               $mailer,
        private UserPasswordHasherInterface $hasher,
        private MongoService                $mongo,
    ) {}

    // GET /api/admin/employes — liste des employés
    #[Route('/employes', methods: ['GET'])]
    public function listEmployes(): JsonResponse
    {
        $role     = $this->em->getRepository(Role::class)->findOneBy(['libelle' => 'employe']);
        $employes = $this->userRepo->findBy(['role' => $role]);

        return $this->json(array_map(fn($e) => [
            'id'     => $e->getId(),
            'nom'    => $e->getNom(),
            'prenom' => $e->getPrenom(),
            'email'  => $e->getEmail(),
            'actif'  => $e->isActif(),
        ], $employes));
    }

    // POST /api/admin/employes — créer un compte employé
    #[Route('/employes', methods: ['POST'])]
    public function createEmploye(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = trim($data['email'] ?? '');
        $mdp   = $data['password'] ?? '';

        if (!$email || !$mdp) {
            return $this->json(['error' => 'Email et mot de passe requis.'], 400);
        }
        if ($this->userRepo->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Cet email est déjà utilisé.'], 409);
        }

        $role = $this->em->getRepository(Role::class)->findOneBy(['libelle' => 'employe']);

        $employe = new Utilisateur();
        $employe->setEmail($email);
        $employe->setNom($data['nom'] ?? '');
        $employe->setPrenom($data['prenom'] ?? '');
        $employe->setRole($role);
        $employe->setPassword($this->hasher->hashPassword($employe, $mdp));

        $this->em->persist($employe);
        $this->em->flush();

        // Notification sans mot de passe
        $this->mailer->sendNouvelEmploye($employe);

        return $this->json(['message' => 'Compte employé créé. Notification envoyée.', 'id' => $employe->getId()], 201);
    }

    // PATCH /api/admin/employes/{id}/toggle — activer/désactiver
    // Résolution explicite : {id} -> Utilisateur::find($id) via #[MapEntity(id: 'id')],
    // indépendamment du nom de la variable PHP ($employe). Plus robuste qu'une résolution
    // implicite face à un refactoring (renommage de variable, ajout d'un paramètre de route).
    #[Route('/employes/{id}/toggle', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function toggleEmploye(#[MapEntity(id: 'id')] Utilisateur $employe): JsonResponse
    {
        $employe->setActif(!$employe->isActif());
        $this->em->flush();

        return $this->json([
            'message' => $employe->isActif() ? 'Compte réactivé.' : 'Compte désactivé.',
            'actif'   => $employe->isActif(),
        ]);
    }

    // GET /api/admin/stats — statistiques commandes (MongoDB)
    #[Route('/stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $filters = [];

        if ($menuId = $request->query->get('menu_id')) {
            $filters['menu_id'] = (int) $menuId;
        }
        if ($dateDebut = $request->query->get('date_debut')) {
            $filters['date_debut'] = new \DateTime($dateDebut);
        }
        if ($dateFin = $request->query->get('date_fin')) {
            $filters['date_fin'] = new \DateTime($dateFin);
        }

        return $this->json($this->mongo->getStats($filters));
    }
}
