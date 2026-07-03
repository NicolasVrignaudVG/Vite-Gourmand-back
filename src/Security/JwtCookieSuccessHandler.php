<?php

namespace App\Security;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JwtCookieSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface   $em,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user     = $token->getUser();
        $jwtToken = $this->jwtManager->create($user);

        // Créer un refresh token sécurisé
        $refreshToken = new RefreshToken($user, $request->getClientIp());
        $this->em->persist($refreshToken);
        $this->em->flush();

        // Cookie JWT — 1h, HttpOnly, Secure
        $jwtCookie = Cookie::create('jwt_token')
            ->withValue($jwtToken)
            ->withExpires(time() + 3600)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('None');

        // Cookie Refresh — 30j, HttpOnly, Secure, chemin restreint /api/auth
        $refreshCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken->getToken())
            ->withExpires(time() + 30 * 24 * 3600)
            ->withPath('/api/auth')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('None');

        $response = new JsonResponse([
            'message' => 'Connexion réussie.',
            'user'    => [
                'id'        => $user->getId(),
                'email'     => $user->getUserIdentifier(),
                'nom'       => $user->getNom(),
                'prenom'    => $user->getPrenom(),
                'telephone' => $user->getTelephone(),
                'role'      => $user->getRole()?->getLibelle(),
                'roles'     => $user->getRoles(),
            ],
        ]);

        $response->headers->setCookie($jwtCookie);
        $response->headers->setCookie($refreshCookie);
        return $response;
    }
}
