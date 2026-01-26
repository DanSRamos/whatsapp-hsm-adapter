# Multi-Platform Messaging Adapter - API Documentation

## Overview

The Multi-Platform Messaging Adapter provides a RESTful API for sending and managing messages across multiple platforms:

- **WhatsApp** (via Infobip, Twilio)
- **Instagram** (via Meta Messenger Platform API)
- **Facebook Messenger** (via Meta Messenger Platform API)

This document describes all available endpoints, request/response formats, error codes, and platform-specific considerations.

## OpenAPI Specification

This API is fully documented using **OpenAPI 3.0** specification:

- **Specification File**: [`openapi.yaml`](openapi.yaml)
- **Quick Start Guide**: [`OPENAPI_QUICK_START.md`](OPENAPI_QUICK_START.md)

### Interactive Documentation

View and test the API using:

- **Swagger UI**: Interactive API explorer with "Try it out" functionality
- **ReDoc**: Clean, three-panel documentation
- **Postman**: Import the OpenAPI spec to create a collection

See the [Quick Start Guide](OPENAPI_QUICK_START.md) for setup instructions.

### Generate Client Libraries

Use the OpenAPI specification to generate client libraries for:

- JavaScript/TypeScript
- PHP
- Python
- Java
- Go
- And 50+ other languages

See the [Quick Start Guide](OPENAPI_QUICK_START.md) for generation instructions.

## Base URL

```
https://your-domain.com/api
```

## Authentication

All API requests must include an API key in the `Authorization` header:

```
Authorization: Bearer YOUR_API_KEY
```

### Platform-Specific Authentication

#### WhatsApp (Infobip/Twilio)

- Uses API key authentication as shown above
- Provider credentials configured server-side

#### Instagram & Facebook Messenger (Meta)

- Uses Page Access Token (configured server-side)
- Requires Facebook App ID and App Secret
- Requires Facebook Page ID
- See [Meta Credentials Setup](META_CREDENTIALS_SETUP.md) for details

## Rate Limiting

- Default: 100 requests per minute per API key
- Rate limit headers are included in all responses:
  - `X-RateLimit-Limit`: Maximum requests allowed
  - `X-RateLimit-Remaining`: Remaining requests in current window
  - `X-RateLimit-Reset`: Unix timestamp when the limit resets

### Platform-Specific Rate Limits

| Platform           | Rate Limit   | Notes                 |
| ------------------ | ------------ | --------------------- |
| WhatsApp (Infobip) | 100 req/min  | Per API key           |
| WhatsApp (Twilio)  | 100 req/min  | Per API key           |
| Instagram          | 200 req/hour | Per Page Access Token |
| Facebook Messenger | 200 req/hour | Per Page Access Token |

## Platform Comparison

### Feature Support Matrix

| Feature                 | WhatsApp                   | Instagram                      | Facebook Messenger             |
| ----------------------- | -------------------------- | ------------------------------ | ------------------------------ |
| **Text Messages**       | ✅ Yes                     | ✅ Yes                         | ✅ Yes                         |
| **Media (Images)**      | ✅ Yes (1 per msg)         | ✅ Yes (up to 10)              | ✅ Yes (1 per msg)             |
| **Media (Video)**       | ✅ Yes                     | ✅ Yes                         | ✅ Yes                         |
| **Media (Audio)**       | ✅ Yes                     | ✅ Yes                         | ✅ Yes                         |
| **Media (Documents)**   | ✅ Yes                     | ✅ Yes                         | ✅ Yes                         |
| **HSM Templates**       | ✅ Yes                     | ❌ No (converted to text)      | ❌ No (converted to text)      |
| **Interactive Buttons** | ✅ Yes (max 3)             | ✅ Yes (Quick Replies, max 13) | ✅ Yes (Quick Replies, max 13) |
| **Interactive Lists**   | ✅ Yes (max 10 items)      | ✅ Yes (Generic Template)      | ✅ Yes (Generic Template)      |
| **Button Template**     | ❌ No                      | ❌ No                          | ✅ Yes (max 3 buttons)         |
| **Recipient ID Format** | Phone number (+1234567890) | IGSID (numeric string)         | PSID (numeric string)          |
| **Messaging Window**    | 24 hours                   | 24 hours                       | 24 hours                       |
| **Status Tracking**     | ✅ Real-time via webhook   | ✅ Via webhook only            | ✅ Via webhook only            |
| **Read Receipts**       | ✅ Yes                     | ✅ Yes                         | ✅ Yes                         |

### Media Size Limits

| Media Type    | WhatsApp | Instagram | Facebook Messenger |
| ------------- | -------- | --------- | ------------------ |
| **Images**    | 5 MB     | 8 MB      | 25 MB              |
| **Videos**    | 16 MB    | 25 MB     | 25 MB              |
| **Audio**     | 16 MB    | 25 MB     | 25 MB              |
| **Documents** | 100 MB   | 25 MB     | 25 MB              |

### Key Differences

#### WhatsApp

- Requires approved HSM templates for initiating conversations
- 24-hour session window after user message
- Phone number-based identification
- Supports document sharing with large file sizes

#### Instagram

- No template support (templates converted to plain text)
- 24-hour messaging window after user message
- IGSID (Instagram-Scoped ID) for user identification
- Supports up to 10 images in a single message
- Requires Instagram Professional/Business account

#### Facebook Messenger

- No template support (templates converted to plain text)
- 24-hour messaging window after user message
- PSID (Page-Scoped ID) for user identification
- Supports Button Template (specific to Messenger)
- Uses same API as Instagram (Meta Messenger Platform)

## Common Response Format

### Success Response

