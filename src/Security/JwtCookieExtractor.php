<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

class JwtCookieExtractor implements TokenExtractorInterface
{
    public function extract(Request $request): string|false
    {
        // Chercher d'abord dans le cookie HttpOnly
        $cookie = $request->cookies->get('jwt_token');
        if ($cookie) {
            return $cookie;
        }

        // Fallback : Authorization header (pour compatibilité)
        $header = $request->headers->get('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return false;
    }
}
