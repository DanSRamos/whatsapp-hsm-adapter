<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\HealthController;
use WhatsApp\Adapter\Http\Controllers\MessageController;
use WhatsApp\Adapter\Http\Controllers\TemplateController;
use WhatsApp\Adapter\Http\Controllers\WebhookController;
use WhatsApp\Adapter\Http\RouterInterface;

/**
 * Register all application routes
 * 
 * @param RouterInterface $router
 * @param array $container Dependency injection container
 */
return function (RouterInterface $router, array $container): void {
    // Health check endpoint
    $router->addRoute('GET', '/health', function ($request) use ($container) {
        $controller = $container[HealthController::class];
        return $controller->check($request);
    });

    // Template endpoints
    $router->addRoute('GET', '/api/templates', function ($request) use ($container) {
        $controller = $container[TemplateController::class];
        return $controller->getTemplates($request);
    });

    $router->addRoute('GET', '/api/templates/{templateId}', function ($request) use ($container) {
        $controller = $container[TemplateController::class];
        $params = $request->getAttribute('routeParams', []);
        return $controller->getTemplate($request, $params['templateId'] ?? '');
    });

    $router->addRoute('POST', '/api/templates/sync', function ($request) use ($container) {
        $controller = $container[TemplateController::class];
        return $controller->syncTemplates($request);
    });

    // Message endpoints
    $router->addRoute('POST', '/api/messages/hsm', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        return $controller->sendHSM($request);
    });

    $router->addRoute('POST', '/api/messages/text', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        return $controller->sendText($request);
    });

    $router->addRoute('POST', '/api/messages/media', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        return $controller->sendMedia($request);
    });

    $router->addRoute('POST', '/api/messages/interactive/buttons', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        return $controller->sendInteractiveButtons($request);
    });

    $router->addRoute('POST', '/api/messages/interactive/list', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        return $controller->sendInteractiveList($request);
    });

    $router->addRoute('GET', '/api/messages/{messageId}/status', function ($request) use ($container) {
        $controller = $container[MessageController::class];
        $params = $request->getAttribute('routeParams', []);
        return $controller->getMessageStatus($request, $params['messageId'] ?? '');
    });

    // Webhook endpoints
    $router->addRoute('POST', '/webhooks/delivery-reports', function ($request) use ($container) {
        $controller = $container[WebhookController::class];
        return $controller->handleDeliveryReport($request);
    });

    $router->addRoute('POST', '/webhooks/incoming-messages', function ($request) use ($container) {
        $controller = $container[WebhookController::class];
        return $controller->handleIncomingMessage($request);
    });

    $router->addRoute('POST', '/webhooks/template-updates', function ($request) use ($container) {
        $controller = $container[WebhookController::class];
        return $controller->handleTemplateUpdate($request);
    });
};
