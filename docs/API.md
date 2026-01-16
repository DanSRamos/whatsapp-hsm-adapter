# WhatsApp HSM Adapter - API Documentation

## Overview

O WhatsApp HSM Adapter é uma API RESTful que fornece uma camada de abstração unificada para integração com múltiplos provedores de WhatsApp (Infobip, Twilio, etc.). A API permite enviar e receber mensagens WhatsApp, incluindo HSM (Highly Structured Messages), mensagens de texto livre, media, e mensagens interativas.

## Base URL

```
https://your-domain.com/api
```

## Authentication

Todas as requisições à API (exceto webhooks e health check) devem incluir autenticação via API key no header:

```
Authorization: Bearer YOUR_API_KEY
```

## Content Type

Todas as requisições e respostas usam JSON:

```
Content-Type: application/json
```

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      "field": "field_name",
      "reason": "Detailed reason"
    }
  },
  "timestamp": "2026-01-16T10:30:00Z",
  "requestId": "req_abc123"
}
```

### HTTP Status Codes

| Code | Description                               |
| ---- | ----------------------------------------- |
| 200  | Success                                   |
| 400  | Bad Request - Invalid parameters          |
| 401  | Unauthorized - Invalid or missing API key |
| 403  | Forbidden - Insufficient permissions      |
| 404  | Not Found - Resource not found            |
| 429  | Too Many Requests - Rate limit exceeded   |
| 500  | Internal Server Error                     |
| 503  | Service Unavailable - Temporary issue     |

### Error Codes

| Code                   | Description                            |
| ---------------------- | -------------------------------------- |
| `VALIDATION_ERROR`     | Invalid or missing required parameters |
| `AUTHENTICATION_ERROR` | Invalid API key or credentials         |
| `NOT_FOUND`            | Resource not found                     |
| `RATE_LIMIT_EXCEEDED`  | Too many requests                      |
| `PROVIDER_ERROR`       | Error from WhatsApp provider           |
| `INTERNAL_ERROR`       | Unexpected server error                |

---

## Endpoints

### Health Check

#### GET /health

Verifica o status do serviço e suas dependências.

**Authentication:** Not required

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
      "message": "All providers accessible",
      "providers": {
        "infobip": {
          "healthy": true,
          "message": "Provider accessible"
        },
        "twilio": {
          "healthy": true,
          "message": "Provider accessible"
        }
      }
    }
  }
}
```

**Status Codes:**

- `200` - All services healthy
- `503` - One or more services unhealthy

---

## Template Management

### GET /api/templates

Recupera todos os templates HSM disponíveis.

**Authentication:** Required

**Query Parameters:**

| Parameter  | Type   | Required | Description                                    |
| ---------- | ------ | -------- | ---------------------------------------------- |
| `provider` | string | No       | Filter by provider (infobip, twilio)           |
| `status`   | string | No       | Filter by status (approved, pending, rejected) |

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": "template_123",
      "name": "welcome_message",
      "language": "pt",
      "status": "approved",
      "category": "MARKETING",
      "components": [
        {
          "type": "BODY",
          "text": "Olá {{1}}, bem-vindo à nossa plataforma!",
          "parameters": ["name"]
        }
      ]
    }
  ]
}
```

---

### GET /api/templates/{templateId}

Recupera um template específico por ID.

**Authentication:** Required

**Path Parameters:**

| Parameter    | Type   | Required | Description |
| ------------ | ------ | -------- | ----------- |
| `templateId` | string | Yes      | Template ID |

**Response:**

```json
{
  "success": true,
  "data": {
    "id": "template_123",
    "name": "welcome_message",
    "language": "pt",
    "status": "approved",
    "category": "MARKETING",
    "components": [
      {
        "type": "BODY",
        "text": "Olá {{1}}, bem-vindo à nossa plataforma!",
        "parameters": ["name"]
      }
    ]
  }
}
```

**Error Responses:**

- `404` - Template not found

---

### POST /api/templates/sync

Sincroniza templates manualmente do provedor para a base de dados local.

**Authentication:** Required

**Query Parameters:**

| Parameter  | Type   | Required | Description                           |
| ---------- | ------ | -------- | ------------------------------------- |
| `provider` | string | No       | Sync specific provider (default: all) |

**Response:**

```json
{
  "success": true,
  "data": {
    "added": 5,
    "updated": 3,
    "deleted": 1,
    "total": 27
  }
}
```

---

## Message Sending

### POST /api/messages/hsm

Envia uma mensagem HSM (template).

**Authentication:** Required

**Request Body:**

```json
{
  "to": "+351912345678",
  "templateName": "welcome_message",
  "templateLanguage": "pt",
  "parameters": ["João"],
  "provider": "infobip"
}
```

**Parameters:**

| Field              | Type   | Required | Description                                   |
| ------------------ | ------ | -------- | --------------------------------------------- |
| `to`               | string | Yes      | Recipient phone number (E.164 format)         |
| `templateName`     | string | Yes      | Template name                                 |
| `templateLanguage` | string | Yes      | Template language code (e.g., "pt", "en")     |
| `parameters`       | array  | No       | Template parameter values                     |
| `provider`         | string | No       | Provider to use (default: configured default) |
| `notifyUrl`        | string | No       | Webhook URL for delivery notifications        |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc123",
    "status": "PENDING",
    "to": "+351912345678"
  }
}
```

