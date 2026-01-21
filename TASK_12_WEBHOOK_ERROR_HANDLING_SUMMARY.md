# Task 12: Webhook Error Handling Implementation Summary

## Overview

Successfully implemented comprehensive webhook error handling for the Meta Messaging Integration, including retry logic, dead letter queue, and specialized error handling for Meta-specific error codes.

## Components Implemented

### 1. WebhookErrorHandler (`src/Services/WebhookErrorHandler.php`)

A specialized service for handling webhook processing errors with intelligent retry logic.

**Key Features:**

- **Meta-Specific Error Detection**: Identifies and handles Meta API error codes:

  - `36103`: Account not eligible
  - `2534068`: Feature not available
  - `2022`: Messaging window expired (24h)
  - `551`: User not available

- **Retry Logic**:

  - Maximum 3 retry attempts
  - Exponential backoff: 1s, 2s, 4s, etc.
  - Maximum delay capped at 30 seconds
  - Non-retryable errors immediately added to dead letter queue

- **User-Friendly Messages**: Provides Portuguese error messages for common Meta errors

- **Critical Error Alerting**: Identifies critical errors that require immediate attention

**Methods:**

- `handleError()`: Main error handling with retry decision
- `isMessagingWindowError()`: Check for 24h window violations
- `isAccountEligibilityError()`: Check for account eligibility issues
- `isFeatureNotAvailableError()`: Check for feature availability
- `getUserFriendlyMessage()`: Get localized error messages

### 2. DeadLetterQueue (`src/Services/DeadLetterQueue.php`)

A persistent queue for failed webhooks that couldn't be processed after all retry attempts.

**Key Features:**

- **Persistent Storage**: Uses existing `webhook_logs` table
- **Metadata Tracking**: Stores error codes, attempt numbers, and additional context
- **Retry Capability**: Allows manual reprocessing of failed webhooks
- **Statistics**: Provides counts by type and overall failed count
- **Cleanup**: Automatic cleanup of old processed webhooks

**Methods:**

- `add()`: Add failed webhook to queue
- `getFailedWebhooks()`: Retrieve failed webhooks with pagination
- `getById()`: Get specific webhook by ID
- `markAsProcessed()`: Mark webhook as successfully processed
- `retry()`: Retry processing a failed webhook
- `getFailedCount()`: Get count of failed webhooks
- `getFailedCountByType()`: Get counts grouped by webhook type
- `cleanupOldWebhooks()`: Remove old processed webhooks

### 3. MetaWebhookHandler Updates

Enhanced the existing webhook handler with error handling integration.

**New Methods:**

- `processWithErrorHandling()`: Process webhook with automatic retry logic
- `isMessagingWindowError()`: Delegate to error handler
- `isAccountEligibilityError()`: Delegate to error handler
- `getUserFriendlyErrorMessage()`: Delegate to error handler

**Constructor Update:**

- Added optional `WebhookErrorHandler` parameter for dependency injection

### 4. WebhookController Updates

Updated the webhook controller to use the new error handling infrastructure.

**Changes:**

- Added `WebhookErrorHandler` and `DeadLetterQueue` as optional dependencies
- Implemented `processMetaWebhookWithRetry()` method for automatic retry logic
- Updated `handleMetaWebhookEvent()` to use retry logic
- Always returns 200 to Meta to prevent their automatic retries (we handle retries internally)

## Error Handling Flow

```
Webhook Received
    ↓
Validate Signature
    ↓
Parse Payload
    ↓
Process Webhook (Attempt 1)
    ↓
[Error Occurs]
    ↓
WebhookErrorHandler.handleError()
    ↓
Is Error Retryable?
    ├─ No → Add to Dead Letter Queue → Return 200
    └─ Yes → Calculate Delay → Wait → Retry
                ↓
        Process Webhook (Attempt 2)
                ↓
        [Error Occurs Again]
                ↓
        Is Max Attempts Reached?
            ├─ Yes → Add to Dead Letter Queue → Return 200
            └─ No → Calculate Delay → Wait → Retry
```

