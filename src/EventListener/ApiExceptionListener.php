<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Błędy HTTP pod /api/ (np. 404 z routingu, 405) zwracamy jako JSON zamiast strony HTML.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface || !str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $status = $exception->getStatusCode();

        $event->setResponse(new JsonResponse(
            ['error' => Response::$statusTexts[$status] ?? 'Error'],
            $status,
            $exception->getHeaders(),
        ));
    }
}
