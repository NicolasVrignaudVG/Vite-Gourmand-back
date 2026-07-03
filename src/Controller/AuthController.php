<?php

namespace App\Controller;

use App\Entity\RefreshToken;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Authentification')]
#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface          $validator,
        private MailerService               $mailer,
        private JWTTokenManagerInterface    $jwtManager,
        private RateLimiterFactory          $loginLimiter,         // login_limiter dans rate_limiter.yaml
        private RateLimiterFactory          $registerLimiter,
        private RateLimiterFactory          $forgotPasswordLimiter,
    ) {}

    // ── POST /api/auth/register ──────────────────────────────
    #[OA\Post(
        path: '/api/auth/register',
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nom','prenom','email','password'],
            properties: [
                new OA\Property(property: 'nom',        type: 'string',  example: 'Dupont'),
                new OA\Property(property: 'prenom',     type: 'string',  example: 'Marie'),
                new OA\Property(property: 'email',      type: 'string',  format: 'email', example: 'marie@example.fr'),
                new OA\Property(property: 'telephone',  type: 'string',  example: '0612345678'),
                new OA\Property(property: 'adresse',    type: 'string',  example: '12 rue de la Paix, Bordeaux'),
                new OA\Property(property: 'password',   type: 'string',  example: 'MonMotDePasse@1'),
                new OA\Property(property: 'pseudonyme', type: 'string',  example: 'Marie B.'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Compte créé avec succès'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 429, description: 'Trop de tentatives'),
        ]
    )]
    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $limiter = $this->registerLimiter->create((string) ($request->getClientIp() ?? 'unknown'));
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

        $data     = json_decode($request->getContent(), true) ?? [];
        $password = $data['password'] ?? '';

        // ── Validation mot de passe (10 car., maj, min, chiffre, spécial) ──
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $password)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.'], 400);
        }

        // ── Vérification e-mail unique ───────────────────────
        $existing = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => strtolower(trim($data['email'] ?? ''))]);
        if ($existing) {
            return $this->json(['error' => 'Cette adresse e-mail est déjà utilisée.'], 409);
        }

        $role = $this->em->getRepository(Role::class)->findOneBy(['libelle' => 'utilisateur']);
        $user = new Utilisateur();

        // ── Sanitisation — strip_tags() sur tous les champs texte ──
        $user->setEmail(strtolower(trim(strip_tags($data['email'] ?? ''))));
        $user->setNom(strip_tags(trim($data['nom'] ?? '')));
        $user->setPrenom(strip_tags(trim($data['prenom'] ?? '')));
        $user->setTelephone(preg_replace('/[^0-9+]/', '', $data['telephone'] ?? ''));
        $user->setAdresse(strip_tags(trim($data['adresse'] ?? '')));
        // Pseudonyme public (optionnel) — affiché sur les avis à la place du nom réel
        $pseudo = strip_tags(trim($data['pseudonyme'] ?? ''));
        $user->setPseudonyme($pseudo ?: null);
        $user->setRole($role);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        // ── Validation entité Symfony (annotations @Assert sur l'entité) ──
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $this->em->persist($user);
        $this->em->flush();
        $this->mailer->sendBienvenue($user);

        return $this->json(['message' => 'Compte créé avec succès. Un e-mail de bienvenue vous a été envoyé.', 'id' => $user->getId()], 201);
    }

    // ── GET /api/auth/me ─────────────────────────────────────
    #[OA\Get(
        path: '/api/auth/me',
        summary: "Recuperer le profil de l'utilisateur connecte",
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié.'], 401);

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
    #[OA\Post(
        path: '/api/auth/forgot-password',
        summary: 'Demande de réinitialisation de mot de passe',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email'],
            properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
        )),
        responses: [
            new OA\Response(response: 200, description: 'E-mail envoyé si le compte existe'),
            new OA\Response(response: 429, description: 'Trop de tentatives'),
        ]
    )]
    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $limiter = $this->forgotPasswordLimiter->create((string) ($request->getClientIp() ?? 'unknown'));
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $email = strtolower(trim(strip_tags($data['email'] ?? '')));
        if (!$email) return $this->json(['error' => "L'adresse e-mail est requise."], 400);

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if ($user) {
            $token  = bin2hex(random_bytes(32));
            $expire = new \DateTime('+1 hour');
            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expire);
            $this->em->flush();
            $this->mailer->sendResetPassword($user, $token);
        }

        // Réponse identique que l'e-mail existe ou non (anti-énumération)
        return $this->json(['message' => 'Si cet e-mail existe dans notre base, un lien de réinitialisation a été envoyé.']);
    }

    // ── POST /api/auth/reset-password ────────────────────────
    #[OA\Post(
        path: '/api/auth/reset-password',
        summary: 'Réinitialiser le mot de passe avec un token',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['token','password'],
            properties: [
                new OA\Property(property: 'token',    type: 'string'),
                new OA\Property(property: 'password', type: 'string', example: 'NouveauMotDePasse@1'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Mot de passe réinitialisé'),
            new OA\Response(response: 400, description: 'Token invalide ou expiré'),
        ]
    )]
    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data        = json_decode($request->getContent(), true) ?? [];
        $token       = trim(strip_tags($data['token'] ?? ''));
        $newPassword = $data['password'] ?? '';

        if (!$token || !$newPassword) return $this->json(['error' => 'Token et mot de passe requis.'], 400);

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/', $newPassword)) {
            return $this->json(['error' => 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.'], 400);
        }

        $user = $this->em->getRepository(Utilisateur::class)->findOneBy(['resetToken' => $token]);
        if (!$user) return $this->json(['error' => 'Lien de réinitialisation invalide.'], 400);
        if ($user->getResetTokenExpiresAt() < new \DateTime()) return $this->json(['error' => 'Ce lien a expiré. Veuillez en demander un nouveau.'], 400);

        $user->setPassword($this->hasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    // ── DELETE /api/auth/me — supprimer son compte (RGPD) ───
    #[OA\Delete(
        path: '/api/auth/me',
        summary: 'Supprimer son compte (RGPD)',
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Compte supprimé'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/me', methods: ['DELETE'])]
    public function deleteMe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié.'], 401);

        // Révoquer le refresh token
        $refreshTokenValue = $request->cookies->get('refresh_token');
        if ($refreshTokenValue) {
            $refreshToken = $this->em->getRepository(RefreshToken::class)
                ->findOneBy(['token' => $refreshTokenValue]);
            if ($refreshToken) $this->em->remove($refreshToken);
        }

        // Supprimer les avis liés (contrainte nullable:false sur utilisateur_id)
        // Alternative RGPD : modifier la contrainte en nullable:true + anonymisation
        foreach ($user->getAvis() as $avis) {
            $this->em->remove($avis);
        }

        $this->em->remove($user);
        $this->em->flush();

        // Supprimer les cookies
        $response = $this->json(['message' => 'Votre compte a été supprimé.']);
        foreach (['jwt_token' => '/', 'refresh_token' => '/api/auth'] as $name => $path) {
            $response->headers->setCookie(Cookie::create($name)->withValue('')
                ->withExpires(time() - 3600)->withPath($path)
                ->withSecure(true)->withHttpOnly(true)->withSameSite('Lax'));
        }
        return $response;
    }

    // ── PUT /api/auth/me ─────────────────────────────────────
    #[OA\Put(
        path: '/api/auth/me',
        summary: 'Modifier son profil',
        security: [['cookieAuth' => []]],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nom',       type: 'string'),
                new OA\Property(property: 'prenom',    type: 'string'),
                new OA\Property(property: 'telephone', type: 'string'),
                new OA\Property(property: 'adresse',   type: 'string'),
                new OA\Property(property: 'password',  type: 'string', description: 'Laisser vide pour ne pas changer'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Profil mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 401, description: 'Non authentifié'),
        ]
    )]
    #[Route('/me', methods: ['PUT'])]
    public function updateMe(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié.'], 401);

        $data = json_decode($request->getContent(), true) ?? [];

        // ── Sanitisation — strip_tags() sur tous les champs modifiables ──
        if (isset($data['nom']))        $user->setNom(strip_tags(trim($data['nom'])));
        if (isset($data['prenom']))     $user->setPrenom(strip_tags(trim($data['prenom'])));
        if (isset($data['telephone']))  $user->setTelephone(preg_replace('/[^0-9+]/', '', $data['telephone']));
        if (isset($data['adresse']))    $user->setAdresse(strip_tags(trim($data['adresse'])));
        if (isset($data['pseudonyme'])) $user->setPseudonyme(strip_tags(trim($data['pseudonyme'])) ?: null);

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

    // ── POST /api/auth/refresh ───────────────────────────────
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Renouveler le JWT via le refresh token (cookie)',
        responses: [
            new OA\Response(response: 200, description: 'Nouveaux cookies JWT et refresh posés'),
            new OA\Response(response: 401, description: 'Refresh token invalide ou expiré'),
        ]
    )]
    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshTokenValue = $request->cookies->get('refresh_token');
        if (!$refreshTokenValue) {
            return $this->json(['error' => 'Refresh token manquant.'], 401);
        }

        $refreshToken = $this->em->getRepository(RefreshToken::class)
            ->findOneBy(['token' => $refreshTokenValue]);

        if (!$refreshToken || !$refreshToken->isValid()) {
            return $this->json(['error' => 'Session expirée. Veuillez vous reconnecter.'], 401);
        }

        // Rotation — révoquer l'ancien, créer un nouveau
        $refreshToken->revoke();
        $user = $refreshToken->getUtilisateur();

        $newRefreshToken = new RefreshToken($user, $request->getClientIp());
        $this->em->persist($newRefreshToken);
        $this->em->flush();

        $jwtToken = $this->jwtManager->create($user);

        $jwtCookie = Cookie::create('jwt_token')
            ->withValue($jwtToken)->withExpires(time() + 3600)
            ->withPath('/')->withSecure(true)->withHttpOnly(true)->withSameSite('Lax');

        $refreshCookie = Cookie::create('refresh_token')
            ->withValue($newRefreshToken->getToken())->withExpires(time() + 30 * 24 * 3600)
            ->withPath('/api/auth')->withSecure(true)->withHttpOnly(true)->withSameSite('Lax');

        $response = new JsonResponse(['message' => 'Token renouvelé.', 'user' => [
            'id'        => $user->getId(),
            'email'     => $user->getUserIdentifier(),
            'nom'       => $user->getNom(),
            'prenom'    => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'role'      => $user->getRole()?->getLibelle(),
            'roles'     => $user->getRoles(),
        ]]);
        $response->headers->setCookie($jwtCookie);
        $response->headers->setCookie($refreshCookie);
        return $response;
    }

    // ── POST /api/auth/logout ────────────────────────────────
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Déconnexion — suppression des cookies JWT',
        security: [['cookieAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Déconnecté avec succès'),
        ]
    )]
    #[Route('/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        // Révoquer le refresh token en base
        $refreshTokenValue = $request->cookies->get('refresh_token');
        if ($refreshTokenValue) {
            $refreshToken = $this->em->getRepository(RefreshToken::class)
                ->findOneBy(['token' => $refreshTokenValue]);
            if ($refreshToken) { $refreshToken->revoke(); $this->em->flush(); }
        }

        $response = $this->json(['message' => 'Déconnexion réussie.']);

        // Supprimer les deux cookies côté client
        foreach (['jwt_token' => '/', 'refresh_token' => '/api/auth'] as $name => $path) {
            $response->headers->setCookie(Cookie::create($name)->withValue('')
                ->withExpires(time() - 3600)->withPath($path)
                ->withSecure(true)->withHttpOnly(true)->withSameSite('Lax'));
        }

        return $response;
    }
}