```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": { ... }
  }
}
```

## Endpoints

### Health Check

#### GET /health

Check the health status of the adapter and its dependencies.

**Response:**

```json
{
  "status": "healthy",
  "timestamp": "2026-01-16T10:30:00Z",
  "checks": {
    "database": {
      "healthy": true,
      "message": "Database connection successful"
    },
    "redis": {
      "healthy": true,
      "message": "Redis connection successful"
    },
    "providers": {
      "healthy": true,
      "message": "All providers healthy",
      "providers": {
        "infobip": {
          "healthy": true,
          "message": "Provider connection successful"
        },
        "twilio": {
          "healthy": true,
          "message": "Provider connection successful"
        }
      }
    }
  }
}
```

**Status Codes:**

- `200 OK`: All systems healthy
- `503 Service Unavailable`: One or more systems unhealthy

---

### Templates

#### GET /api/templates

Retrieve all available WhatsApp message templates.

**Query Parameters:**

- `provider` (optional): Filter by provider name (e.g., `infobip`, `twilio`)

**Response:**

```json
{
  "success": true,
  "data": {
    "templates": [
      {
        "id": "template_123",
        "name": "welcome_message",
        "language": "en",
        "status": "APPROVED",
        "category": "MARKETING",
        "components": [
          {
            "type": "BODY",
            "text": "Hello {{1}}, welcome to our service!",
            "parameters": ["name"]
          }
        ]
      }
    ]
  }
}
```

**Status Codes:**

- `200 OK`: Templates retrieved successfully
- `401 Unauthorized`: Invalid or missing API key
- `500 Internal Server Error`: Provider API error

---

#### GET /api/templates/{templateId}

Retrieve a specific template by ID.

**Path Parameters:**

- `templateId`: The unique identifier of the template

**Response:**

```json
{
  "success": true,
  "data": {
    "id": "template_123",
    "name": "welcome_message",
    "language": "en",
    "status": "APPROVED",
    "category": "MARKETING",
    "components": [
      {
        "type": "BODY",
        "text": "Hello {{1}}, welcome to our service!",
        "parameters": ["name"]
      }
    ]
  }
}
```

**Status Codes:**

- `200 OK`: Template found
- `404 Not Found`: Template not found
- `401 Unauthorized`: Invalid or missing API key

---

#### POST /api/templates/sync

Manually synchronize templates from provider(s) to local database.

**Query Parameters:**

- `provider` (optional): Sync specific provider (default: all providers)

**Response:**

```json
{
  "success": true,
  "data": {
    "synced": {
      "infobip": {
        "added": 5,
        "updated": 3,
        "deleted": 1
      },
      "twilio": {
        "added": 2,
        "updated": 1,
        "deleted": 0
      }
    }
  }
}
```

**Status Codes:**

- `200 OK`: Sync completed successfully
- `401 Unauthorized`: Invalid or missing API key
- `500 Internal Server Error`: Sync failed

---

### Messages

#### POST /api/messages/hsm

Send a Highly Structured Message (HSM) using an approved template.

**Request Body:**

```json
{
  "to": "+1234567890",
  "templateId": "template_123",
  "parameters": {
    "name": "John Doe"
  },
  "provider": "infobip"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc123",
    "status": "PENDING",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:30:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Message sent successfully
- `400 Bad Request`: Invalid parameters
- `401 Unauthorized`: Invalid or missing API key
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Provider API error

---

#### POST /api/messages/text

Send a free-form text message.

**Request Body:**

```json
{
  "to": "+1234567890",
  "text": "Hello! How can I help you today?",
  "provider": "infobip"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc124",
    "status": "PENDING",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:31:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Message sent successfully
- `400 Bad Request`: Invalid parameters
- `401 Unauthorized`: Invalid or missing API key
- `403 Forbidden`: Session expired (use HSM to start new session)
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Provider API error

---

#### POST /api/messages/media

Send media (image, document, audio, or video).

**Request Body:**

```json
{
  "to": "+1234567890",
  "type": "image",
  "url": "https://example.com/image.jpg",
  "caption": "Check out this image!",
  "provider": "infobip"
}
```

**Supported Media Types:**

- `image`: JPEG, PNG (max 5MB)
- `document`: PDF, DOC, DOCX, XLS, XLSX (max 100MB)
- `audio`: MP3, OGG, AMR (max 16MB, max 30 minutes)
- `video`: MP4, 3GP (max 16MB)

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc125",
    "status": "PENDING",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:32:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Media sent successfully
- `400 Bad Request`: Invalid media type or size
- `401 Unauthorized`: Invalid or missing API key
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Provider API error

---

#### POST /api/messages/interactive/buttons

Send an interactive message with buttons (max 3 buttons).

**Request Body:**

```json
{
  "to": "+1234567890",
  "body": "Please select an option:",
  "buttons": [
    {
      "id": "btn_1",
      "text": "Option 1"
    },
    {
      "id": "btn_2",
      "text": "Option 2"
    },
    {
      "id": "btn_3",
      "text": "Option 3"
    }
  ],
  "provider": "infobip"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc126",
    "status": "PENDING",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:33:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Message sent successfully
- `400 Bad Request`: Invalid button count or format
- `401 Unauthorized`: Invalid or missing API key
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Provider API error

---

#### POST /api/messages/interactive/list

Send an interactive message with a list (max 10 items).

**Request Body:**

```json
{
  "to": "+1234567890",
  "body": "Please select from the menu:",
  "buttonText": "View Menu",
  "sections": [
    {
      "title": "Main Dishes",
      "rows": [
        {
          "id": "item_1",
          "title": "Pizza",
          "description": "Delicious cheese pizza"
        },
        {
          "id": "item_2",
          "title": "Burger",
          "description": "Classic beef burger"
        }
      ]
    }
  ],
  "provider": "infobip"
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc127",
    "status": "PENDING",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:34:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Message sent successfully
- `400 Bad Request`: Invalid list item count or format
- `401 Unauthorized`: Invalid or missing API key
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Provider API error

---

#### GET /api/messages/{messageId}/status

Query the status of a sent message.

**Path Parameters:**

- `messageId`: The unique identifier of the message

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc123",
    "status": "DELIVERED",
    "to": "+1234567890",
    "sentAt": "2026-01-16T10:30:00Z",
    "deliveredAt": "2026-01-16T10:30:15Z",
    "readAt": null
  }
}
```

**Message Statuses:**

- `PENDING`: Message queued for sending
- `SENT`: Message sent to provider
- `DELIVERED`: Message delivered to recipient
- `READ`: Message read by recipient
- `FAILED`: Message delivery failed

**Status Codes:**

- `200 OK`: Status retrieved successfully
- `404 Not Found`: Message not found
- `401 Unauthorized`: Invalid or missing API key
- `500 Internal Server Error`: Provider API error

---

## Meta Platform Messages (Instagram & Facebook Messenger)

### POST /api/messages/meta/text

Send a text message via Instagram or Facebook Messenger.

**Request Body:**

```json
{
  "to": "1234567890",
  "text": "Hello! How can I help you today?",
  "provider": "meta",
  "platform": "instagram"
}
```

**Parameters:**

- `to` (required): IGSID (Instagram) or PSID (Messenger) - numeric string
- `text` (required): Message text (max 2000 characters)
- `provider` (required): Must be "meta"
- `platform` (optional): "instagram" or "messenger" (auto-detected if omitted)

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc123",
    "status": "SENT",
    "to": "1234567890",
    "platform": "instagram",
    "sentAt": "2026-01-16T10:31:00Z",
    "messagingWindowExpiresAt": "2026-01-17T10:31:00Z"
  }
}
```

