<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use WhatsApp\Adapter\Models\Template;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\TemplateRepositoryInterface;
use Psr\Log\LoggerInterface;

class TemplateService
{
    private const CACHE_KEY_ALL_TEMPLATES = 'templates:all';
    private const CACHE_KEY_TEMPLATE_PREFIX = 'template:';
    private const CACHE_TTL = 3600; // 1 hora

    public function __construct(
        private MessagingProviderFactory $providerFactory,
        private TemplateRepositoryInterface $templateRepository,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Recupera todos os templates da API do provider
     * Usa cache para reduzir chamadas à API
     */
    public function getAllTemplates(?string $providerName = null): array
    {
        // Tenta recuperar do cache
        if ($this->cache->has(self::CACHE_KEY_ALL_TEMPLATES)) {
            $this->logger->debug('Templates retrieved from cache');
            return $this->cache->get(self::CACHE_KEY_ALL_TEMPLATES);
        }

        $this->logger->info('Fetching templates from provider API');

        try {
            // Busca templates do provider
            $provider = $this->providerFactory->getProvider($providerName);
            $providerTemplates = $provider->getTemplates();

            // Converte para objetos Template
            $templates = [];
            foreach ($providerTemplates as $providerTemplate) {
                $template = new Template(
                    id: $providerTemplate->id,
                    name: $providerTemplate->name,
                    language: $providerTemplate->language,
                    status: $providerTemplate->status,
                    category: $providerTemplate->category,
                    components: $providerTemplate->components,
                    rejectionReason: $providerTemplate->rejectionReason
                );

                $templates[] = $template;

                // Salva no repositório para persistência
                $this->templateRepository->save($template);

                // Cacheia template individual
                $this->cache->set(
                    self::CACHE_KEY_TEMPLATE_PREFIX . $template->id,
                    $template,
                    self::CACHE_TTL
                );
            }

            // Cacheia lista completa
            $this->cache->set(
                self::CACHE_KEY_ALL_TEMPLATES,
                $templates,
                self::CACHE_TTL
            );

            $this->logger->info('Templates fetched and cached', [
                'count' => count($templates)
            ]);

            return $templates;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch templates from provider', [
                'error' => $e->getMessage()
            ]);

            // Fallback: tenta recuperar do repositório
            $this->logger->info('Attempting to retrieve templates from repository');
            return $this->templateRepository->findAll();
        }
    }

    /**
     * Recupera um template específico por ID
     */
    public function getTemplateById(string $templateId, ?string $providerName = null): ?Template
    {
        // Tenta recuperar do cache
        $cacheKey = self::CACHE_KEY_TEMPLATE_PREFIX . $templateId;
        if ($this->cache->has($cacheKey)) {
            $this->logger->debug('Template retrieved from cache', [
                'template_id' => $templateId
            ]);
            return $this->cache->get($cacheKey);
        }

        $this->logger->info('Fetching template from provider API', [
            'template_id' => $templateId
        ]);

        try {
            // Busca template do provider
            $provider = $this->providerFactory->getProvider($providerName);
            $providerTemplate = $provider->getTemplate($templateId);

            if ($providerTemplate === null) {
                $this->logger->warning('Template not found', [
                    'template_id' => $templateId
                ]);
                return null;
            }

            // Converte para objeto Template
            $template = new Template(
                id: $providerTemplate->id,
                name: $providerTemplate->name,
                language: $providerTemplate->language,
                status: $providerTemplate->status,
                category: $providerTemplate->category,
                components: $providerTemplate->components,
                rejectionReason: $providerTemplate->rejectionReason
            );

            // Salva no repositório
            $this->templateRepository->save($template);

            // Cacheia template
            $this->cache->set($cacheKey, $template, self::CACHE_TTL);

            $this->logger->info('Template fetched and cached', [
                'template_id' => $templateId
            ]);

            return $template;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch template from provider', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);

            // Fallback: tenta recuperar do repositório
            $this->logger->info('Attempting to retrieve template from repository', [
                'template_id' => $templateId
            ]);
            return $this->templateRepository->findById($templateId);
        }
    }

    /**
     * Sincroniza templates manualmente do provedor para a base de dados
     * 
     * @param string|null $providerName Nome do provedor (null = todos)
     * @return array Estatísticas da sincronização (added, updated, deleted)
     */
    public function syncTemplates(?string $providerName = null): array
    {
        $this->logger->info('Starting manual template synchronization', [
            'provider' => $providerName ?? 'all'
        ]);

        $stats = [
            'added' => 0,
            'updated' => 0,
            'deleted' => 0,
            'total' => 0
        ];

        try {
            // Determina quais provedores sincronizar
            $providersToSync = $providerName 
                ? [$providerName] 
                : $this->providerFactory->getConfiguredProviders();

            foreach ($providersToSync as $currentProviderName) {
                $this->logger->info('Syncing templates for provider', [
                    'provider' => $currentProviderName
                ]);

                $provider = $this->providerFactory->getProvider($currentProviderName);
                
                // Busca templates do provider
                $providerTemplates = $provider->getTemplates();
                
                // Busca templates existentes no repositório
                $existingTemplates = $this->templateRepository->findAll();
                $existingIds = array_map(fn($t) => $t->id, $existingTemplates);
                
                // Processa cada template do provider
                $providerIds = [];
                foreach ($providerTemplates as $providerTemplate) {
                    $providerIds[] = $providerTemplate->id;
                    
                    $template = new Template(
                        id: $providerTemplate->id,
                        name: $providerTemplate->name,
                        language: $providerTemplate->language,
                        status: $providerTemplate->status,
                        category: $providerTemplate->category,
                        components: $providerTemplate->components,
                        rejectionReason: $providerTemplate->rejectionReason
                    );

                    // Verifica se é novo ou atualização
                    if (in_array($template->id, $existingIds)) {
                        $stats['updated']++;
                    } else {
                        $stats['added']++;
                    }

                    // Salva no repositório
                    $this->templateRepository->save($template);

                    // Atualiza cache individual
                    $this->cache->set(
                        self::CACHE_KEY_TEMPLATE_PREFIX . $template->id,
                        $template,
                        self::CACHE_TTL
                    );
                }

                // Identifica templates que foram removidos do provider
                $deletedIds = array_diff($existingIds, $providerIds);
                foreach ($deletedIds as $deletedId) {
                    $this->templateRepository->delete($deletedId);
                    $this->cache->delete(self::CACHE_KEY_TEMPLATE_PREFIX . $deletedId);
                    $stats['deleted']++;
                }

                $stats['total'] += count($providerTemplates);
            }

            // Invalida cache da lista completa
            $this->cache->delete(self::CACHE_KEY_ALL_TEMPLATES);

            $this->logger->info('Template synchronization completed', $stats);

            return $stats;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to synchronize templates', [
                'error' => $e->getMessage(),
                'provider' => $providerName
            ]);
            throw $e;
        }
    }

    /**
     * Processa atualização de template recebida via webhook
     */
    public function processTemplateUpdate(array $webhookData): void
    {
        $this->logger->info('Processing template update', [
            'webhook_data' => $webhookData
        ]);

        try {
            $templateId = $webhookData['id'] ?? null;
            $action = $webhookData['action'] ?? 'updated';

            if (!$templateId) {
                $this->logger->warning('Template update missing ID', [
                    'webhook_data' => $webhookData
                ]);
                return;
            }

            // Se template foi apagado
            if ($action === 'deleted' || ($webhookData['status'] ?? null) === 'deleted') {
                $this->logger->info('Template deleted', [
                    'template_id' => $templateId
                ]);

                // Remove do repositório
                $this->templateRepository->delete($templateId);

                // Remove do cache
                $this->cache->delete(self::CACHE_KEY_TEMPLATE_PREFIX . $templateId);
                $this->cache->delete(self::CACHE_KEY_ALL_TEMPLATES);

                return;
            }

            // Template foi modificado - invalida cache e recarrega
            $this->logger->info('Template modified', [
                'template_id' => $templateId,
                'action' => $action
            ]);

            // Invalida cache
            $this->cache->delete(self::CACHE_KEY_TEMPLATE_PREFIX . $templateId);
            $this->cache->delete(self::CACHE_KEY_ALL_TEMPLATES);

            // Recarrega template do provider
            $this->getTemplateById($templateId);

            $this->logger->info('Template update processed successfully', [
                'template_id' => $templateId
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process template update', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Invalida cache de templates
     */
    public function invalidateCache(): void
    {
        $this->logger->info('Invalidating template cache');

        // Remove cache de lista completa
        $this->cache->delete(self::CACHE_KEY_ALL_TEMPLATES);

        // Nota: não removemos templates individuais pois não temos lista de IDs
        // Eles expirarão naturalmente após o TTL

        $this->logger->info('Template cache invalidated');
    }
}