## Testing

### Unit Tests Created

1. **WebhookErrorHandlerTest.php** (16 tests, 32 assertions)

   - Error code detection and handling
   - Retry logic with exponential backoff
   - Max attempts enforcement
   - Dead letter queue integration
   - User-friendly message generation

2. **DeadLetterQueueTest.php** (17 tests, 33 assertions)

   - Adding webhooks to queue
   - Retrieving failed webhooks with pagination
   - Marking webhooks as processed
   - Retry functionality
   - Statistics and counts
   - Cleanup of old webhooks

3. **MetaWebhookHandlerTest.php** (35 tests, 55 assertions)
   - Updated with new error handling tests
   - Process with error handling
   - Error checking method delegation

**Total: 68 tests, 120 assertions - All passing ✓**

## Database Schema

Uses existing `webhook_logs` table:

```sql
CREATE TABLE webhook_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_type (type),
    INDEX idx_processed (processed),
    INDEX idx_received_at (received_at)
);
```

## Error Codes Handled

| Error Code | Description              | Action                         |
| ---------- | ------------------------ | ------------------------------ |
| 36103      | Account not eligible     | No retry, add to DLQ, alert    |
| 2534068    | Feature not available    | No retry, add to DLQ, alert    |
| 2022       | Messaging window expired | No retry, add to DLQ           |
| 551        | User not available       | No retry, add to DLQ           |
| Others     | Transient errors         | Retry with exponential backoff |

## Configuration

### Retry Configuration

- **Max Retries**: 3 attempts
- **Initial Delay**: 1 second
- **Max Delay**: 30 seconds
- **Backoff Strategy**: Exponential (1s, 2s, 4s, 8s, ...)

### Dead Letter Queue

- **Retention**: Configurable (default 30 days for processed)
- **Storage**: MySQL/MariaDB via PDO
- **Cleanup**: Manual or automated via cron

## Usage Example

```php
// Create services
$errorHandler = new WebhookErrorHandler($logger, $deadLetterQueue);
$webhookHandler = new MetaWebhookHandler($logger, $errorHandler);

// Process webhook with automatic retry
$result = $webhookHandler->processWithErrorHandling(
    $payload,
    function($payload) {
        // Your processing logic here
        processWebhook($payload);
    },
    $attemptNumber
);

if (!$result['success']) {
    if ($result['should_retry']) {
        // Will be retried automatically
        usleep($result['delay_ms'] * 1000);
    } else {
        // Added to dead letter queue
        // Can be manually retried later
    }
}
```

## Benefits

1. **Resilience**: Automatic retry for transient errors
2. **Observability**: All failed webhooks logged and trackable
3. **Recovery**: Manual retry capability for failed webhooks
4. **User Experience**: Friendly error messages in Portuguese
5. **Monitoring**: Statistics and counts for failed webhooks
6. **Compliance**: Proper handling of Meta API restrictions
7. **Performance**: Exponential backoff prevents API overload

## Next Steps

The following tasks can now be implemented:

- **Task 13**: Implement message status queries
- **Task 14**: Implement template management (adapted)
- **Task 15**: Update WhatsAppProviderFactory
- **Task 16**: Adapt MessageService

## Files Created/Modified

### Created:

- `src/Services/WebhookErrorHandler.php`
- `src/Services/DeadLetterQueue.php`
- `tests/Unit/Services/WebhookErrorHandlerTest.php`
- `tests/Unit/Services/DeadLetterQueueTest.php`

### Modified:

- `src/Providers/Meta/MetaWebhookHandler.php`
- `src/Http/Controllers/WebhookController.php`
- `tests/Unit/Providers/MetaWebhookHandlerTest.php`

## Validation

All requirements from the task have been met:

✅ Treat messages outside the 24h window (Error 2022)
✅ Treat ineligible accounts (Error 36103)
✅ Implement retry logic for webhooks (exponential backoff)
✅ Add dead letter queue for failures (persistent storage)
✅ Requirements: Webhook resilience

Task 12 is now **COMPLETE**.