**Status Codes:**

- `200 OK`: Message sent successfully
- `400 Bad Request`: Invalid IGSID/PSID or parameters
- `403 Forbidden`: 24-hour messaging window expired
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Meta API error

**Error Examples:**

```json
{
  "success": false,
  "error": {
    "code": "MESSAGING_WINDOW_EXPIRED",
    "message": "Cannot send message outside 24-hour window",
    "details": {
      "lastUserMessage": "2026-01-15T10:31:00Z",
      "windowExpired": "2026-01-16T10:31:00Z"
    }
  }
}
```

---

### POST /api/messages/meta/media

Send media via Instagram or Facebook Messenger.

**Request Body:**

```json
{
  "to": "1234567890",
  "type": "image",
  "url": "https://example.com/image.jpg",
  "provider": "meta",
  "platform": "instagram"
}
```

**Supported Media Types:**

- `image`: JPEG, PNG (max 8MB for Instagram, 25MB for Messenger)
- `video`: MP4, OGG, AVI, MOV, WEBM (max 25MB)
- `audio`: AAC, M4A, WAV, MP4 (max 25MB)
- `file`: PDF (max 25MB)

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc124",
    "status": "SENT",
    "to": "1234567890",
    "platform": "instagram",
    "mediaType": "image",
    "sentAt": "2026-01-16T10:32:00Z"
  }
}
```

---

### POST /api/messages/meta/multiple-images

Send multiple images in a single message (Instagram only, max 10 images).

**Request Body:**

```json
{
  "to": "1234567890",
  "images": [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg",
    "https://example.com/image3.jpg"
  ],
  "provider": "meta",
  "platform": "instagram"
}
```

**Limitations:**

- Instagram: Up to 10 images per message
- Messenger: Use single image endpoint (1 image per message)

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc125",
    "status": "SENT",
    "to": "1234567890",
    "platform": "instagram",
    "imageCount": 3,
    "sentAt": "2026-01-16T10:33:00Z"
  }
}
```

---

### POST /api/messages/meta/quick-replies

Send a message with quick reply buttons (Instagram & Messenger).

**Request Body:**

```json
{
  "to": "1234567890",
  "text": "Please select an option:",
  "quickReplies": [
    {
      "title": "Option 1",
      "payload": "option_1"
    },
    {
      "title": "Option 2",
      "payload": "option_2"
    },
    {
      "title": "Option 3",
      "payload": "option_3"
    }
  ],
  "provider": "meta",
  "platform": "instagram"
}
```

**Limitations:**

- Maximum 13 quick replies per message
- Title max length: 20 characters
- Payload max length: 1000 characters

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc126",
    "status": "SENT",
    "to": "1234567890",
    "platform": "instagram",
    "quickReplyCount": 3,
    "sentAt": "2026-01-16T10:34:00Z"
  }
}
```

---

### POST /api/messages/meta/generic-template

Send a Generic Template with cards (Instagram & Messenger).

**Request Body:**

```json
{
  "to": "1234567890",
  "elements": [
    {
      "title": "Product 1",
      "subtitle": "Description of product 1",
      "imageUrl": "https://example.com/product1.jpg",
      "buttons": [
        {
          "type": "web_url",
          "title": "View Details",
          "url": "https://example.com/product1"
        },
        {
          "type": "postback",
          "title": "Buy Now",
          "payload": "buy_product_1"
        }
      ]
    },
    {
      "title": "Product 2",
      "subtitle": "Description of product 2",
      "imageUrl": "https://example.com/product2.jpg",
      "buttons": [
        {
          "type": "web_url",
          "title": "View Details",
          "url": "https://example.com/product2"
        }
      ]
    }
  ],
  "provider": "meta",
  "platform": "instagram"
}
```

**Limitations:**

- Maximum 10 elements (cards)
- Maximum 3 buttons per element
- Title max length: 80 characters
- Subtitle max length: 80 characters

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc127",
    "status": "SENT",
    "to": "1234567890",
    "platform": "instagram",
    "elementCount": 2,
    "sentAt": "2026-01-16T10:35:00Z"
  }
}
```

