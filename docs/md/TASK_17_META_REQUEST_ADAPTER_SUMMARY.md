# Task 17: Meta Request Adapter Implementation Summary

## Overview

Successfully implemented the `MetaRequestAdapter` class to handle request adaptation and validation for Meta platforms (Instagram and Facebook Messenger). This adapter provides a clean separation of concerns for converting WhatsApp-style requests to Meta-compatible formats.

## Implementation Date

January 19, 2026

## Files Created

### 1. Core Adapter Class

- **File**: `src/Providers/Meta/MetaRequestAdapter.php`
- **Purpose**: Main adapter class for request conversion and validation
- **Lines of Code**: ~550 lines
- **Key Features**:
  - Template to text conversion
  - Media request validation
  - Interactive buttons adaptation
  - Interactive list adaptation
  - Platform-specific limit handling
  - Comprehensive logging

### 2. Unit Tests

- **File**: `tests/Unit/Providers/MetaRequestAdapterTest.php`
- **Purpose**: Comprehensive test coverage for the adapter
- **Test Count**: 19 tests
- **Assertions**: 46 assertions
- **Coverage**: All major functionality tested
- **Status**: ✅ All tests passing

### 3. Documentation

- **File**: `docs/META_REQUEST_ADAPTER.md`
- **Purpose**: Complete usage guide and API documentation
- **Sections**:
  - Overview and features
  - Usage examples for all methods
  - Platform differences (Instagram vs Messenger vs WhatsApp)
  - Error handling guide
  - Best practices
  - Integration examples

## Key Features Implemented

### 1. Template to Text Conversion

Converts HSM template requests to plain text messages since Meta doesn't support HSM templates:

```php
// Input: Template with placeholders
$templateRequest = new HSMRequest(
    to: '1234567890',
    templateName: 'welcome',
    templateLanguage: 'en',
    parameters: ['Hello {{1}}, welcome to {{2}}!', 'John', 'Meta']
);

// Output: Plain text message
$textRequest = $adapter->adaptTemplateRequest($templateRequest);
// Result: "Hello John, welcome to Meta!"
```

**Features**:

- Placeholder substitution ({{1}}, {{2}}, etc.)
- Parameter concatenation for non-template text
- Empty result detection
- Comprehensive logging of conversions

### 2. Media Request Validation

Validates media requests against Meta platform requirements:

```php
$validation = $adapter->validateMediaRequest($mediaRequest, 'instagram');

// Returns:
// - valid: bool
// - errors: array (blocking issues)
// - warnings: array (informational)
```

**Validations**:

- ✅ URL format validation
- ✅ HTTPS protocol enforcement
- ✅ Media type support check
- ✅ File format validation
- ✅ Platform-specific warnings

**Supported Formats**:

- **Images**: jpg, jpeg, png, gif, webp
- **Videos**: mp4, mov, avi, webm, ogg
- **Audio**: mp3, aac, m4a, wav, ogg
- **Documents**: pdf, doc, docx, xls, xlsx, ppt, pptx, txt

### 3. Interactive Buttons Adaptation

Adapts button requests to Meta's quick reply format:

```php
$result = $adapter->adaptInteractiveButtonsRequest($request, 'instagram');

// Returns:
// - valid: bool
// - errors: array
// - warnings: array
// - adapted_request: InteractiveButtonsRequest|null
```

**Features**:

- Button count validation (max 13 for Meta vs 3 for WhatsApp)
- Title length validation (max 20 characters)
- Automatic title truncation with warnings
- Platform-specific adaptations

### 4. Interactive List Adaptation

Adapts list requests to Meta's generic template format:

```php
$result = $adapter->adaptInteractiveListRequest($request, 'instagram');
```

**Features**:

- Element count validation (max 10)
- Title/subtitle length validation (max 80 characters)
- Button per card validation (max 3)
- Truncation warnings for long content

### 5. Platform-Specific Limits

Provides platform-specific media limits:

```php
$limits = $adapter->getPlatformMediaLimits('instagram');
// Returns: [
//   'image' => 8 * 1024 * 1024,      // 8MB
//   'video' => 25 * 1024 * 1024,     // 25MB
//   'max_images_per_message' => 10
// ]

$limits = $adapter->getPlatformMediaLimits('messenger');
// Returns: [
//   'image' => 25 * 1024 * 1024,     // 25MB
//   'video' => 25 * 1024 * 1024,     // 25MB
//   'max_images_per_message' => 1
// ]
```

### 6. Comprehensive Logging

All operations are logged with detailed context:

```php
$adapter->logConversionSummary('template', [
    'template_name' => 'welcome_message',
    'converted' => true,
    'parameters_used' => 3
]);
```

**Logged Information**:

- Template conversions (original text, converted text, parameters)
- Media validations (errors, warnings, formats)
- Button adaptations (truncations, count issues)
- List adaptations (element counts, length issues)

## Platform Differences Handled

### Instagram vs Messenger

| Feature             | Instagram | Messenger |
| ------------------- | --------- | --------- |
| Max Image Size      | 8 MB      | 25 MB     |
| Max Images/Message  | 10        | 1         |
| Quick Replies       | 13        | 13        |
| Button Title Length | 20 chars  | 20 chars  |

### WhatsApp vs Meta

| Feature             | WhatsApp     | Meta               |
| ------------------- | ------------ | ------------------ |
| HSM Templates       | ✅ Supported | ❌ Convert to text |
| Interactive Buttons | 3 max        | 13 max             |
| Media Protocol      | HTTP/HTTPS   | HTTPS only         |

## Error Handling

### Validation Result Structure

