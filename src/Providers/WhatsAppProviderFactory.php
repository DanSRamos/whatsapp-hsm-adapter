<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Providers\Infobip\InfobipProvider;
use WhatsApp\Adapter\Providers\Twilio\TwilioProvider;

/**
 * Factory for creating WhatsApp provider instances
 * 
 * This factory creates and manages provider instances based on configuration,
 * and can automatically detect the provider from incoming webhooks.
 */
class WhatsAppProviderFactory
{
    /** @var array<string, WhatsAppProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly array $config,
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get or create a provider instance
     *
     * @param string|null $providerName The provider name, or null to use default
     * @return WhatsAppProviderInterface The provider instance
     * @throws \InvalidArgumentException If provider is unknown
     */
    public function getProvider(?string $providerName = null): WhatsAppProviderInterface
    {
        $providerName = $providerName ?? $this->config['default_provider'] ?? 'infobip';

        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    /**
     * Create a new provider instance
     *
     * @param string $providerName The provider name
     * @return WhatsAppProviderInterface The provider instance
     * @throws \InvalidArgumentException If provider is unknown
     */
    private function createProvider(string $providerName): WhatsAppProviderInterface
    {
        if (!isset($this->config['providers'][$providerName])) {
            throw new \InvalidArgumentException("Provider configuration not found: {$providerName}");
        }

        $providerConfig = $this->config['providers'][$providerName];

        return match($providerName) {
            'infobip' => new InfobipProvider(
                $this->httpClient,
                $providerConfig,
                $this->logger
            ),
            'twilio' => new TwilioProvider(
                $this->httpClient,
                $providerConfig,
                $this->logger
            ),
            default => throw new \InvalidArgumentException("Unknown provider: {$providerName}")
        };
    }

    /**
     * Detect provider from incoming webhook request
     * 
     * This method attempts to identify which provider sent the webhook
     * by validating the request against each configured provider.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @return WhatsAppProviderInterface|null The detected provider, or null if none match
     */
    public function detectProviderFromWebhook(ServerRequestInterface $request): ?WhatsAppProviderInterface
    {
        foreach ($this->config['providers'] as $name => $config) {
            try {
                $provider = $this->getProvider($name);
                if ($provider->validateWebhook($request)) {
                    $this->logger->info('Detected provider from webhook', [
                        'provider' => $name
                    ]);
                    return $provider;
                }
            } catch (\Throwable $e) {
                $this->logger->debug('Provider validation failed', [
                    'provider' => $name,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->warning('Could not detect provider from webhook');
        return null;
    }

    /**
     * Get all configured provider names
     *
     * @return array<string> Array of provider names
     */
    public function getConfiguredProviders(): array
    {
        return array_keys($this->config['providers'] ?? []);
    }
}
