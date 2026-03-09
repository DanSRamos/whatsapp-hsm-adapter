<?php

namespace WhatsApp\Adapter\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Serviço para gerenciar chamadas via WhatsApp usando Infobip
 */
class InfobipCallService
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl;
    private string $sender;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->sender = $config['sender'];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'App ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Inicia uma chamada via WhatsApp
     */
    public function initiateCall(string $to, ?string $from = null): array
    {
        $from = $from ?? $this->sender;

        $payload = [
            'from' => $from,
            'to' => $this->formatPhoneNumber($to),
        ];

        try {
            $response = $this->client->post('/calls/1/calls', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'call_id' => $data['callId'] ?? null,
                'status' => $data['status'] ?? 'initiated',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Obtém o status de uma chamada
     */
    public function getCallStatus(string $callId): array
    {
        try {
            $response = $this->client->get("/calls/1/calls/{$callId}");
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'call_id' => $callId,
                'status' => $data['status'] ?? 'unknown',
                'duration' => $data['duration'] ?? 0,
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Encerra uma chamada ativa
     */
    public function hangupCall(string $callId): array
    {
        try {
            $response = $this->client->post("/calls/1/calls/{$callId}/hangup");
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'call_id' => $callId,
                'status' => 'terminated',
                'data' => $data,
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Lista histórico de chamadas
     */
    public function getCallHistory(?string $from = null, ?string $to = null, int $limit = 50): array
    {
        $queryParams = [
            'limit' => $limit,
        ];

        if ($from) {
            $queryParams['from'] = $from;
        }

        if ($to) {
            $queryParams['to'] = $this->formatPhoneNumber($to);
        }

        try {
            $response = $this->client->get('/calls/1/calls', [
                'query' => $queryParams,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'calls' => $data['results'] ?? [],
                'total' => count($data['results'] ?? []),
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * Formata número de telefone para formato internacional
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove caracteres não numéricos
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Adiciona + se não tiver
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
