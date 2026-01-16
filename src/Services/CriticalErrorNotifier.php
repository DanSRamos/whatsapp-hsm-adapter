<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Service for notifying administrators of critical errors
 */
class CriticalErrorNotifier
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Notify administrators of a critical error
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @return bool Whether notification was sent successfully
     */
    public function notifyCriticalError(string $message, array $context = []): bool
    {
        $this->logger->critical('Critical error occurred', [
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $success = true;

        // Send email notification if configured
        if ($this->config['email']['enabled'] ?? false) {
            $success = $this->sendEmailNotification($message, $context) && $success;
        }

        // Send Slack notification if configured
        if ($this->config['slack']['enabled'] ?? false) {
            $success = $this->sendSlackNotification($message, $context) && $success;
        }

        // Send webhook notification if configured
        if ($this->config['webhook']['enabled'] ?? false) {
            $success = $this->sendWebhookNotification($message, $context) && $success;
        }

        return $success;
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(string $message, array $context): bool
    {
        try {
            $to = $this->config['email']['to'] ?? [];
            $from = $this->config['email']['from'] ?? 'noreply@whatsapp-adapter.local';
            $subject = $this->config['email']['subject'] ?? 'Critical Error in WhatsApp Adapter';

            if (empty($to)) {
                $this->logger->warning('Email notification configured but no recipients specified');
                return false;
            }

            $body = $this->formatEmailBody($message, $context);

            // In a real implementation, this would use a proper email library
            // For now, we'll just log that we would send an email
            $this->logger->info('Would send email notification', [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'body_length' => strlen($body)
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send Slack notification
     */
    private function sendSlackNotification(string $message, array $context): bool
    {
        try {
            $webhookUrl = $this->config['slack']['webhook_url'] ?? null;

            if (empty($webhookUrl)) {
                $this->logger->warning('Slack notification configured but no webhook URL specified');
                return false;
            }

            $payload = [
                'text' => ':rotating_light: *Critical Error in WhatsApp Adapter*',
                'attachments' => [
                    [
                        'color' => 'danger',
                        'title' => 'Error Message',
                        'text' => $message,
                        'fields' => $this->formatSlackFields($context),
                        'footer' => 'WhatsApp Adapter',
                        'ts' => time()
                    ]
                ]
            ];

            // In a real implementation, this would make an HTTP request to Slack
            // For now, we'll just log that we would send a Slack message
            $this->logger->info('Would send Slack notification', [
                'webhook_url' => substr($webhookUrl, 0, 30) . '...',
                'payload' => $payload
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send Slack notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send webhook notification
     */
    private function sendWebhookNotification(string $message, array $context): bool
    {
        try {
            $webhookUrl = $this->config['webhook']['url'] ?? null;

            if (empty($webhookUrl)) {
                $this->logger->warning('Webhook notification configured but no URL specified');
                return false;
            }

            $payload = [
                'level' => 'critical',
                'message' => $message,
                'context' => $context,
                'timestamp' => date('c'),
                'service' => 'whatsapp-adapter'
            ];

            // In a real implementation, this would make an HTTP request
            // For now, we'll just log that we would send a webhook
            $this->logger->info('Would send webhook notification', [
                'webhook_url' => substr($webhookUrl, 0, 30) . '...',
                'payload' => $payload
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send webhook notification', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Format email body
     */
    private function formatEmailBody(string $message, array $context): string
    {
        $body = "A critical error has occurred in the WhatsApp Adapter:\n\n";
        $body .= "Error Message:\n";
        $body .= $message . "\n\n";

        if (!empty($context)) {
            $body .= "Context:\n";
            foreach ($context as $key => $value) {
                $body .= "  {$key}: " . json_encode($value) . "\n";
            }
        }

        $body .= "\nTimestamp: " . date('Y-m-d H:i:s') . "\n";

        return $body;
    }

    /**
     * Format Slack fields
     */
    private function formatSlackFields(array $context): array
    {
        $fields = [];

        foreach ($context as $key => $value) {
            $fields[] = [
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'value' => is_string($value) ? $value : json_encode($value),
                'short' => strlen(json_encode($value)) < 50
            ];
        }

        return $fields;
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'email' => [
                'enabled' => (bool) getenv('CRITICAL_ERROR_EMAIL_ENABLED'),
                'to' => explode(',', getenv('CRITICAL_ERROR_EMAIL_TO') ?: ''),
                'from' => getenv('CRITICAL_ERROR_EMAIL_FROM') ?: 'noreply@whatsapp-adapter.local',
                'subject' => 'Critical Error in WhatsApp Adapter'
            ],
            'slack' => [
                'enabled' => (bool) getenv('CRITICAL_ERROR_SLACK_ENABLED'),
                'webhook_url' => getenv('CRITICAL_ERROR_SLACK_WEBHOOK_URL')
            ],
            'webhook' => [
                'enabled' => (bool) getenv('CRITICAL_ERROR_WEBHOOK_ENABLED'),
                'url' => getenv('CRITICAL_ERROR_WEBHOOK_URL')
            ]
        ];
    }
}
