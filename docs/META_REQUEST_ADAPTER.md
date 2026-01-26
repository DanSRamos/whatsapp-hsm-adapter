# Meta Request Adapter

## Overview

The `MetaRequestAdapter` is a utility class that adapts standard WhatsApp HSM Adapter requests to Meta-compatible formats for Instagram and Facebook Messenger. Since Meta platforms don't support HSM templates and have different constraints than WhatsApp, this adapter handles the necessary conversions and validations.

## Features

- **Template to Text Conversion**: Converts HSM template requests to plain text messages
- **Media Validation**: Validates media URLs, formats, and platform-specific constraints
- **Interactive Message Adaptation**: Adapts button and list requests to Meta's format
- **Comprehensive Logging**: Logs all conversions and validations for debugging
- **Platform-Specific Limits**: Handles different limits for Instagram vs Messenger

## Usage

### Basic Setup

```php
use WhatsApp\Adapter\Providers\Meta\MetaRequestAdapter;
use WhatsApp\Adapter\Providers\Meta\MetaMessageFormatter;
use Psr\Log\LoggerInterface;

// Create dependencies
$logger = // ... your PSR-3 logger
$formatter = new MetaMessageFormatter();

// Create adapter
$adapter = new MetaRequestAdapter($logger, $formatter);
```

### Template Conversion

Convert HSM template requests to text messages:

```php
use WhatsApp\Adapter\Models\Requests\HSMRequest;

// Create template request with placeholders
$templateRequest = new HSMRequest(
    to: '1234567890',
    templateName: 'welcome_message',
    templateLanguage: 'en',
    parameters: [
        'Hello {{1}}, welcome to {{2}}!',  // Template text with placeholders
        'John',                              // {{1}} replacement
        'Meta Platform'                      // {{2}} replacement
    ]
);

// Adapt to text request
$textRequest = $adapter->adaptTemplateRequest($templateRequest);

if ($textRequest !== null) {
    // Result: "Hello John, welcome to Meta Platform!"
    echo $textRequest->text;
}
```

### Media Validation

Validate media requests before sending:

```php
use WhatsApp\Adapter\Models\Requests\MediaRequest;

$mediaRequest = new MediaRequest(
    to: '1234567890',
    mediaType: 'image',
    mediaUrl: 'https://example.com/image.jpg'
);

// Validate for Instagram
$result = $adapter->validateMediaRequest($mediaRequest, 'instagram');

if ($result['valid']) {
    // Media is valid, proceed with sending
    echo "Media is valid!";
} else {
    // Handle errors
    foreach ($result['errors'] as $error) {
        echo "Error: $error\n";
    }
}

// Check warnings (non-blocking issues)
foreach ($result['warnings'] as $warning) {
    echo "Warning: $warning\n";
}
```

### Interactive Buttons Adaptation

Adapt interactive button requests for Meta:

```php
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;

$buttonsRequest = new InteractiveButtonsRequest(
    to: '1234567890',
    bodyText: 'Choose an option',
    buttons: [
        ['id' => 'btn1', 'text' => 'This is a very long button title that exceeds twenty characters'],
        ['id' => 'btn2', 'text' => 'Short']
    ]
);

// Adapt for Meta (truncates long titles to 20 chars)
$result = $adapter->adaptInteractiveButtonsRequest($buttonsRequest, 'instagram');

if ($result['valid']) {
    // Use the adapted request
    $adaptedRequest = $result['adapted_request'];

    // Check for warnings about truncation
    foreach ($result['warnings'] as $warning) {
        echo "Warning: $warning\n";
    }
}
```

### Interactive List Adaptation

Adapt interactive list requests for Meta:

```php
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

$listRequest = new InteractiveListRequest(
    to: '1234567890',
    bodyText: 'Choose from the list',
    buttonText: 'View Options',
    sections: [
        [
            'title' => 'Section 1',
            'items' => [
                [
                    'id' => 'item1',
                    'title' => 'Item 1',
                    'description' => 'Description 1',
                    'buttons' => [
                        ['type' => 'postback', 'title' => 'Select', 'payload' => 'item1']
                    ]
                ]
            ]
        ]
    ]
);

// Adapt for Meta (validates limits and formats)
$result = $adapter->adaptInteractiveListRequest($listRequest, 'instagram');

if ($result['valid']) {
    // List is valid for Meta
    echo "List is valid!";

    // Check for warnings
    foreach ($result['warnings'] as $warning) {
        echo "Warning: $warning\n";
    }
}
```

### Platform-Specific Limits

Get platform-specific media limits:

```php
// Get Instagram limits
$instagramLimits = $adapter->getPlatformMediaLimits('instagram');
echo "Instagram max image size: " . ($instagramLimits['image'] / 1024 / 1024) . " MB\n";
echo "Instagram max images per message: " . $instagramLimits['max_images_per_message'] . "\n";

// Get Messenger limits
$messengerLimits = $adapter->getPlatformMediaLimits('messenger');
echo "Messenger max image size: " . ($messengerLimits['image'] / 1024 / 1024) . " MB\n";
echo "Messenger max images per message: " . $messengerLimits['max_images_per_message'] . "\n";
```

### Logging Conversions

Log conversion summaries for monitoring:

```php
$adapter->logConversionSummary('template', [
    'template_name' => 'welcome_message',
    'converted' => true,
    'original_parameters' => 3,
    'result_length' => 45
]);
```

## Platform Differences

