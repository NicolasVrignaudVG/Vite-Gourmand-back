<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtCookieSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user      = $token->getUser();
        $jwtToken  = $this->jwtManager->create($user);

        $cookie = Cookie::create('jwt_token')
            ->withValue($jwtToken)
            ->withExpires(time() + 3600)
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('None'); // None requis pour cross-origin Vercel→Render

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

        $response->headers->setCookie($cookie);
        return $response;
    }
}
