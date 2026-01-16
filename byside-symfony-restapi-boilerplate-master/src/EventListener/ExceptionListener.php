<?php

namespace App\EventListener;

use App\Response\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 2],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

        // Customize your response object to display the exception details
        $statusCode = ($exception instanceof HttpExceptionInterface) ?
            $exception->getStatusCode() :
            ($exception->getCode() ?: JsonResponse::HTTP_INTERNAL_SERVER_ERROR)
        ;

        $errors = [
            'code' => $statusCode,
            'message' => $exception->getMessage(),
        ];

        if ($statusCode === JsonResponse::HTTP_INTERNAL_SERVER_ERROR) {
            $errors['trace'] = $exception->getTraceAsString();
        }

        $response = new ApiResponse(false, null, $errors, $statusCode);

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
