<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating configured Monolog logger instances
 */
class LoggerFactory
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Create a logger instance with configured handlers
     */
    public function createLogger(string $name = 'whatsapp-adapter'): LoggerInterface
    {
        $logger = new Logger($name);

        // Add processors for context enrichment
        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        
        // Add sensitive data processor to redact sensitive information
        $sensitivePatterns = $this->config['sensitive_patterns'] ?? [];
        $logger->pushProcessor(new SensitiveDataProcessor($sensitivePatterns));

        // Add configured handlers
        foreach ($this->config['handlers'] as $handlerConfig) {
            $handler = $this->createHandler($handlerConfig);
            if ($handler) {
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    }

    /**
     * Create a handler based on configuration
     */
    private function createHandler(array $config): ?\Monolog\Handler\HandlerInterface
    {
        $type = $config['type'] ?? 'stream';
        $level = $this->parseLevel($config['level'] ?? 'debug');

        return match ($type) {
            'stream' => $this->createStreamHandler($config, $level),
            'rotating' => $this->createRotatingFileHandler($config, $level),
            'syslog' => $this->createSyslogHandler($config, $level),
            default => null,
        };
    }

    /**
     * Create a stream handler (file or stdout/stderr)
     */
    private function createStreamHandler(array $config, int $level): StreamHandler
    {
        $stream = $config['path'] ?? 'php://stdout';
        $handler = new StreamHandler($stream, $level);

        // Apply formatter
        if (($config['format'] ?? 'json') === 'json') {
            $handler->setFormatter(new JsonFormatter());
        } else {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            ));
        }

        return $handler;
    }

    /**
     * Create a rotating file handler
     */
    private function createRotatingFileHandler(array $config, int $level): RotatingFileHandler
    {
        $filename = $config['path'] ?? 'storage/logs/whatsapp-adapter.log';
        $maxFiles = $config['max_files'] ?? 14;
        
        $handler = new RotatingFileHandler($filename, $maxFiles, $level);

        // Apply formatter
        if (($config['format'] ?? 'json') === 'json') {
            $handler->setFormatter(new JsonFormatter());
        } else {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            ));
        }

        return $handler;
    }

    /**
     * Create a syslog handler
     */
    private function createSyslogHandler(array $config, int $level): SyslogHandler
    {
        $ident = $config['ident'] ?? 'whatsapp-adapter';
        $facility = $config['facility'] ?? LOG_USER;
        
        return new SyslogHandler($ident, $facility, $level);
    }

    /**
     * Parse log level string to Monolog constant
     */
    private function parseLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
            default => Logger::DEBUG,
        };
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'handlers' => [
                [
                    'type' => 'rotating',
                    'path' => 'storage/logs/whatsapp-adapter.log',
                    'level' => getenv('LOG_LEVEL') ?: 'debug',
                    'format' => 'json',
                    'max_files' => 14,
                ],
                [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'level' => 'error',
                    'format' => 'json',
                ],
            ],
        ];
    }
}
