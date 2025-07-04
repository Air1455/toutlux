<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Log l'exception
        $this->logger->error('Exception occurred', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'url' => $request->getUri(),
            'method' => $request->getMethod()
        ]);

        // Si ce n'est pas une requête API, laisser Symfony gérer
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Créer la réponse JSON
        $response = $this->createApiResponse($exception);
        $event->setResponse($response);
    }

    private function createApiResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Une erreur interne s\'est produite';
        $data = [
            'error' => true,
            'message' => $message
        ];

        // Gérer les différents types d'exceptions
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? $message;
            $data['message'] = $message;

            // Ajouter les headers de l'exception
            foreach ($exception->getHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }
        } elseif ($exception instanceof AccessDeniedException) {
            $statusCode = Response::HTTP_FORBIDDEN;
            $data['message'] = 'Accès refusé';
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $data['message'] = 'Authentification requise';
        }

        // En mode dev, ajouter plus d'informations
        if ($this->environment === 'dev') {
            $data['exception'] = [
                'message' => $exception->getMessage(),
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        // Ajouter un code d'erreur unique pour le tracking
        $data['code'] = $this->generateErrorCode($exception);

        return new JsonResponse($data, $statusCode);
    }

    private function generateErrorCode(\Throwable $exception): string
    {
        return substr(
            md5(
                $exception->getMessage() .
                $exception->getFile() .
                $exception->getLine()
            ),
            0,
            8
        );
    }
}