---

### POST /api/messages/meta/button-template

Send a Button Template (Messenger only).

**Request Body:**

```json
{
  "to": "1234567890",
  "text": "What would you like to do?",
  "buttons": [
    {
      "type": "web_url",
      "title": "Visit Website",
      "url": "https://example.com"
    },
    {
      "type": "postback",
      "title": "Contact Support",
      "payload": "contact_support"
    },
    {
      "type": "phone_number",
      "title": "Call Us",
      "payload": "+1234567890"
    }
  ],
  "provider": "meta",
  "platform": "messenger"
}
```

**Limitations:**

- Messenger only (not supported on Instagram)
- Maximum 3 buttons
- Button types: `web_url`, `postback`, `phone_number`
- Title max length: 20 characters

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc128",
    "status": "SENT",
    "to": "1234567890",
    "platform": "messenger",
    "buttonCount": 3,
    "sentAt": "2026-01-16T10:36:00Z"
  }
}
```

---

### GET /api/messages/meta/{messageId}/status

Query the status of a Meta message (Instagram or Messenger).

**Path Parameters:**

- `messageId`: The unique identifier of the message

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_meta_abc123",
    "status": "DELIVERED",
    "platform": "instagram",
    "to": "1234567890",
    "sentAt": "2026-01-16T10:30:00Z",
    "deliveredAt": "2026-01-16T10:30:15Z",
    "readAt": "2026-01-16T10:30:45Z"
  }
}
```

**Note:** Meta platforms only provide status updates via webhooks. This endpoint returns the last known status from the local database.

---

## Meta Platform Webhooks

### POST /webhooks/meta

Unified webhook endpoint for Instagram and Facebook Messenger events.

**Webhook Verification (GET):**

Meta sends a GET request to verify your webhook:

```
GET /webhooks/meta?hub.mode=subscribe&hub.verify_token=YOUR_VERIFY_TOKEN&hub.challenge=CHALLENGE_STRING
```

**Response:**

```
CHALLENGE_STRING
```

**Webhook Events (POST):**

Meta sends POST requests for various events:

#### Incoming Message Event

```json
{
  "object": "page",
  "entry": [
    {
      "id": "PAGE_ID",
      "time": 1642334400000,
      "messaging": [
        {
          "sender": {
            "id": "1234567890"
          },
          "recipient": {
            "id": "PAGE_ID"
          },
          "timestamp": 1642334400000,
          "message": {
            "mid": "msg_id_123",
            "text": "Hello, I need help"
          }
        }
      ]
    }
  ]
}
```

#### Instagram-Specific Event

```json
{
  "object": "instagram",
  "entry": [
    {
      "id": "INSTAGRAM_ACCOUNT_ID",
      "time": 1642334400000,
      "messaging": [
        {
          "sender": {
            "id": "1234567890"
          },
          "recipient": {
            "id": "INSTAGRAM_ACCOUNT_ID"
          },
          "timestamp": 1642334400000,
          "message": {
            "mid": "msg_id_123",
            "text": "Hello from Instagram"
          }
        }
      ]
    }
  ]
}
```

#### Delivery Report Event

```json
{
  "object": "page",
  "entry": [
    {
      "id": "PAGE_ID",
      "time": 1642334415000,
      "messaging": [
        {
          "sender": {
            "id": "1234567890"
          },
          "recipient": {
            "id": "PAGE_ID"
          },
          "delivery": {
            "mids": ["msg_id_123"],
            "watermark": 1642334415000
          }
        }
      ]
    }
  ]
}
```

#### Read Receipt Event

```json
{
  "object": "page",
  "entry": [
    {
      "id": "PAGE_ID",
      "time": 1642334445000,
      "messaging": [
        {
          "sender": {
            "id": "1234567890"
          },
          "recipient": {
            "id": "PAGE_ID"
          },
          "read": {
            "watermark": 1642334445000
          }
        }
      ]
    }
  ]
}
```

**Webhook Security:**

All Meta webhooks include an `X-Hub-Signature-256` header for verification:

```
X-Hub-Signature-256: sha256=HMAC_SHA256_SIGNATURE
```

The adapter automatically validates this signature using your App Secret.

---

## Meta Platform Error Codes

| Error Code | Description                        | Handling                               |
| ---------- | ---------------------------------- | -------------------------------------- |
| `36103`    | Account not eligible for messaging | Permanent error - check account status |
| `2534068`  | Feature not available              | Permanent error - feature not enabled  |
| `10`       | Permission denied                  | Check app permissions                  |
| `100`      | Invalid parameter                  | Validate request parameters            |
| `190`      | Invalid access token               | Refresh Page Access Token              |
| `200`      | Permission error                   | Check pages_messaging permission       |
| `551`      | User not available                 | User may have blocked page             |
| `2022`     | Messaging window expired           | Wait for user message or use tags      |
| `613`      | Rate limit exceeded                | Implement backoff and retry            |

### Meta-Specific Error Response

```json
{
  "success": false,
  "error": {
    "code": "META_API_ERROR",
    "message": "Meta API returned an error",
    "details": {
      "metaErrorCode": 36103,
      "metaErrorMessage": "This account is not eligible to receive messages",
      "metaErrorType": "OAuthException",
      "traceId": "AaBbCcDdEeFfGg"
    }
  }
}
```

