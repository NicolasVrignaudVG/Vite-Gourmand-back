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
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Appliquer uniquement aux routes API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        // Déterminer le code HTTP
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        // Message d'erreur selon l'environnement
        $message = $statusCode === 500
            ? 'Une erreur interne est survenue. Veuillez réessayer.'
            : $exception->getMessage();

        $response = new JsonResponse([
            'error'  => $message,
            'code'   => $statusCode,
        ], $statusCode);

        // Ajouter les headers CORS
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $event->setResponse($response);
    }
}
