<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Services\TemplateService;

/**
 * Controller for template management endpoints
 */
class TemplateController
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * GET /api/templates
     * Recupera todos os templates disponíveis
     */
    public function getTemplates(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('GET /api/templates - Fetching all templates');

            // Obtém query params
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Busca templates
            $templates = $this->templateService->getAllTemplates($providerName);

            // Converte templates para array
            $templatesData = array_map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'language' => $template->language,
                    'status' => $template->status,
                    'category' => $template->category,
                    'components' => $template->components,
                    'rejection_reason' => $template->rejectionReason,
                    'is_approved' => $template->isApproved(),
                    'parameters' => $template->getParameters()
                ];
            }, $templates);

            $this->logger->info('Templates fetched successfully', [
                'count' => count($templates),
                'provider' => $providerName
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'templates' => $templatesData,
                    'count' => count($templatesData)
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch templates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_TEMPLATES_ERROR',
                    'message' => 'Failed to fetch templates: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * GET /api/templates/{templateId}
     * Recupera um template específico
     */
    public function getTemplate(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extrai templateId dos parâmetros da rota
            $routeParams = $request->getAttribute('routeParams', []);
            $templateId = $routeParams['templateId'] ?? null;

            if (!$templateId) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_TEMPLATE_ID',
                        'message' => 'Template ID is required'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            $this->logger->info('GET /api/templates/{templateId} - Fetching template', [
                'template_id' => $templateId
            ]);

            // Obtém query params
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Busca template
            $template = $this->templateService->getTemplateById($templateId, $providerName);

            if ($template === null) {
                $this->logger->warning('Template not found', [
                    'template_id' => $templateId
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'TEMPLATE_NOT_FOUND',
                        'message' => "Template not found: {$templateId}"
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 404);
            }

            $this->logger->info('Template fetched successfully', [
                'template_id' => $templateId
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'language' => $template->language,
                    'status' => $template->status,
                    'category' => $template->category,
                    'components' => $template->components,
                    'rejection_reason' => $template->rejectionReason,
                    'is_approved' => $template->isApproved(),
                    'parameters' => $template->getParameters()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch template', [
                'template_id' => $templateId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'FETCH_TEMPLATE_ERROR',
                    'message' => 'Failed to fetch template: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/templates/sync
     * Sincroniza templates manualmente do provedor
     */
    public function syncTemplates(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Obtém query params
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            $this->logger->info('POST /api/templates/sync - Starting manual synchronization', [
                'provider' => $providerName ?? 'all'
            ]);

            // Executa sincronização
            $stats = $this->templateService->syncTemplates($providerName);

            $this->logger->info('Template synchronization completed', $stats);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'message' => 'Templates synchronized successfully',
                    'statistics' => $stats
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to synchronize templates', [
                'provider' => $providerName ?? 'all',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SYNC_TEMPLATES_ERROR',
                    'message' => 'Failed to synchronize templates: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }
}
