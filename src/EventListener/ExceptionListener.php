<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class ExceptionListener
{
    public function __construct(
        private string $environment,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception  = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        // En production : message générique pour les erreurs serveur, afin de
        // ne pas exposer d'informations internes (chemins, structure du code).
        // En dev : détails complets pour faciliter le débogage.
        if ($this->environment === 'dev') {
            $payload = [
                'error' => $exception->getMessage(),
                'code'  => $statusCode,
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
            ];
        } else {
            $payload = [
                'error' => $statusCode >= 500
                    ? 'Une erreur interne est survenue.'
                    : $exception->getMessage(),
                'code'  => $statusCode,
            ];
        }

        $response = new JsonResponse($payload, $statusCode);
        $event->setResponse($response);
    }
}
