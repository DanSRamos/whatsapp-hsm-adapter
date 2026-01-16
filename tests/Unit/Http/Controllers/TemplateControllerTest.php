<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\TemplateController;
use WhatsApp\Adapter\Services\TemplateService;
use WhatsApp\Adapter\Models\Template;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->templateService = Mockery::mock(TemplateService::class);
    $this->logger = new NullLogger();
    
    $this->controller = new TemplateController(
        $this->templateService,
        $this->logger
    );
});

afterEach(function () {
    Mockery::close();
});

describe('TemplateController', function () {
    
    describe('getTemplates', function () {
        
        test('returns templates successfully', function () {
            $templates = [
                new Template('1', 'template1', 'pt', 'approved', 'marketing', [
                    ['type' => 'BODY', 'text' => 'Hello {{1}}']
                ]),
                new Template('2', 'template2', 'en', 'approved', 'utility', [])
            ];
            
            $this->templateService->shouldReceive('getAllTemplates')
                ->with(null)
                ->once()
                ->andReturn($templates);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplates($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['templates'])->toHaveCount(2);
            expect($body['data']['count'])->toBe(2);
            expect($body['data']['templates'][0]['id'])->toBe('1');
            expect($body['data']['templates'][0]['name'])->toBe('template1');
        });
        
        test('returns templates for specific provider', function () {
            $templates = [
                new Template('1', 'template1', 'pt', 'approved', 'marketing', [])
            ];
            
            $this->templateService->shouldReceive('getAllTemplates')
                ->with('infobip')
                ->once()
                ->andReturn($templates);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn(['provider' => 'infobip']);
            
            $response = $this->controller->getTemplates($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['templates'])->toHaveCount(1);
        });
        
        test('returns error when service fails', function () {
            $this->templateService->shouldReceive('getAllTemplates')
                ->with(null)
                ->once()
                ->andThrow(new \RuntimeException('Service error'));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplates($request);
            
            expect($response->getStatusCode())->toBe(500);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('FETCH_TEMPLATES_ERROR');
            expect($body['error']['message'])->toContain('Service error');
        });
    });
    
    describe('getTemplate', function () {
        
        test('returns template successfully', function () {
            $template = new Template(
                '1',
                'template1',
                'pt',
                'approved',
                'marketing',
                [['type' => 'BODY', 'text' => 'Hello {{1}}']]
            );
            
            $this->templateService->shouldReceive('getTemplateById')
                ->with('1', null)
                ->once()
                ->andReturn($template);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn(['templateId' => '1']);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplate($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['id'])->toBe('1');
            expect($body['data']['name'])->toBe('template1');
            expect($body['data']['language'])->toBe('pt');
        });
        
        test('returns 404 when template not found', function () {
            $this->templateService->shouldReceive('getTemplateById')
                ->with('999', null)
                ->once()
                ->andReturn(null);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn(['templateId' => '999']);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplate($request);
            
            expect($response->getStatusCode())->toBe(404);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('TEMPLATE_NOT_FOUND');
        });
        
        test('returns 400 when template ID is missing', function () {
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplate($request);
            
            expect($response->getStatusCode())->toBe(400);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('MISSING_TEMPLATE_ID');
        });
        
        test('returns error when service fails', function () {
            $this->templateService->shouldReceive('getTemplateById')
                ->with('1', null)
                ->once()
                ->andThrow(new \RuntimeException('Service error'));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn(['templateId' => '1']);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getTemplate($request);
            
            expect($response->getStatusCode())->toBe(500);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('FETCH_TEMPLATE_ERROR');
        });
    });
    
    describe('syncTemplates', function () {
        
        test('synchronizes templates successfully for all providers', function () {
            $stats = [
                'added' => 5,
                'updated' => 3,
                'deleted' => 1,
                'total' => 8
            ];
            
            $this->templateService->shouldReceive('syncTemplates')
                ->with(null)
                ->once()
                ->andReturn($stats);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->syncTemplates($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message'])->toContain('synchronized successfully');
            expect($body['data']['statistics'])->toBe($stats);
        });
        
        test('synchronizes templates for specific provider', function () {
            $stats = [
                'added' => 2,
                'updated' => 1,
                'deleted' => 0,
                'total' => 3
            ];
            
            $this->templateService->shouldReceive('syncTemplates')
                ->with('twilio')
                ->once()
                ->andReturn($stats);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn(['provider' => 'twilio']);
            
            $response = $this->controller->syncTemplates($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['statistics']['added'])->toBe(2);
        });
        
        test('returns error when synchronization fails', function () {
            $this->templateService->shouldReceive('syncTemplates')
                ->with(null)
                ->once()
                ->andThrow(new \RuntimeException('Sync error'));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->syncTemplates($request);
            
            expect($response->getStatusCode())->toBe(500);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('SYNC_TEMPLATES_ERROR');
            expect($body['error']['message'])->toContain('Sync error');
        });
    });
});