---

### Webhooks

Webhooks are used to receive notifications from WhatsApp providers. Configure your webhook URLs in the provider dashboard.

#### POST /webhooks/delivery-reports

Receive delivery status updates for sent messages.

**Webhook Payload Example:**

```json
{
  "messageId": "msg_abc123",
  "status": "DELIVERED",
  "timestamp": "2026-01-16T10:30:15Z",
  "error": null
}
```

**Response:**

```json
{
  "success": true
}
```

---

#### POST /webhooks/incoming-messages

Receive messages sent by customers.

**Webhook Payload Example:**

```json
{
  "messageId": "incoming_msg_123",
  "from": "+1234567890",
  "to": "+0987654321",
  "type": "text",
  "content": {
    "text": "Hello, I need help"
  },
  "timestamp": "2026-01-16T10:35:00Z"
}
```

**Response:**

```json
{
  "success": true
}
```

---

#### POST /webhooks/template-updates

Receive notifications when templates are approved, rejected, or deleted.

**Webhook Payload Example:**

```json
{
  "templateId": "template_123",
  "name": "welcome_message",
  "status": "APPROVED",
  "timestamp": "2026-01-16T10:36:00Z"
}
```

**Response:**

```json
{
  "success": true
}
```

---

## Error Codes

| Code                   | Description                        |
| ---------------------- | ---------------------------------- |
| `INVALID_REQUEST`      | Request validation failed          |
| `UNAUTHORIZED`         | Invalid or missing API key         |
| `NOT_FOUND`            | Resource not found                 |
| `RATE_LIMIT_EXCEEDED`  | Too many requests                  |
| `PROVIDER_ERROR`       | Provider API error                 |
| `VALIDATION_ERROR`     | Parameter validation failed        |
| `TEMPLATE_NOT_FOUND`   | Template ID not found              |
| `INVALID_PHONE_NUMBER` | Phone number format invalid        |
| `MEDIA_TOO_LARGE`      | Media file exceeds size limit      |
| `INVALID_MEDIA_TYPE`   | Media type not supported           |
| `SESSION_EXPIRED`      | WhatsApp session expired (use HSM) |
| `INTERNAL_ERROR`       | Internal server error              |

## Retry Logic

The adapter automatically retries failed requests with exponential backoff:

- Maximum 3 retry attempts
- Initial delay: 1 second
- Exponential backoff multiplier: 2
- Respects `Retry-After` header from provider
- No retry for permanent errors (4xx except 429)

## Webhook Security

All webhooks are validated using HMAC signatures:

1. Provider sends webhook with signature header
2. Adapter validates signature using configured secret
3. Invalid signatures are rejected with 401 Unauthorized

Configure webhook secrets in your `.env` file:

```
INFOBIP_WEBHOOK_SECRET=your_secret_here
TWILIO_WEBHOOK_SECRET=your_secret_here
```

## Examples

### WhatsApp Examples

#### Send HSM with cURL

```bash
curl -X POST https://your-domain.com/api/messages/hsm \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+1234567890",
    "templateId": "template_123",
    "parameters": {
      "name": "John Doe"
    },
    "provider": "infobip"
  }'
```

#### Send Text Message with PHP

```php
$client = new GuzzleHttp\Client();

$response = $client->post('https://your-domain.com/api/messages/text', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'to' => '+1234567890',
        'text' => 'Hello from PHP!',
        'provider' => 'infobip',
    ],
]);

$data = json_decode($response->getBody(), true);
echo "Message ID: " . $data['data']['messageId'];
```

#### Check Message Status with JavaScript

```javascript
fetch("https://your-domain.com/api/messages/msg_abc123/status", {
  headers: {
    Authorization: "Bearer YOUR_API_KEY",
  },
})
  .then((response) => response.json())
  .then((data) => {
    console.log("Status:", data.data.status);
  });
```

---

### Instagram Examples

#### Send Text Message to Instagram User

```bash
curl -X POST https://your-domain.com/api/messages/meta/text \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "1234567890",
    "text": "Hello from Instagram!",
    "provider": "meta",
    "platform": "instagram"
  }'
```

#### Send Multiple Images (Instagram)

```javascript
const response = await fetch(
  "https://your-domain.com/api/messages/meta/multiple-images",
  {
    method: "POST",
    headers: {
      Authorization: "Bearer YOUR_API_KEY",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      to: "1234567890",
      images: [
        "https://example.com/image1.jpg",
        "https://example.com/image2.jpg",
        "https://example.com/image3.jpg",
      ],
      provider: "meta",
      platform: "instagram",
    }),
  }
);

const data = await response.json();
console.log("Message ID:", data.data.messageId);
```

#### Send Quick Replies (Instagram)

```python
import requests

url = "https://your-domain.com/api/messages/meta/quick-replies"
headers = {
    "Authorization": "Bearer YOUR_API_KEY",
    "Content-Type": "application/json"
}
payload = {
    "to": "1234567890",
    "text": "What would you like to do?",
    "quickReplies": [
        {"title": "View Products", "payload": "view_products"},
        {"title": "Contact Support", "payload": "contact_support"},
        {"title": "Track Order", "payload": "track_order"}
    ],
    "provider": "meta",
    "platform": "instagram"
}

response = requests.post(url, json=payload, headers=headers)
print(f"Message ID: {response.json()['data']['messageId']}")
```

#### Send Generic Template (Instagram)