```php
[
    'valid' => bool,              // Overall validity
    'errors' => array<string>,    // Blocking errors
    'warnings' => array<string>,  // Non-blocking warnings
    'adapted_request' => ?object  // Adapted request (if applicable)
]
```

### Common Errors Detected

1. **Invalid URL**: Malformed media URL
2. **HTTP Protocol**: Using HTTP instead of HTTPS
3. **Unsupported Format**: File format not supported by Meta
4. **Too Many Buttons**: Exceeding quick reply limit
5. **Empty Template**: Template conversion resulted in empty text

### Common Warnings Generated

1. **No File Extension**: URL has no extension (Meta will validate)
2. **Title Truncation**: Content exceeds character limits
3. **Too Many Elements**: List exceeds element limit
4. **Platform Limitation**: Different behavior on platforms

## Integration Points

### With MetaProvider

The adapter integrates seamlessly with `MetaProvider`:

```php
// Template conversion
$textRequest = $adapter->adaptTemplateRequest($templateRequest);
if ($textRequest) {
    $result = $metaProvider->sendText($textRequest);
}

// Media validation
$validation = $adapter->validateMediaRequest($mediaRequest, 'instagram');
if ($validation['valid']) {
    $result = $metaProvider->sendMedia($mediaRequest);
}
```

### With MetaMessageFormatter

Uses `MetaMessageFormatter` for template text conversion:

```php
$formatter = new MetaMessageFormatter();
$adapter = new MetaRequestAdapter($logger, $formatter);
```

## Test Coverage

### Test Statistics

- **Total Tests**: 19
- **Total Assertions**: 46
- **Pass Rate**: 100%
- **Execution Time**: ~34ms

### Test Categories

1. **Template Conversion Tests** (4 tests)

   - With placeholders
   - Without placeholders
   - Empty parameters
   - Empty result

2. **Media Validation Tests** (6 tests)

   - Valid HTTPS URL
   - HTTP URL (should fail)
   - Invalid URL format
   - Unsupported format
   - No file extension
   - Platform-specific warnings

3. **Interactive Buttons Tests** (3 tests)

   - Valid buttons
   - Long titles (truncation)
   - Button count limits

4. **Interactive List Tests** (3 tests)

   - Valid items
   - Item count limits
   - Long titles/descriptions

5. **Utility Tests** (3 tests)
   - Platform limits (Instagram)
   - Platform limits (Messenger)
   - Logging functionality

## Benefits

### 1. Separation of Concerns

- Request adaptation logic separated from provider logic
- Easier to test and maintain
- Clear responsibility boundaries

### 2. Validation Before Sending

- Catch errors early before API calls
- Reduce failed API requests
- Better error messages for debugging

### 3. Platform Flexibility

- Easy to add new platform-specific rules
- Centralized platform difference handling
- Consistent validation across the application

### 4. Comprehensive Logging

- All conversions logged for debugging
- Warnings help identify potential issues
- Audit trail for request adaptations

### 5. Developer Experience

- Clear API with structured results
- Detailed documentation with examples
- Comprehensive test coverage

## Usage Examples

### Basic Template Conversion

```php
$adapter = new MetaRequestAdapter($logger, $formatter);

$templateRequest = new HSMRequest(
    to: '1234567890',
    templateName: 'greeting',
    templateLanguage: 'en',
    parameters: ['Hello {{1}}!', 'World']
);

$textRequest = $adapter->adaptTemplateRequest($templateRequest);
// Result: TextRequest with text "Hello World!"
```

### Media Validation with Error Handling

```php
$mediaRequest = new MediaRequest(
    to: '1234567890',
    mediaType: 'image',
    mediaUrl: 'https://example.com/image.jpg'
);

$validation = $adapter->validateMediaRequest($mediaRequest, 'instagram');

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        $logger->error('Validation failed', ['error' => $error]);
    }
    return;
}

// Proceed with sending
$result = $metaProvider->sendMedia($mediaRequest);
```

## Future Enhancements

Potential improvements for future iterations:

1. **Size Validation**: Add actual file size checking (currently only validates format)
2. **Async Validation**: Support for asynchronous media URL validation
3. **Caching**: Cache validation results for frequently used media URLs
4. **Batch Validation**: Validate multiple requests in a single call
5. **Custom Rules**: Allow custom validation rules per use case
6. **Metrics**: Add metrics collection for conversion success rates

## Requirements Satisfied

This implementation satisfies the following requirements from the spec:

- ✅ **Requirement 5**: Template to text conversion for Meta platforms
- ✅ **Requirement 3**: Media format validation and conversion
- ✅ **Requirement 4**: Interactive message adaptation
- ✅ **Requirement 14**: Comprehensive logging of conversions

## Conclusion

The `MetaRequestAdapter` successfully provides a robust, well-tested solution for adapting WhatsApp-style requests to Meta platform requirements. The implementation includes:

- ✅ Complete functionality for all required adaptations
- ✅ Comprehensive test coverage (100% pass rate)
- ✅ Detailed documentation with examples
- ✅ Platform-specific handling for Instagram and Messenger
- ✅ Extensive logging for debugging and monitoring
- ✅ Clean separation of concerns
- ✅ Easy integration with existing MetaProvider

The adapter is production-ready and can be used immediately to handle request conversions for Meta messaging platforms.

## Related Files

- Implementation: `src/Providers/Meta/MetaRequestAdapter.php`
- Tests: `tests/Unit/Providers/MetaRequestAdapterTest.php`
- Documentation: `docs/META_REQUEST_ADAPTER.md`
- Formatter: `src/Providers/Meta/MetaMessageFormatter.php`
- Provider: `src/Providers/Meta/MetaProvider.php`