**Error Responses:**

- `400` - Missing or invalid parameters
- `404` - Template not found

---

### POST /api/messages/text

Envia uma mensagem de texto livre.

**Authentication:** Required

**Request Body:**

```json
{
  "to": "+351912345678",
  "text": "Olá! Como posso ajudar?",
  "previewUrl": true,
  "provider": "infobip"
}
```

**Parameters:**

| Field        | Type    | Required | Description                            |
| ------------ | ------- | -------- | -------------------------------------- |
| `to`         | string  | Yes      | Recipient phone number (E.164 format)  |
| `text`       | string  | Yes      | Message text                           |
| `previewUrl` | boolean | No       | Enable URL preview (default: false)    |
| `provider`   | string  | No       | Provider to use                        |
| `notifyUrl`  | string  | No       | Webhook URL for delivery notifications |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc124",
    "status": "PENDING",
    "to": "+351912345678"
  }
}
```

---

### POST /api/messages/media

Envia media (imagem, documento, áudio, vídeo).

**Authentication:** Required

**Request Body:**

```json
{
  "to": "+351912345678",
  "mediaType": "image",
  "mediaUrl": "https://example.com/image.jpg",
  "caption": "Veja esta imagem",
  "provider": "infobip"
}
```

**Parameters:**

| Field       | Type   | Required | Description                            |
| ----------- | ------ | -------- | -------------------------------------- |
| `to`        | string | Yes      | Recipient phone number                 |
| `mediaType` | string | Yes      | Type: image, document, audio, video    |
| `mediaUrl`  | string | Yes      | URL of the media file                  |
| `caption`   | string | No       | Media caption (for images/videos)      |
| `filename`  | string | No       | Filename (for documents)               |
| `provider`  | string | No       | Provider to use                        |
| `notifyUrl` | string | No       | Webhook URL for delivery notifications |

**Supported Formats:**

| Media Type | Formats                   | Max Size |
| ---------- | ------------------------- | -------- |
| Image      | JPEG, PNG                 | 5 MB     |
| Document   | PDF, DOC, DOCX, XLS, XLSX | 100 MB   |
| Audio      | MP3, OGG, AMR             | 16 MB    |
| Video      | MP4, 3GP                  | 16 MB    |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc125",
    "status": "PENDING",
    "to": "+351912345678"
  }
}
```

**Error Responses:**

- `400` - Invalid media format or size

---

### POST /api/messages/interactive/buttons

Envia mensagem com botões interativos.

**Authentication:** Required

**Request Body:**

```json
{
  "to": "+351912345678",
  "bodyText": "Escolha uma opção:",
  "buttons": [
    {
      "id": "btn_1",
      "text": "Opção 1"
    },
    {
      "id": "btn_2",
      "text": "Opção 2"
    }
  ],
  "headerText": "Menu Principal",
  "footerText": "Powered by WhatsApp",
  "provider": "infobip"
}
```

**Parameters:**

