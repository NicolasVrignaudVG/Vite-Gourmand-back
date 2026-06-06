<?php
// src/EventSubscriber/LoginRateLimiterSubscriber.php
// ─────────────────────────────────────────────────────────────────
// Intercepte POST /api/auth/login AVANT que Symfony vérifie
// le mot de passe, pour bloquer le brute-force.
// Utilise le limiter "login_limiter" défini dans rate_limiter.yaml.
// ─────────────────────────────────────────────────────────────────

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class LoginRateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $loginLimiter,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 256 : s'exécute avant le firewall Symfony (priorité 8)
            KernelEvents::REQUEST => ['onKernelRequest', 256],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // N'agit que sur la route de login, en POST
        if ($request->getPathInfo() !== '/api/auth/login') {
            return;
        }
        if ($request->getMethod() !== 'POST') {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';

        // ── Rate limit par IP ──────────────────────────────
        $limiter = $this->loginLimiter->create('login_' . $ip);
        $limit   = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $event->setResponse(new JsonResponse(
                ['error' => 'Trop de tentatives de connexion. Réessayez dans 1 minute.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $retryAfter->getTimestamp() - time()]
            ));
        }
    }
}
