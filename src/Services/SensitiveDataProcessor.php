<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor to redact sensitive information from logs
 */
class SensitiveDataProcessor implements ProcessorInterface
{
    private array $patterns;

    public function __construct(array $patterns = [])
    {
        $this->patterns = $patterns ?: $this->getDefaultPatterns();
    }

    /**
     * Process log record to redact sensitive data
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        // Redact sensitive data from message
        $message = $this->redactSensitiveData($record->message);

        // Redact sensitive data from context
        $context = $this->redactArrayData($record->context);

        // Redact sensitive data from extra
        $extra = $this->redactArrayData($record->extra);

        return $record->with(
            message: $message,
            context: $context,
            extra: $extra
        );
    }

    /**
     * Redact sensitive data from a string
     */
    private function redactSensitiveData(string $data): string
    {
        foreach ($this->patterns as $name => $pattern) {
            $data = preg_replace_callback(
                $pattern,
                function ($matches) use ($name) {
                    // Keep the key/field name but redact the value
                    if (isset($matches[1])) {
                        return str_replace($matches[1], '[REDACTED]', $matches[0]);
                    }
                    return '[REDACTED]';
                },
                $data
            );
        }

        return $data;
    }

    /**
     * Redact sensitive data from array recursively
     */
    private function redactArrayData(array $data): array
    {
        $sensitiveKeys = [
            'api_key',
            'apiKey',
            'token',
            'password',
            'authorization',
            'auth',
            'secret',
            'webhook_secret',
            'webhookSecret',
        ];

        foreach ($data as $key => $value) {
            // Check if key is sensitive
            if (in_array($key, $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
                continue;
            }

            // Recursively process arrays
            if (is_array($value)) {
                $data[$key] = $this->redactArrayData($value);
                continue;
            }

            // Process string values
            if (is_string($value)) {
                $data[$key] = $this->redactSensitiveData($value);
            }
        }

        return $data;
    }

    /**
     * Get default sensitive data patterns
     */
    private function getDefaultPatterns(): array
    {
        return [
            'api_key' => '/api[_-]?key["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
            'token' => '/token["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
            'password' => '/password["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
            'authorization' => '/authorization["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
        ];
    }
}
