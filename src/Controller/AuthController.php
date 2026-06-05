<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface          $validator,
        private MailerService               $mailer,
        private RateLimiterFactory          $registerLimiter,
        private RateLimiterFactory          $forgotPasswordLimiter,
    ) {}

    // ── POST /api/auth/register ──────────────────────────────
    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Rate limiting : 3 inscriptions par IP par 10 minutes
        $limiter = $this->registerLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

        $data = json_decode($request->getContent(), true);

        // Validation mot de passe (10 car. min, maj, min, chiffre, spécial)
        $password = $data['password'] ?? '';
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
            return $this->json([
                'error' => 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.'
            ], 400);
        }

        // Vérification email existant
        $existing = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email'] ?? '']);
        if ($existing) {
            return $this->json(['error' => 'Cette adresse e-mail est déjà utilisée.'], 409);
        }

        $role = $this->em->getRepository(Role::class)->findOneBy(['libelle' => 'utilisateur']);

        $user = new Utilisateur();
        $user->setEmail($data['email'] ?? '');
        $user->setNom($data['nom'] ?? '');
        $user->setPrenom($data['prenom'] ?? '');
        $user->setTelephone($data['telephone'] ?? null);
        $user->setAdresse($data['adresse'] ?? null);
        $user->setRole($role);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $this->em->persist($user);
        $this->em->flush();

        // Mail de bienvenue
        $this->mailer->sendBienvenue($user);

        return $this->json([
            'message' => 'Compte créé avec succès. Un e-mail de bienvenue vous a été envoyé.',
            'id'      => $user->getId(),
        ], 201);
    }

    // ── GET /api/auth/me ─────────────────────────────────────
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getUserIdentifier(),
            'nom'       => $user->getNom(),
            'prenom'    => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'adresse'   => $user->getAdresse(),
            'role'      => $user->getRole()?->getLibelle(),
            'roles'     => $user->getRoles(),
        ]);
    }

    // ── POST /api/auth/forgot-password ───────────────────────
    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        // Rate limiting : 3 demandes par IP par 15 minutes
        $limiter = $this->forgotPasswordLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

        $data  = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!$email) {
            return $this->json(['error' => 'L\'adresse e-mail est requise.'], 400);
        }

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if ($user) {
            $token  = bin2hex(random_bytes(32));
            $expire = new \DateTime('+1 hour');
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expire);
            $this->em->flush();
            $this->mailer->sendResetPassword($user, $token);
        }

        return $this->json([
            'message' => 'Si cet e-mail existe dans notre base, un lien de réinitialisation a été envoyé.'
        ]);
    }

    // ── POST /api/auth/reset-password ────────────────────────
    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data        = json_decode($request->getContent(), true);
        $token       = trim($data['token'] ?? '');
        $newPassword = $data['password'] ?? '';

        if (!$token || !$newPassword) {
            return $this->json(['error' => 'Token et mot de passe requis.'], 400);
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $newPassword)) {
            return $this->json([
                'error' => 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.'
            ], 400);
        }

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Lien de réinitialisation invalide.'], 400);
        }

        if ($user->getResetTokenExpiresAt() < new \DateTime()) {
            return $this->json(['error' => 'Ce lien a expiré. Veuillez en demander un nouveau.'], 400);
        }

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    // ── PUT /api/auth/me ─────────────────────────────────────
    #[Route('/me', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom']))       $user->setNom($data['nom']);
        if (isset($data['prenom']))    $user->setPrenom($data['prenom']);
        if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
        if (isset($data['adresse']))   $user->setAdresse($data['adresse']);

        if (!empty($data['password'])) {
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $data['password'])) {
                return $this->json(['error' => 'Mot de passe invalide.'], 400);
            }
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
        }

        $this->em->flush();

        return $this->json([
            'message'   => 'Profil mis à jour.',
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'nom'       => $user->getNom(),
            'prenom'    => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'adresse'   => $user->getAdresse(),
        ]);
    }

    // ── POST /api/auth/logout ────────────────────────────────
    #[Route('/logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json(['message' => 'Déconnexion réussie.']);

        $cookie = Cookie::create('jwt_token')
            ->withValue('')
            ->withExpires(time() - 3600)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('None');

        $response->headers->setCookie($cookie);
        return $response;
    }
}
