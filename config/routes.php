<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\HealthController;
use WhatsApp\Adapter\Http\Controllers\MessageController;
use WhatsApp\Adapter\Http\Controllers\MetricsController;
use WhatsApp\Adapter\Http\Controllers\NumberValidationController;
use WhatsApp\Adapter\Http\Controllers\RcsController;
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

    // WhatsApp number validation endpoints
    $router->addRoute('GET', '/api/whatsapp/check-number', function ($request) use ($container) {
        $controller = $container[NumberValidationController::class];
        return $controller->checkNumber($request);
    });

    $router->addRoute('POST', '/api/whatsapp/check-numbers', function ($request) use ($container) {
        $controller = $container[NumberValidationController::class];
        return $controller->checkNumbers($request);
    });

    // RCS endpoints
    $router->addRoute('POST', '/api/rcs/text', function ($request) use ($container) {
        $controller = $container[RcsController::class];
        return $controller->sendText($request);
    });

    $router->addRoute('POST', '/api/rcs/file', function ($request) use ($container) {
        $controller = $container[RcsController::class];
        return $controller->sendFile($request);
    });

    $router->addRoute('POST', '/api/rcs/card', function ($request) use ($container) {
        $controller = $container[RcsController::class];
        return $controller->sendCard($request);
    });

    $router->addRoute('POST', '/api/rcs/carousel', function ($request) use ($container) {
        $controller = $container[RcsController::class];
        return $controller->sendCarousel($request);
    });

    $router->addRoute('POST', '/api/rcs/suggestions', function ($request) use ($container) {
        $controller = $container[RcsController::class];
        return $controller->sendWithSuggestions($request);
    });

    // Webhook endpoints
    
    // Meta webhook (Instagram + Messenger) - supports both GET and POST
    $router->addRoute('GET', '/webhooks/meta', function ($request) use ($container) {
        $controller = $container[WebhookController::class];
        return $controller->handleMetaWebhook($request);
    });

    $router->addRoute('POST', '/webhooks/meta', function ($request) use ($container) {
        $controller = $container[WebhookController::class];
        return $controller->handleMetaWebhook($request);
    });

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

    // Metrics endpoints
    $router->addRoute('GET', '/metrics/meta', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getSummary($request);
    });

    $router->addRoute('GET', '/metrics/meta/success-rate', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getSuccessRate($request);
    });

    $router->addRoute('GET', '/metrics/meta/response-time', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getResponseTime($request);
    });

    $router->addRoute('GET', '/metrics/meta/errors', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getErrors($request);
    });

    $router->addRoute('GET', '/metrics/meta/webhooks', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getWebhooks($request);
    });

    $router->addRoute('GET', '/metrics/meta/messaging-window-errors', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getMessagingWindowErrors($request);
    });

    $router->addRoute('GET', '/metrics/meta/alerts', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getAlerts($request);
    });

    $router->addRoute('GET', '/metrics/meta/circuit-breaker', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getCircuitBreakerStatus($request);
    });

    $router->addRoute('GET', '/metrics/meta/rate-limit', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getRateLimitStatus($request);
    });

    $router->addRoute('GET', '/metrics/meta/health', function ($request) use ($container) {
        $controller = $container[MetricsController::class];
        return $controller->getHealthCheck($request);
    });
};
