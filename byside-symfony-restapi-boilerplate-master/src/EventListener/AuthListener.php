<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthListener implements EventSubscriberInterface
{
    private ?LoggerInterface $logger = null;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // the priority must be greater than the Security HTTP
            // ExceptionListener, to make sure it's called before
            // the default exception listener
            KernelEvents::CONTROLLER => ['onKernelController', 32],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        // All route parameters including the `_controller`
        $ignoreListener = $request->attributes->get('ignore_listener');

        // Delete this false ;)
        if (false && (empty($ignoreListener) || $ignoreListener !== self::class)) {
            $token = $request->headers->get('Authorization');
            if ($token === null || $token === '' || $token === '0') {
                throw new AccessDeniedHttpException('You cannot access this page!');
            }
        }

        $this->logger->debug('I just got a request: ' . $event->getRequest()->getRequestUri());
    }
}
