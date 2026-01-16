<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Serviço de validação de webhooks
 * Valida assinaturas HMAC e IPs de origem (whitelist)
 * 
 * Validates: Requirements 2.4, 5.3, 8.4, 10.4, 11.3
 */
class WebhookValidator
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Valida webhook verificando assinatura HMAC e IP de origem
     * 
     * @param ServerRequestInterface $request
     * @param string $providerName Nome do provedor (infobip, twilio, etc.)
     * @return bool True se o webhook é válido
     */
    public function validate(ServerRequestInterface $request, string $providerName): bool
    {
        // Validar IP se whitelist estiver configurada
        if (!$this->validateIpWhitelist($request, $providerName)) {
            $this->logger->warning('Webhook rejected: IP not in whitelist', [
                'provider' => $providerName,
                'ip' => $this->getClientIp($request)
            ]);
            return false;
        }

        // Validar assinatura HMAC
        if (!$this->validateHmacSignature($request, $providerName)) {
            $this->logger->warning('Webhook rejected: Invalid HMAC signature', [
                'provider' => $providerName,
                'ip' => $this->getClientIp($request)
            ]);
            return false;
        }

        return true;
    }

    /**
     * Valida assinatura HMAC do webhook
     * 
     * @param ServerRequestInterface $request
     * @param string $providerName
     * @return bool
     */
    public function validateHmacSignature(ServerRequestInterface $request, string $providerName): bool
    {
        $providerConfig = $this->config['providers'][$providerName] ?? null;
        
        if (!$providerConfig) {
            $this->logger->error('Provider configuration not found', [
                'provider' => $providerName
            ]);
            return false;
        }

        // Obter configuração de validação específica do provedor
        $signatureHeader = $providerConfig['signature_header'] ?? 'X-Signature';
        $secret = $providerConfig['webhook_secret'] ?? null;
        $algorithm = $providerConfig['signature_algorithm'] ?? 'sha256';

        if (!$secret) {
            $this->logger->warning('Webhook secret not configured', [
                'provider' => $providerName
            ]);
            // Se não há secret configurado, permitir (fail-open para desenvolvimento)
            return true;
        }

        // Obter assinatura do header
        $signature = $request->getHeaderLine($signatureHeader);
        
        if (empty($signature)) {
            $this->logger->warning('Webhook signature header missing', [
                'provider' => $providerName,
                'expected_header' => $signatureHeader
            ]);
            return false;
        }

        // Calcular assinatura esperada
        $body = (string) $request->getBody();
        
        // Resetar stream para que possa ser lido novamente
        $request->getBody()->rewind();
        
        $expectedSignature = $this->calculateSignature($body, $secret, $algorithm, $providerName, $request);

        // Comparação segura contra timing attacks
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            $this->logger->warning('Webhook signature mismatch', [
                'provider' => $providerName,
                'expected_prefix' => substr($expectedSignature, 0, 10) . '...',
                'received_prefix' => substr($signature, 0, 10) . '...'
            ]);
        }

        return $isValid;
    }

    /**
     * Calcula assinatura HMAC baseado no provedor
     * 
     * @param string $body
     * @param string $secret
     * @param string $algorithm
     * @param string $providerName
     * @param ServerRequestInterface $request
     * @return string
     */
    private function calculateSignature(
        string $body, 
        string $secret, 
        string $algorithm, 
        string $providerName,
        ServerRequestInterface $request
    ): string {
        // Diferentes provedores usam diferentes métodos de assinatura
        switch ($providerName) {
            case 'infobip':
                // Infobip: HMAC-SHA256 do body
                return hash_hmac($algorithm, $body, $secret);
                
            case 'twilio':
                // Twilio: HMAC-SHA1 de URL + params ordenados
                $url = (string) $request->getUri();
                $params = $request->getParsedBody() ?? [];
                ksort($params);
                
                $data = $url;
                foreach ($params as $key => $value) {
                    $data .= $key . $value;
                }
                
                return base64_encode(hash_hmac('sha1', $data, $secret, true));
                
            default:
                // Padrão: HMAC do body
                return hash_hmac($algorithm, $body, $secret);
        }
    }

    /**
     * Valida IP de origem contra whitelist
     * 
     * @param ServerRequestInterface $request
     * @param string $providerName
     * @return bool
     */
    public function validateIpWhitelist(ServerRequestInterface $request, string $providerName): bool
    {
        $providerConfig = $this->config['providers'][$providerName] ?? null;
        
        if (!$providerConfig) {
            return false;
        }

        // Se não há whitelist configurada, permitir todos os IPs
        $whitelist = $providerConfig['ip_whitelist'] ?? [];
        
        if (empty($whitelist)) {
            return true;
        }

        $clientIp = $this->getClientIp($request);

        // Verificar se IP está na whitelist
        foreach ($whitelist as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se um IP corresponde a um padrão (suporta CIDR)
     * 
     * @param string $ip IP a verificar
     * @param string $pattern Padrão (IP exato ou CIDR)
     * @return bool
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Se é um IP exato
        if (strpos($pattern, '/') === false) {
            return $ip === $pattern;
        }

        // Se é CIDR notation (ex: 192.168.1.0/24)
        list($subnet, $mask) = explode('/', $pattern);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Obtém o IP real do cliente, considerando proxies
     * 
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Verificar headers de proxy (em ordem de prioridade)
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];
                
                // X-Forwarded-For pode conter múltiplos IPs
                if ($header === 'HTTP_X_FORWARDED_FOR' && strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Valida múltiplos aspectos do webhook de uma vez
     * Útil para validação completa em um único método
     * 
     * @param ServerRequestInterface $request
     * @param string $providerName
     * @param array $options Opções adicionais de validação
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateComplete(ServerRequestInterface $request, string $providerName, array $options = []): array
    {
        $errors = [];

        // Validar IP
        if (!$this->validateIpWhitelist($request, $providerName)) {
            $errors[] = 'IP not in whitelist';
        }

        // Validar assinatura
        if (!$this->validateHmacSignature($request, $providerName)) {
            $errors[] = 'Invalid HMAC signature';
        }

        // Validações adicionais opcionais
        if (isset($options['require_timestamp']) && $options['require_timestamp']) {
            $timestamp = $request->getHeaderLine('X-Timestamp');
            if (empty($timestamp)) {
                $errors[] = 'Missing timestamp header';
            } elseif (isset($options['max_age_seconds'])) {
                $age = time() - (int)$timestamp;
                if ($age > $options['max_age_seconds']) {
                    $errors[] = 'Webhook too old';
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
