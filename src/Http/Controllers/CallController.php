<?php

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WhatsApp\Adapter\Services\InfobipCallService;

/**
 * Controller para gerenciar chamadas via WhatsApp
 */
class CallController
{
    private InfobipCallService $callService;

    public function __construct()
    {
        $config = require __DIR__ . '/../../../config/providers.php';
        $infobipConfig = $config['providers']['infobip']['config'];
        
        $this->callService = new InfobipCallService($infobipConfig);
    }

    /**
     * Inicia uma nova chamada
     */
    public function initiateCall(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['to'])) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Campo "to" é obrigatório',
            ], 400);
        }

        $result = $this->callService->initiateCall(
            $data['to'],
            $data['from'] ?? null
        );

        $statusCode = $result['success'] ? 200 : 400;
        return $this->jsonResponse($response, $result, $statusCode);
    }

    /**
     * Obtém status de uma chamada
     */
    public function getCallStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $callId = $args['callId'] ?? null;

        if (!$callId) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Call ID é obrigatório',
            ], 400);
        }

        $result = $this->callService->getCallStatus($callId);
        $statusCode = $result['success'] ? 200 : 404;
        
        return $this->jsonResponse($response, $result, $statusCode);
    }

    /**
     * Encerra uma chamada
     */
    public function hangupCall(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $callId = $args['callId'] ?? null;

        if (!$callId) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Call ID é obrigatório',
            ], 400);
        }

        $result = $this->callService->hangupCall($callId);
        $statusCode = $result['success'] ? 200 : 400;
        
        return $this->jsonResponse($response, $result, $statusCode);
    }

    /**
     * Lista histórico de chamadas
     */
    public function getCallHistory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        
        $result = $this->callService->getCallHistory(
            $params['from'] ?? null,
            $params['to'] ?? null,
            (int)($params['limit'] ?? 50)
        );

        return $this->jsonResponse($response, $result, 200);
    }

    /**
     * Helper para retornar resposta JSON
     */
    private function jsonResponse(ResponseInterface $response, array $data, int $statusCode = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