```php
$client = new GuzzleHttp\Client();

$response = $client->post('https://your-domain.com/api/messages/meta/generic-template', [
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'to' => '1234567890',
        'elements' => [
            [
                'title' => 'Summer Collection',
                'subtitle' => 'Check out our latest arrivals',
                'imageUrl' => 'https://example.com/summer.jpg',
                'buttons' => [
                    [
                        'type' => 'web_url',
                        'title' => 'Shop Now',
                        'url' => 'https://example.com/shop'
                    ],
                    [
                        'type' => 'postback',
                        'title' => 'Learn More',
                        'payload' => 'learn_more_summer'
                    ]
                ]
            ]
        ],
        'provider' => 'meta',
        'platform' => 'instagram',
    ],
]);

$data = json_decode($response->getBody(), true);
echo "Message ID: " . $data['data']['messageId'];
```

---

### Facebook Messenger Examples

#### Send Text Message to Messenger User

```bash
curl -X POST https://your-domain.com/api/messages/meta/text \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "9876543210",
    "text": "Hello from Messenger!",
    "provider": "meta",
    "platform": "messenger"
  }'
```

#### Send Button Template (Messenger Only)

```javascript
const response = await fetch(
  "https://your-domain.com/api/messages/meta/button-template",
  {
    method: "POST",
    headers: {
      Authorization: "Bearer YOUR_API_KEY",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      to: "9876543210",
      text: "How can we help you today?",
      buttons: [
        {
          type: "web_url",
          title: "Visit Website",
          url: "https://example.com",
        },
        {
          type: "postback",
          title: "Contact Support",
          payload: "contact_support",
        },
        {
          type: "phone_number",
          title: "Call Us",
          payload: "+1234567890",
        },
      ],
      provider: "meta",
      platform: "messenger",
    }),
  }
);

const data = await response.json();
console.log("Message ID:", data.data.messageId);
```

#### Send Media (Messenger)

```python
import requests

url = "https://your-domain.com/api/messages/meta/media"
headers = {
    "Authorization": "Bearer YOUR_API_KEY",
    "Content-Type": "application/json"
}
payload = {
    "to": "9876543210",
    "type": "video",
    "url": "https://example.com/video.mp4",
    "provider": "meta",
    "platform": "messenger"
}

response = requests.post(url, json=payload, headers=headers)
print(f"Message ID: {response.json()['data']['messageId']}")
```

---

### Multi-Platform Example

#### Send to Multiple Platforms

```javascript
// Function to send message to multiple platforms
async function sendMultiPlatform(message) {
  const platforms = [
    { provider: "infobip", to: "+1234567890", type: "whatsapp" },
    { provider: "meta", to: "1234567890", platform: "instagram" },
    { provider: "meta", to: "9876543210", platform: "messenger" },
  ];

  const results = await Promise.all(
    platforms.map(async (platform) => {
      const endpoint =
        platform.type === "whatsapp"
          ? "/api/messages/text"
          : "/api/messages/meta/text";

      const payload = {
        to: platform.to,
        text: message,
        provider: platform.provider,
      };

      if (platform.platform) {
        payload.platform = platform.platform;
      }

      const response = await fetch(`https://your-domain.com${endpoint}`, {
        method: "POST",
        headers: {
          Authorization: "Bearer YOUR_API_KEY",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      return response.json();
    })
  );

  return results;
}

// Usage
sendMultiPlatform("Hello from all platforms!").then((results) => {
  results.forEach((result, index) => {
    console.log(`Platform ${index + 1}:`, result.data.messageId);
  });
});
```

## Additional Resources

### Platform-Specific Documentation

- **WhatsApp**: See provider documentation (Infobip, Twilio)
- **Instagram**: [Instagram Setup Guide](INSTAGRAM_SETUP.md)
- **Facebook Messenger**: [Instagram Setup Guide](INSTAGRAM_SETUP.md) (uses same Meta API)
- **Meta Credentials**: [Meta Credentials Setup](META_CREDENTIALS_SETUP.md)
- **Troubleshooting**: [Troubleshooting Guide](TROUBLESHOOTING.md)

### External Documentation

- [Meta Messenger Platform API](https://developers.facebook.com/docs/messenger-platform)
- [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram)
- [WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)

## Support

For issues or questions:

- GitHub Issues: https://github.com/your-repo/issues
- Email: support@your-domain.com
- Documentation: https://your-domain.com/docs

---

## WhatsApp Number Validation

### GET /api/whatsapp/check-number

Check if a phone number has WhatsApp before sending a message.

**Query Parameters:**

- `phoneNumber` (required): Phone number in E.164 format (e.g., +351912345678)
- `provider` (optional): Provider to use for validation (default: `infobip`)
  - `infobip`: Uses Infobip WhatsApp Contacts API (accurate)
  - `twilio`: Uses Twilio Lookup API (less accurate, based on line type)

**Request Example:**

```bash
curl -X GET "https://your-domain.com/api/whatsapp/check-number?phoneNumber=%2B351912345678&provider=infobip" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**Success Response (Has WhatsApp):**

```json
{
  "success": true,
  "data": {
    "phoneNumber": "+351912345678",
    "hasWhatsApp": true,
    "accountType": "consumer",
    "error": null,
    "provider": "infobip",
    "metadata": {
      "type": "consumer",
      "status": "active"
    }
  }
}
```

**Success Response (No WhatsApp):**

```json
{
  "success": true,
  "data": {
    "phoneNumber": "+351912345678",
    "hasWhatsApp": false,
    "accountType": null,
    "error": null,
    "provider": "infobip",
    "metadata": {
      "reason": "Contact not found in WhatsApp"
    }
  }
}
```

**Success Response (Uncertain - Twilio):**

