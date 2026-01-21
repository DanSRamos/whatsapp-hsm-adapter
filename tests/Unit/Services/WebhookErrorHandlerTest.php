<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WhatsApp\Adapter\Services\WebhookErrorHandler;
use WhatsApp\Adapter\Services\DeadLetterQueue;

class WebhookErrorHandlerTest extends TestCase
{
    private WebhookErrorHandler $handler;
    private DeadLetterQueue $deadLetterQueue;

    protected function setUp(): void
    {
        $this->deadLetterQueue = $this->createMock(DeadLetterQueue::class);
        $this->handler = new WebhookErrorHandler(
            new NullLogger(),
            $this->deadLetterQueue
        );
    }

    public function testHandleErrorWithMessagingWindowExpired(): void
    {
        $error = new \Exception('Messaging window expired', 2022);
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 1);

        $this->assertFalse($result['should_retry']);
        $this->assertEquals(0, $result['delay_ms']);
        $this->assertStringContainsString('Non-retryable', $result['reason']);
    }

    public function testHandleErrorWithAccountNotEligible(): void
    {
        $error = new \Exception('Account not eligible', 36103);
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 1);

        $this->assertFalse($result['should_retry']);
        $this->assertEquals(0, $result['delay_ms']);
    }

    public function testHandleErrorWithFeatureNotAvailable(): void
    {
        $error = new \Exception('Feature not available', 2534068);
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 1);

        $this->assertFalse($result['should_retry']);
        $this->assertEquals(0, $result['delay_ms']);
    }

    public function testHandleErrorWithRetryableError(): void
    {
        $error = new \Exception('Temporary error', 500);
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 1);

        $this->assertTrue($result['should_retry']);
        $this->assertGreaterThan(0, $result['delay_ms']);
        $this->assertEquals(1000, $result['delay_ms']); // First attempt: 1s
    }

    public function testHandleErrorWithExponentialBackoff(): void
    {
        $error = new \Exception('Temporary error', 500);
        $payload = ['object' => 'page', 'entry' => []];

        // First attempt: 1s
        $result1 = $this->handler->handleError($error, $payload, 1);
        $this->assertTrue($result1['should_retry']);
        $this->assertEquals(1000, $result1['delay_ms']);

        // Second attempt: 2s
        $result2 = $this->handler->handleError($error, $payload, 2);
        $this->assertTrue($result2['should_retry']);
        $this->assertEquals(2000, $result2['delay_ms']);

        // Third attempt: max reached, should not retry
        $result3 = $this->handler->handleError($error, $payload, 3);
        $this->assertFalse($result3['should_retry']);
        $this->assertEquals(0, $result3['delay_ms']);
    }

    public function testHandleErrorMaxAttemptsReached(): void
    {
        $error = new \Exception('Temporary error', 500);
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 3);

        $this->assertFalse($result['should_retry']);
        $this->assertStringContainsString('Max retry attempts', $result['reason']);
    }

    public function testIsMessagingWindowError(): void
    {
        $error = new \Exception('Messaging window expired', 2022);
        $this->assertTrue($this->handler->isMessagingWindowError($error));

        $otherError = new \Exception('Other error', 500);
        $this->assertFalse($this->handler->isMessagingWindowError($otherError));
    }

    public function testIsAccountEligibilityError(): void
    {
        $error = new \Exception('Account not eligible', 36103);
        $this->assertTrue($this->handler->isAccountEligibilityError($error));

        $otherError = new \Exception('Other error', 500);
        $this->assertFalse($this->handler->isAccountEligibilityError($otherError));
    }

    public function testIsFeatureNotAvailableError(): void
    {
        $error = new \Exception('Feature not available', 2534068);
        $this->assertTrue($this->handler->isFeatureNotAvailableError($error));

        $otherError = new \Exception('Other error', 500);
        $this->assertFalse($this->handler->isFeatureNotAvailableError($otherError));
    }

    public function testGetUserFriendlyMessageForMessagingWindow(): void
    {
        $error = new \Exception('Messaging window expired', 2022);
        $message = $this->handler->getUserFriendlyMessage($error);

        $this->assertStringContainsString('24 horas', $message);
        $this->assertStringContainsString('expirada', $message);
    }

    public function testGetUserFriendlyMessageForAccountNotEligible(): void
    {
        $error = new \Exception('Account not eligible', 36103);
        $message = $this->handler->getUserFriendlyMessage($error);

        $this->assertStringContainsString('não elegível', $message);
    }

    public function testGetUserFriendlyMessageForFeatureNotAvailable(): void
    {
        $error = new \Exception('Feature not available', 2534068);
        $message = $this->handler->getUserFriendlyMessage($error);

        $this->assertStringContainsString('não disponível', $message);
    }

    public function testGetUserFriendlyMessageForGenericError(): void
    {
        $error = new \Exception('Some error', 500);
        $message = $this->handler->getUserFriendlyMessage($error);

        $this->assertStringContainsString('Erro ao processar webhook', $message);
    }

    public function testNonRetryableErrorsAddedToDeadLetterQueue(): void
    {
        $error = new \Exception('Account not eligible', 36103);
        $payload = ['object' => 'page', 'entry' => []];

        $this->deadLetterQueue
            ->expects($this->once())
            ->method('add')
            ->with(
                $this->equalTo($payload),
                $this->equalTo('Account not eligible'),
                $this->equalTo(36103),
                $this->equalTo(1),
                $this->anything()
            );

        $this->handler->handleError($error, $payload, 1);
    }

    public function testGetMaxRetryAttempts(): void
    {
        $this->assertEquals(3, $this->handler->getMaxRetryAttempts());
    }

    public function testErrorCodeExtractedFromMessage(): void
    {
        // Error code in message
        $error = new \Exception('Error 36103: Account not eligible');
        $payload = ['object' => 'page', 'entry' => []];

        $result = $this->handler->handleError($error, $payload, 1);

        $this->assertFalse($result['should_retry']);
    }
}

