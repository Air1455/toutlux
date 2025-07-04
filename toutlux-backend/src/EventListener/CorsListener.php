<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class CorsListener
{
    private array $allowedOrigins;

    public function __construct(string $corsAllowOrigin)
    {
        // Parse allowed origins from environment variable
        $this->allowedOrigins = array_map('trim', explode(',', $corsAllowOrigin));
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Don't do anything if it's not the main request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getRealMethod();

        // Handle preflight requests
        if ('OPTIONS' === $method) {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Max-Age', '3600');

            $this->setAccessControlHeaders($request, $response);

            $event->setResponse($response);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Don't do anything if it's not the main request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Add CORS headers to all responses
        $this->setAccessControlHeaders($request, $response);
    }

    private function setAccessControlHeaders($request, $response): void
    {
        $origin = $request->headers->get('Origin');

        // Check if origin is allowed
        if ($origin && $this->isOriginAllowed($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } elseif (in_array('*', $this->allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        // Expose custom headers if needed
        $response->headers->set('Access-Control-Expose-Headers', 'Link, X-Total-Count');
    }

    private function isOriginAllowed(string $origin): bool
    {
        foreach ($this->allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') {
                return true;
            }

            // Check if it's a regex pattern
            if (preg_match('/^\/.*\/$/', $allowedOrigin)) {
                if (preg_match($allowedOrigin, $origin)) {
                    return true;
                }
            } elseif ($allowedOrigin === $origin) {
                return true;
            }
        }

        return false;
    }
}