| Field            | Type   | Required | Description                            |
| ---------------- | ------ | -------- | -------------------------------------- |
| `to`             | string | Yes      | Recipient phone number                 |
| `bodyText`       | string | Yes      | Main message text                      |
| `buttons`        | array  | Yes      | Array of buttons (max 3)               |
| `buttons[].id`   | string | Yes      | Unique button ID                       |
| `buttons[].text` | string | Yes      | Button text                            |
| `headerText`     | string | No       | Header text                            |
| `footerText`     | string | No       | Footer text                            |
| `provider`       | string | No       | Provider to use                        |
| `notifyUrl`      | string | No       | Webhook URL for delivery notifications |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc126",
    "status": "PENDING",
    "to": "+351912345678"
  }
}
```

**Error Responses:**

- `400` - Too many buttons (max 3) or invalid button data

---

### POST /api/messages/interactive/list

Envia mensagem com lista interativa.

**Authentication:** Required

**Request Body:**

```json
{
  "to": "+351912345678",
  "bodyText": "Escolha um produto:",
  "buttonText": "Ver Produtos",
  "sections": [
    {
      "title": "Categoria 1",
      "items": [
        {
          "id": "item_1",
          "title": "Produto 1",
          "description": "Descrição do produto 1"
        },
        {
          "id": "item_2",
          "title": "Produto 2",
          "description": "Descrição do produto 2"
        }
      ]
    }
  ],
  "headerText": "Catálogo",
  "footerText": "Powered by WhatsApp",
  "provider": "infobip"
}
```

**Parameters:**

| Field                            | Type   | Required | Description                            |
| -------------------------------- | ------ | -------- | -------------------------------------- |
| `to`                             | string | Yes      | Recipient phone number                 |
| `bodyText`                       | string | Yes      | Main message text                      |
| `buttonText`                     | string | Yes      | List button text                       |
| `sections`                       | array  | Yes      | Array of sections                      |
| `sections[].title`               | string | Yes      | Section title                          |
| `sections[].items`               | array  | Yes      | Array of items (max 10 total)          |
| `sections[].items[].id`          | string | Yes      | Unique item ID                         |
| `sections[].items[].title`       | string | Yes      | Item title                             |
| `sections[].items[].description` | string | No       | Item description                       |
| `headerText`                     | string | No       | Header text                            |
| `footerText`                     | string | No       | Footer text                            |
| `provider`                       | string | No       | Provider to use                        |
| `notifyUrl`                      | string | No       | Webhook URL for delivery notifications |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc127",
    "status": "PENDING",
    "to": "+351912345678"
  }
}
```

**Error Responses:**

- `400` - Too many items (max 10) or invalid list data

---

### GET /api/messages/{messageId}/status

Consulta o status de uma mensagem enviada.

**Authentication:** Required

**Path Parameters:**

| Parameter   | Type   | Required | Description |
| ----------- | ------ | -------- | ----------- |
| `messageId` | string | Yes      | Message ID  |

**Response:**

```json
{
  "success": true,
  "data": {
    "messageId": "msg_abc123",
    "status": "DELIVERED",
    "to": "+351912345678",
    "sentAt": "2026-01-16T10:30:00Z",
    "deliveredAt": "2026-01-16T10:30:15Z",
    "readAt": "2026-01-16T10:31:00Z"
  }
}
```

**Status Values:**

- `PENDING` - Message queued for sending
- `SENT` - Message sent to provider
- `DELIVERED` - Message delivered to recipient
- `READ` - Message read by recipient
- `FAILED` - Message delivery failed

**Error Responses:**

- `404` - Message not found

---

## Webhooks

Os webhooks são usados para receber notificações assíncronas do provedor WhatsApp. Configure os URLs dos webhooks no painel do provedor.

### Webhook Authentication

Todos os webhooks são validados usando assinatura HMAC. O adapter verifica automaticamente a autenticidade dos webhooks recebidos.

### POST /webhooks/delivery-reports

Recebe relatórios de entrega de mensagens.

**Authentication:** HMAC signature validation

**Request Body (Infobip):**

```json
{
  "results": [
    {
      "messageId": "msg_abc123",
      "to": "+351912345678",
      "status": {
        "groupId": 3,
        "groupName": "DELIVERED",
        "id": 5,
        "name": "DELIVERED_TO_HANDSET",
        "description": "Message delivered to handset"
      },
      "sentAt": "2026-01-16T10:30:00.000+0000",
      "doneAt": "2026-01-16T10:30:15.000+0000"
    }
  ]
}
```

**Response:**

```json
{
  "success": true
}
```

---

### POST /webhooks/incoming-messages

Recebe mensagens enviadas por clientes.

**Authentication:** HMAC signature validation

**Request Body (Infobip):**

```json
{
  "results": [
    {
      "messageId": "msg_incoming_123",
      "from": "+351912345678",
      "to": "447860099299",
      "receivedAt": "2026-01-16T10:35:00.000+0000",
      "message": {
        "type": "TEXT",
        "text": "Olá, preciso de ajuda"
      },
      "contact": {
        "name": "João Silva"
      }
    }
  ]
}
```

**Message Types:**

- `TEXT` - Text message
- `IMAGE` - Image with optional caption
- `DOCUMENT` - Document file
- `AUDIO` - Audio file
- `VIDEO` - Video file
- `BUTTON` - Button response
- `LIST` - List item selection

**Response:**

```json
{
  "success": true
}
```

---

### POST /webhooks/template-updates

Recebe notificações de alterações em templates.