```json
{
  "success": true,
  "data": {
    "phoneNumber": "+351912345678",
    "hasWhatsApp": null,
    "accountType": "unknown",
    "error": null,
    "provider": "twilio",
    "metadata": {
      "line_type": "mobile",
      "carrier": "Vodafone",
      "note": "Twilio cannot definitively confirm WhatsApp availability. This is based on line type."
    }
  }
}
```

**Error Response:**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_PHONE_FORMAT",
    "message": "Invalid phone number format. Use E.164 format (e.g., +351912345678)"
  }
}
```

**Status Codes:**

- `200 OK`: Validation completed (check `hasWhatsApp` field)
- `400 Bad Request`: Invalid phone number format or missing parameters
- `404 Not Found`: Provider not found or not configured
- `500 Internal Server Error`: Validation failed

**Response Fields:**

- `phoneNumber`: The phone number that was validated
- `hasWhatsApp`: Boolean or null
  - `true`: Number has WhatsApp
  - `false`: Number does not have WhatsApp
  - `null`: Cannot determine (provider limitation or error)
- `accountType`: Type of WhatsApp account
  - `consumer`: Personal WhatsApp account
  - `business`: WhatsApp Business account
  - `unknown`: Cannot determine
  - `null`: Not applicable
- `error`: Error message if validation failed
- `provider`: Provider used for validation
- `metadata`: Additional provider-specific information

---

### POST /api/whatsapp/check-numbers

Batch check multiple phone numbers for WhatsApp availability.

**Request Body:**

```json
{
  "phoneNumbers": ["+351912345678", "+351987654321", "+351123456789"],
  "provider": "infobip"
}
```

**Parameters:**

- `phoneNumbers` (required): Array of phone numbers in E.164 format (max 100)
- `provider` (optional): Provider to use for validation (default: `infobip`)

**Request Example:**

```bash
curl -X POST https://your-domain.com/api/whatsapp/check-numbers \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "phoneNumbers": ["+351912345678", "+351987654321"],
    "provider": "infobip"
  }'
```

**Success Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "phoneNumber": "+351912345678",
        "hasWhatsApp": true,
        "accountType": "consumer",
        "error": null,
        "provider": "infobip",
        "metadata": {
          "type": "consumer",
          "status": "active"
        }
      },
      {
        "phoneNumber": "+351987654321",
        "hasWhatsApp": false,
        "accountType": null,
        "error": null,
        "provider": "infobip",
        "metadata": {
          "reason": "Contact not found in WhatsApp"
        }
      }
    ],
    "total": 2,
    "provider": "infobip"
  }
}
```

**Error Response:**

```json
{
  "success": false,
  "error": {
    "code": "BATCH_SIZE_EXCEEDED",
    "message": "Too many phone numbers. Maximum 100 per request."
  }
}
```

**Status Codes:**

- `200 OK`: Batch validation completed
- `400 Bad Request`: Invalid parameters or batch size exceeded
- `404 Not Found`: Provider not found or not configured
- `500 Internal Server Error`: Batch validation failed

---

### WhatsApp Number Validation Examples

#### Check Single Number with JavaScript

```javascript
async function checkWhatsAppNumber(phoneNumber) {
  const response = await fetch(
    `https://your-domain.com/api/whatsapp/check-number?phoneNumber=${encodeURIComponent(
      phoneNumber
    )}&provider=infobip`,
    {
      headers: {
        Authorization: "Bearer YOUR_API_KEY",
      },
    }
  );

  const data = await response.json();

  if (data.success && data.data.hasWhatsApp === true) {
    console.log(`✓ ${phoneNumber} has WhatsApp`);
    return true;
  } else if (data.success && data.data.hasWhatsApp === false) {
    console.log(`✗ ${phoneNumber} does not have WhatsApp`);
    return false;
  } else {
    console.log(`? Cannot determine if ${phoneNumber} has WhatsApp`);
    return null;
  }
}

// Usage
checkWhatsAppNumber("+351912345678");
```

#### Batch Check with PHP

```php
<?php

function checkWhatsAppNumbers(array $phoneNumbers, string $provider = 'infobip'): array
{
    $client = new GuzzleHttp\Client();

    $response = $client->post('https://your-domain.com/api/whatsapp/check-numbers', [
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'phoneNumbers' => $phoneNumbers,
            'provider' => $provider,
        ],
    ]);

    $data = json_decode($response->getBody(), true);

    if ($data['success']) {
        return $data['data']['results'];
    }

    throw new Exception('Batch validation failed: ' . $data['error']['message']);
}

// Usage
$numbers = ['+351912345678', '+351987654321', '+351123456789'];
$results = checkWhatsAppNumbers($numbers);

foreach ($results as $result) {
    $status = $result['hasWhatsApp'] === true ? '✓ Has WhatsApp' :
              ($result['hasWhatsApp'] === false ? '✗ No WhatsApp' : '? Unknown');

    echo "{$result['phoneNumber']}: {$status}\n";
}
```

#### Check Before Sending HSM