### Instagram vs Messenger

| Feature                   | Instagram | Messenger        |
| ------------------------- | --------- | ---------------- |
| Max Image Size            | 8 MB      | 25 MB            |
| Max Video/Audio Size      | 25 MB     | 25 MB            |
| Max Images per Message    | 10        | 1 (use carousel) |
| Quick Replies             | Up to 13  | Up to 13         |
| Button Title Length       | 20 chars  | 20 chars         |
| Generic Template Elements | Up to 10  | Up to 10         |
| Element Title Length      | 80 chars  | 80 chars         |

### WhatsApp vs Meta

| Feature             | WhatsApp      | Meta (Instagram/Messenger)         |
| ------------------- | ------------- | ---------------------------------- |
| HSM Templates       | ✅ Supported  | ❌ Not supported (convert to text) |
| Interactive Buttons | 3 buttons max | 13 quick replies max               |
| Interactive Lists   | 10 items max  | 10 elements max                    |
| Media URL Protocol  | HTTP/HTTPS    | HTTPS only                         |

## Error Handling

The adapter returns structured validation results:

```php
$result = [
    'valid' => bool,              // Whether the request is valid
    'errors' => array<string>,    // Blocking errors (must fix)
    'warnings' => array<string>,  // Non-blocking warnings (informational)
    'adapted_request' => ?object  // Adapted request (if applicable)
];
```

### Common Errors

1. **Invalid URL**: Media URL is not a valid URL format
2. **HTTP Protocol**: Media URL uses HTTP instead of HTTPS
3. **Unsupported Format**: Media file format is not supported by Meta
4. **Too Many Buttons**: More than 13 quick replies provided
5. **Empty Template**: Template conversion resulted in empty text

### Common Warnings

1. **No File Extension**: Media URL has no file extension (Meta will validate content type)
2. **Title Truncation**: Button or element title exceeds character limit and will be truncated
3. **Too Many Elements**: List has more than 10 elements (extras will be truncated)
4. **Platform Limitation**: Feature has different behavior on Messenger vs Instagram

## Integration with MetaProvider

The adapter is designed to work seamlessly with `MetaProvider`:

```php
use WhatsApp\Adapter\Providers\Meta\MetaProvider;
use WhatsApp\Adapter\Providers\Meta\MetaRequestAdapter;

// Create adapter
$adapter = new MetaRequestAdapter($logger, $formatter);

// Adapt template request
$textRequest = $adapter->adaptTemplateRequest($templateRequest);

if ($textRequest !== null) {
    // Send via MetaProvider
    $result = $metaProvider->sendText($textRequest);
}

// Validate media before sending
$validation = $adapter->validateMediaRequest($mediaRequest, 'instagram');

if ($validation['valid']) {
    // Send via MetaProvider
    $result = $metaProvider->sendMedia($mediaRequest);
} else {
    // Handle validation errors
    foreach ($validation['errors'] as $error) {
        $logger->error('Media validation failed', ['error' => $error]);
    }
}
```

## Best Practices

1. **Always Validate**: Validate media requests before sending to catch errors early
2. **Check Warnings**: Review warnings to understand platform-specific adaptations
3. **Log Conversions**: Use `logConversionSummary()` for monitoring and debugging
4. **Handle Nulls**: Template adaptation may return null if conversion fails
5. **Platform Detection**: Use appropriate platform identifier ('instagram' or 'messenger')
6. **Error Handling**: Always check validation results before proceeding

## Examples

### Complete Template Conversion Flow

```php
// 1. Create template request
$templateRequest = new HSMRequest(
    to: '1234567890',
    templateName: 'order_confirmation',
    templateLanguage: 'en',
    parameters: [
        'Your order {{1}} has been confirmed. Total: {{2}}',
        '#12345',
        '$99.99'
    ]
);

// 2. Adapt to text
$textRequest = $adapter->adaptTemplateRequest($templateRequest);

if ($textRequest === null) {
    $logger->error('Failed to adapt template request');
    return;
}

// 3. Log conversion
$adapter->logConversionSummary('template', [
    'template_name' => $templateRequest->templateName,
    'converted_text' => $textRequest->text,
    'success' => true
]);

// 4. Send via provider
$result = $metaProvider->sendText($textRequest);
```

### Complete Media Validation Flow

```php
// 1. Create media request
$mediaRequest = new MediaRequest(
    to: '1234567890',
    mediaType: 'image',
    mediaUrl: 'https://example.com/product.jpg',
    caption: 'Check out our new product!'
);

// 2. Validate for platform
$validation = $adapter->validateMediaRequest($mediaRequest, 'instagram');

// 3. Handle validation result
if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        $logger->error('Media validation error', ['error' => $error]);
    }
    return;
}

// 4. Log warnings
foreach ($validation['warnings'] as $warning) {
    $logger->warning('Media validation warning', ['warning' => $warning]);
}

// 5. Send via provider
$result = $metaProvider->sendMedia($mediaRequest);
```

## Testing

The adapter includes comprehensive unit tests. Run tests with:

```bash
./vendor/bin/phpunit tests/Unit/Providers/MetaRequestAdapterTest.php
```

## See Also

- [Meta Credentials Setup](META_CREDENTIALS_SETUP.md)
- [Meta Provider Documentation](../src/Providers/Meta/MetaProvider.php)
- [Meta Message Formatter](../src/Providers/Meta/MetaMessageFormatter.php)