**Authentication:** HMAC signature validation

**Request Body (Infobip):**

```json
{
  "results": [
    {
      "templateId": "template_123",
      "name": "welcome_message",
      "language": "pt",
      "status": "APPROVED",
      "category": "MARKETING",
      "updatedAt": "2026-01-16T10:00:00.000+0000"
    }
  ]
}
```

**Status Values:**

- `APPROVED` - Template approved and ready to use
- `PENDING` - Template pending approval
- `REJECTED` - Template rejected
- `PAUSED` - Template paused
- `DELETED` - Template deleted

**Response:**

```json
{
  "success": true
}
```

---

## Rate Limiting

A API implementa rate limiting para prevenir abuso:

- **100 requisições por minuto** por IP
- **1000 requisições por hora** por API key

Quando o limite é excedido, a API retorna:

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please try again later.",
    "details": {
      "retryAfter": 60
    }
  }
}
```

**Headers:**

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1642329600
Retry-After: 60
```

---

## Multi-Provider Support

O adapter suporta múltiplos provedores WhatsApp. Você pode:

1. **Usar o provedor padrão** (configurado em `WHATSAPP_PROVIDER`)
2. **Especificar o provedor** em cada requisição usando o parâmetro `provider`

**Exemplo:**

```json
{
  "to": "+351912345678",
  "text": "Olá!",
  "provider": "twilio"
}
```

**Provedores Suportados:**

- `infobip` - Infobip WhatsApp API
- `twilio` - Twilio WhatsApp API

---

## Best Practices

### Phone Number Format

Sempre use o formato E.164 para números de telefone:

```
+[country code][number]
```

**Exemplos:**

- Portugal: `+351912345678`
- Brasil: `+5511987654321`
- EUA: `+14155551234`

### Error Handling

Implemente retry com backoff exponencial para erros temporários (5xx, timeouts):

```javascript
async function sendWithRetry(payload, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await sendMessage(payload);
    } catch (error) {
      if (error.status >= 500 || error.code === "TIMEOUT") {
        const delay = Math.pow(2, i) * 1000; // 1s, 2s, 4s
        await sleep(delay);
        continue;
      }
      throw error; // Don't retry 4xx errors
    }
  }
  throw new Error("Max retries exceeded");
}
```

### Webhook Handling

- Responda rapidamente aos webhooks (< 200ms) para evitar retries
- Processe webhooks de forma assíncrona usando filas
- Valide sempre a assinatura HMAC dos webhooks
- Implemente idempotência para lidar com webhooks duplicados

### Template Management

- Sincronize templates periodicamente (ex: a cada hora)
- Cache templates localmente para reduzir chamadas à API
- Invalide o cache quando receber webhook de template update

---

## Code Examples

### Node.js

```javascript
const axios = require("axios");

const client = axios.create({
  baseURL: "https://your-domain.com/api",
  headers: {
    Authorization: "Bearer YOUR_API_KEY",
    "Content-Type": "application/json",
  },
});

// Send HSM
async function sendHSM() {
  const response = await client.post("/messages/hsm", {
    to: "+351912345678",
    templateName: "welcome_message",
    templateLanguage: "pt",
    parameters: ["João"],
  });
  console.log("Message ID:", response.data.data.messageId);
}

// Get message status
async function getStatus(messageId) {
  const response = await client.get(`/messages/${messageId}/status`);
  console.log("Status:", response.data.data.status);
}
```

### PHP

```php
<?php

$client = new GuzzleHttp\Client([
    'base_uri' => 'https://your-domain.com/api',
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json'
    ]
]);

// Send HSM
$response = $client->post('/messages/hsm', [
    'json' => [
        'to' => '+351912345678',
        'templateName' => 'welcome_message',
        'templateLanguage' => 'pt',
        'parameters' => ['João']
    ]
]);

$data = json_decode($response->getBody(), true);
echo "Message ID: " . $data['data']['messageId'];
```

### Python

```python
import requests

client = requests.Session()
client.headers.update({
    'Authorization': 'Bearer YOUR_API_KEY',
    'Content-Type': 'application/json'
})

base_url = 'https://your-domain.com/api'

# Send HSM
response = client.post(f'{base_url}/messages/hsm', json={
    'to': '+351912345678',
    'templateName': 'welcome_message',
    'templateLanguage': 'pt',
    'parameters': ['João']
})

data = response.json()
print(f"Message ID: {data['data']['messageId']}")
```

---

## Support

Para suporte técnico ou questões sobre a API, contacte:

- Email: support@example.com
- Documentation: https://docs.example.com
- Status Page: https://status.example.com