```python
import requests

def send_hsm_if_whatsapp(phone_number, template_id, parameters):
    """Send HSM only if number has WhatsApp"""

    # First, check if number has WhatsApp
    check_url = f"https://your-domain.com/api/whatsapp/check-number"
    check_params = {
        "phoneNumber": phone_number,
        "provider": "infobip"
    }
    check_headers = {
        "Authorization": "Bearer YOUR_API_KEY"
    }

    check_response = requests.get(check_url, params=check_params, headers=check_headers)
    check_data = check_response.json()

    if not check_data['success']:
        print(f"Validation failed: {check_data['error']['message']}")
        return None

    has_whatsapp = check_data['data']['hasWhatsApp']

    if has_whatsapp is False:
        print(f"{phone_number} does not have WhatsApp. Skipping message.")
        return None

    if has_whatsapp is None:
        print(f"Cannot determine if {phone_number} has WhatsApp. Proceeding anyway...")

    # Number has WhatsApp (or uncertain), proceed with sending
    send_url = "https://your-domain.com/api/messages/hsm"
    send_payload = {
        "to": phone_number,
        "templateId": template_id,
        "parameters": parameters,
        "provider": "infobip"
    }
    send_headers = {
        "Authorization": "Bearer YOUR_API_KEY",
        "Content-Type": "application/json"
    }

    send_response = requests.post(send_url, json=send_payload, headers=send_headers)
    send_data = send_response.json()

    if send_data['success']:
        print(f"✓ Message sent to {phone_number}: {send_data['data']['messageId']}")
        return send_data['data']['messageId']
    else:
        print(f"✗ Failed to send message: {send_data['error']['message']}")
        return None

# Usage
send_hsm_if_whatsapp(
    phone_number="+351912345678",
    template_id="template_123",
    parameters={"name": "João"}
)
```

#### Filter WhatsApp Numbers from List

```javascript
async function filterWhatsAppNumbers(phoneNumbers) {
  const response = await fetch(
    "https://your-domain.com/api/whatsapp/check-numbers",
    {
      method: "POST",
      headers: {
        Authorization: "Bearer YOUR_API_KEY",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        phoneNumbers: phoneNumbers,
        provider: "infobip",
      }),
    }
  );

  const data = await response.json();

  if (!data.success) {
    throw new Error(`Validation failed: ${data.error.message}`);
  }

  // Filter only numbers that have WhatsApp
  const whatsappNumbers = data.data.results
    .filter((result) => result.hasWhatsApp === true)
    .map((result) => result.phoneNumber);

  console.log(
    `Found ${whatsappNumbers.length} WhatsApp numbers out of ${phoneNumbers.length}`
  );

  return whatsappNumbers;
}

// Usage
const allNumbers = [
  "+351912345678",
  "+351987654321",
  "+351123456789",
  "+351555555555",
];

filterWhatsAppNumbers(allNumbers).then((whatsappNumbers) => {
  console.log("WhatsApp numbers:", whatsappNumbers);

  // Now send messages only to WhatsApp numbers
  whatsappNumbers.forEach((number) => {
    // Send message...
  });
});
```

---

### Provider Comparison for Number Validation

| Feature                   | Infobip                              | Twilio                                                |
| ------------------------- | ------------------------------------ | ----------------------------------------------------- |
| **Accuracy**              | ✅ High (direct WhatsApp API)        | ⚠️ Medium (based on line type)                        |
| **Definitive Result**     | ✅ Yes (true/false)                  | ⚠️ No (returns null for mobile numbers)               |
| **Account Type**          | ✅ Yes (consumer/business)           | ❌ No                                                 |
| **API Endpoint**          | `/whatsapp/1/contacts/{phoneNumber}` | `/v2/PhoneNumbers/{phoneNumber}?Fields=line_type_...` |
| **Rate Limits**           | Standard Infobip limits              | Standard Twilio Lookup limits                         |
| **Cost**                  | May incur charges                    | May incur charges (Lookup API)                        |
| **Recommended For**       | Production use, accurate validation  | Development/testing, approximate validation           |
| **Response Time**         | Fast (~200-500ms)                    | Fast (~200-500ms)                                     |
| **Batch Support**         | ✅ Yes (via batch endpoint)          | ✅ Yes (via batch endpoint)                           |
| **Business Account Info** | ✅ Yes                               | ❌ No                                                 |

**Recommendation**: Use **Infobip** for production environments where accurate WhatsApp validation is critical. Use **Twilio** for development or when approximate validation is sufficient.

---

### Use Cases for Number Validation

1. **Pre-send Validation**

   - Check if number has WhatsApp before sending HSM
   - Avoid wasting HSM template sends on non-WhatsApp numbers
   - Reduce costs by filtering invalid numbers

2. **Contact List Cleaning**

   - Batch validate entire contact database
   - Identify which contacts can receive WhatsApp messages
   - Segment contacts by WhatsApp availability

3. **User Onboarding**

   - Validate user's phone number during registration
   - Offer WhatsApp as communication channel only if available
   - Provide alternative channels for non-WhatsApp users

4. **Campaign Planning**

   - Estimate reach before launching WhatsApp campaign
   - Calculate expected delivery rates
   - Plan fallback strategies for non-WhatsApp numbers

5. **Cost Optimization**
   - Avoid sending messages to invalid numbers
   - Reduce failed message attempts
   - Optimize messaging budget

---

### Best Practices

1. **Cache Results**

   - Cache validation results for 24-48 hours
   - Reduce API calls and costs
   - Update cache periodically

2. **Handle Uncertain Results**

   - For `hasWhatsApp: null`, decide whether to send or skip
   - Consider fallback channels (SMS, email)
   - Log uncertain cases for analysis

3. **Batch Processing**

   - Use batch endpoint for multiple numbers
   - Process in chunks of 100 numbers
   - Implement retry logic for failed batches

4. **Error Handling**

   - Handle validation errors gracefully
   - Implement fallback to sending without validation
   - Log errors for monitoring

5. **Rate Limiting**

   - Respect provider rate limits
   - Implement exponential backoff
   - Use queue for large batches

6. **Privacy**
   - Don't log full phone numbers
   - Mask numbers in logs (e.g., +351\*\*\*678)
   - Comply with GDPR and privacy regulations

---
