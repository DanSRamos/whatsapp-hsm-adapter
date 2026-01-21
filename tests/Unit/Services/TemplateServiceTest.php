<?php

declare(strict_types=1);

use WhatsApp\Adapter\Services\TemplateService;
use WhatsApp\Adapter\Services\CacheInterface;
use WhatsApp\Adapter\Providers\MessagingProviderInterface;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Providers\Models\ProviderTemplate;
use WhatsApp\Adapter\Repositories\TemplateRepositoryInterface;
use WhatsApp\Adapter\Models\Template;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->provider = Mockery::mock(MessagingProviderInterface::class);
    $this->providerFactory = Mockery::mock(MessagingProviderFactory::class);
    $this->repository = Mockery::mock(TemplateRepositoryInterface::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->logger = new NullLogger();
    
    // Configure factory to return the mocked provider
    $this->providerFactory->shouldReceive('getProvider')
        ->andReturn($this->provider);
    
    $this->service = new TemplateService(
        $this->providerFactory,
        $this->repository,
        $this->cache,
        $this->logger
    );
});

afterEach(function () {
    Mockery::close();
});

describe('TemplateService', function () {
    
    describe('getAllTemplates', function () {
        
        test('returns templates from cache when available (cache hit)', function () {
            $cachedTemplates = [
                new Template('1', 'template1', 'pt', 'approved', 'marketing', []),
                new Template('2', 'template2', 'en', 'approved', 'utility', [])
            ];
            
            $this->cache->shouldReceive('has')
                ->with('templates:all')
                ->once()
                ->andReturn(true);
            
            $this->cache->shouldReceive('get')
                ->with('templates:all')
                ->once()
                ->andReturn($cachedTemplates);
            
            // Provider não deve ser chamado
            $this->provider->shouldNotReceive('getTemplates');
            
            $result = $this->service->getAllTemplates();
            
            expect($result)->toBe($cachedTemplates);
            expect($result)->toHaveCount(2);
        });
        
        test('fetches from provider and caches when cache miss', function () {
            $providerTemplates = [
                (object)[
                    'id' => '1',
                    'name' => 'template1',
                    'language' => 'pt',
                    'status' => 'approved',
                    'category' => 'marketing',
                    'components' => [],
                    'rejectionReason' => null
                ]
            ];
            
            $this->cache->shouldReceive('has')
                ->with('templates:all')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplates')
                ->once()
                ->andReturn($providerTemplates);
            
            $this->repository->shouldReceive('save')
                ->once();
            
            $this->cache->shouldReceive('set')
                ->with('template:1', Mockery::type(Template::class), 3600)
                ->once();
            
            $this->cache->shouldReceive('set')
                ->with('templates:all', Mockery::type('array'), 3600)
                ->once();
            
            $result = $this->service->getAllTemplates();
            
            expect($result)->toBeArray();
            expect($result)->toHaveCount(1);
            expect($result[0])->toBeInstanceOf(Template::class);
            expect($result[0]->id)->toBe('1');
        });
        
        test('falls back to repository when provider fails', function () {
            $repositoryTemplates = [
                new Template('1', 'template1', 'pt', 'approved', 'marketing', [])
            ];
            
            $this->cache->shouldReceive('has')
                ->with('templates:all')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplates')
                ->once()
                ->andThrow(new \RuntimeException('Provider error'));
            
            $this->repository->shouldReceive('findAll')
                ->once()
                ->andReturn($repositoryTemplates);
            
            $result = $this->service->getAllTemplates();
            
            expect($result)->toBe($repositoryTemplates);
        });
    });
    
    describe('getTemplateById', function () {
        
        test('returns template from cache when available (cache hit)', function () {
            $cachedTemplate = new Template('1', 'template1', 'pt', 'approved', 'marketing', []);
            
            $this->cache->shouldReceive('has')
                ->with('template:1')
                ->once()
                ->andReturn(true);
            
            $this->cache->shouldReceive('get')
                ->with('template:1')
                ->once()
                ->andReturn($cachedTemplate);
            
            // Provider não deve ser chamado
            $this->provider->shouldNotReceive('getTemplate');
            
            $result = $this->service->getTemplateById('1');
            
            expect($result)->toBe($cachedTemplate);
        });
        
        test('fetches from provider and caches when cache miss', function () {
            $providerTemplate = new ProviderTemplate(
                id: '1',
                name: 'template1',
                language: 'pt',
                status: 'approved',
                category: 'marketing',
                components: [],
                rejectionReason: null
            );
            
            $this->cache->shouldReceive('has')
                ->with('template:1')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplate')
                ->with('1')
                ->once()
                ->andReturn($providerTemplate);
            
            $this->repository->shouldReceive('save')
                ->once()
                ->andReturnNull();
            
            $this->cache->shouldReceive('set')
                ->with('template:1', Mockery::type(Template::class), 3600)
                ->once()
                ->andReturnNull();
            
            $result = $this->service->getTemplateById('1');
            
            expect($result)->toBeInstanceOf(Template::class);
            expect($result->id)->toBe('1');
        });
        
        test('returns null when template not found', function () {
            $this->cache->shouldReceive('has')
                ->with('template:999')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplate')
                ->with('999')
                ->once()
                ->andReturn(null);
            
            $result = $this->service->getTemplateById('999');
            
            expect($result)->toBeNull();
        });
        
        test('falls back to repository when provider fails', function () {
            $repositoryTemplate = new Template('1', 'template1', 'pt', 'approved', 'marketing', []);
            
            $this->cache->shouldReceive('has')
                ->with('template:1')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplate')
                ->with('1')
                ->once()
                ->andThrow(new \RuntimeException('Provider error'));
            
            $this->repository->shouldReceive('findById')
                ->with('1')
                ->once()
                ->andReturn($repositoryTemplate);
            
            $result = $this->service->getTemplateById('1');
            
            expect($result)->toBe($repositoryTemplate);
        });
    });
    
    describe('processTemplateUpdate', function () {
        
        test('deletes template when action is deleted', function () {
            $webhookData = [
                'id' => '1',
                'action' => 'deleted'
            ];
            
            $this->repository->shouldReceive('delete')
                ->with('1')
                ->once();
            
            $this->cache->shouldReceive('delete')
                ->with('template:1')
                ->once();
            
            $this->cache->shouldReceive('delete')
                ->with('templates:all')
                ->once();
            
            $this->service->processTemplateUpdate($webhookData);
        });
        
        test('deletes template when status is deleted', function () {
            $webhookData = [
                'id' => '1',
                'status' => 'deleted'
            ];
            
            $this->repository->shouldReceive('delete')
                ->with('1')
                ->once();
            
            $this->cache->shouldReceive('delete')
                ->with('template:1')
                ->once();
            
            $this->cache->shouldReceive('delete')
                ->with('templates:all')
                ->once();
            
            $this->service->processTemplateUpdate($webhookData);
        });
        
        test('invalidates cache and reloads template when modified', function () {
            $webhookData = [
                'id' => '1',
                'action' => 'updated'
            ];
            
            $providerTemplate = new ProviderTemplate(
                id: '1',
                name: 'template1',
                language: 'pt',
                status: 'approved',
                category: 'marketing',
                components: [],
                rejectionReason: null
            );
            
            // Invalida cache
            $this->cache->shouldReceive('delete')
                ->with('template:1')
                ->once()
                ->andReturnNull();
            
            $this->cache->shouldReceive('delete')
                ->with('templates:all')
                ->once()
                ->andReturnNull();
            
            // Recarrega template
            $this->cache->shouldReceive('has')
                ->with('template:1')
                ->once()
                ->andReturn(false);
            
            $this->provider->shouldReceive('getTemplate')
                ->with('1')
                ->once()
                ->andReturn($providerTemplate);
            
            $this->repository->shouldReceive('save')
                ->once()
                ->andReturnNull();
            
            $this->cache->shouldReceive('set')
                ->with('template:1', Mockery::type(Template::class), 3600)
                ->once()
                ->andReturnNull();
            
            $this->service->processTemplateUpdate($webhookData);
        });
    });
    
    describe('invalidateCache', function () {
        
        test('deletes all templates cache', function () {
            $this->cache->shouldReceive('delete')
                ->with('templates:all')
                ->once();
            
            $this->service->invalidateCache();
        });
    });
});
