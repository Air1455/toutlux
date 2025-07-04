<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class JWTDecodedListener
{
    public function __construct(
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    /**
     * Valider et traiter le JWT décodé
     */
    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        // Vérifier l'expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            $event->markAsInvalid();
            $this->logger->warning('JWT token expired', [
                'exp' => $payload['exp'] ?? null,
                'current_time' => time()
            ]);
            return;
        }

        // Vérifier l'IP si configuré (optionnel)
        if (isset($payload['ip']) && $payload['ip'] !== $request->getClientIp()) {
            $this->logger->warning('JWT IP mismatch', [
                'token_ip' => $payload['ip'],
                'request_ip' => $request->getClientIp()
            ]);
            // On peut décider de marquer comme invalide ou juste logger
            // $event->markAsInvalid();
        }

        // Logger l'utilisation du token
        $this->logger->info('JWT decoded successfully', [
            'user_id' => $payload['id'] ?? null,
            'username' => $payload['username'] ?? null,
            'ip' => $request->getClientIp()
        ]);
    }
}
