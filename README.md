# Multi-Platform Messaging Adapter

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Version](https://img.shields.io/badge/version-2.0.0-orange)
![Status](https://img.shields.io/badge/status-active-success)

A PHP adapter for integrating with multiple messaging platform APIs (WhatsApp, Instagram, Facebook Messenger, and RCS). Provides a unified abstraction layer for sending and receiving messages across different platforms, including HSM (Highly Structured Messages), free-form text, media, interactive messages, and voice calls.

## 🚀 New in v2.0.0: WhatsApp Voice Calls

Make voice calls via WhatsApp using Infobip API! Check the [Calls Setup Guide](docs/CALLS_SETUP.md) for details.

⚠️ **Important**: Requires Infobip account with Voice/Calls service activated. See [troubleshooting guide](docs/CALLS_TROUBLESHOOTING.md) if you get "Unauthorized access" error.

## Features

### WhatsApp & HSM

- ✅ **HSM/Template messages** - Send approved WhatsApp Business templates
- ✅ **Template synchronization** - Automatic sync of approved templates
- ✅ **Template parameters** - Dynamic content in HSM messages
- ✅ **Multi-provider support** - Infobip, Twilio for WhatsApp
- ✅ **Free-form text** - Send text messages within 24-hour window
- ✅ **Media messages** - Images, documents, audio, video (WhatsApp limits apply)
- ✅ **Interactive buttons** - Up to 3 quick reply buttons
- ✅ **List messages** - Interactive lists for WhatsApp
- ✅ **Voice Calls** 🆕 - Make WhatsApp calls via Infobip API (requires Voice service)

### Instagram & Facebook Messenger

- ✅ **Text messages** - Free-form messaging
- ✅ **Quick Replies** - Up to 13 quick reply buttons
- ✅ **Generic Templates** - Rich card carousels
- ✅ **Multiple images** - Up to 10 images per message (Instagram)
- ✅ **Automatic platform detection** - Instagram vs Messenger
- ✅ **24-hour messaging window** - With message tags support

### RCS (Rich Communication Services)

- ✅ **Rich Cards** - Interactive cards with images and buttons
- ✅ **Carousels** - Scrollable product catalogs
- ✅ **Suggestions** - Quick reply buttons and actions
- ✅ **File sharing** - Documents, PDFs, and files
- ✅ **High-quality media** - Images and videos

### Core Features

- ✅ **Web Admin Panel** - Easy API interaction without coding
- ✅ **Voice Calls Interface** 🆕 - Make and manage WhatsApp calls from web UI
- ✅ **Webhooks** - Delivery reports and incoming messages
- ✅ **Rate limiting** - Prevent API abuse
- ✅ **Automatic retry** - Exponential backoff on failures
- ✅ **Structured logging** - JSON logs with context
- ✅ **Health checks** - System monitoring endpoints
- ✅ **Property-based testing** - Comprehensive test coverage
- ✅ **Multi-language support** - i18n ready admin panel

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or PostgreSQL 12+
- Redis 6.0+
- Composer
- PHP Extensions: PDO, Redis, JSON, cURL

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/your-org/multi-platform-messaging-adapter.git
cd multi-platform-messaging-adapter
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment variables

Copy the example file and configure your credentials:

```bash
cp .env.example .env
```

Edit the `.env` file with your settings:

```env
# WhatsApp Provider Configuration
WHATSAPP_PROVIDER=infobip

# Infobip Configuration
INFOBIP_API_KEY=your_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=447860099299
INFOBIP_WEBHOOK_SECRET=your_webhook_secret

# Infobip RCS Configuration
INFOBIP_RCS_SENDER=your_rcs_sender_id

# Twilio Configuration (optional)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_SENDER=+14155551234
TWILIO_WEBHOOK_SECRET=your_webhook_secret

# Meta Configuration (Instagram + Facebook Messenger)
META_PAGE_ACCESS_TOKEN=your_page_access_token
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_PAGE_ID=your_page_id
META_VERIFY_TOKEN=your_verify_token
META_API_VERSION=v21.0

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_adapter
DB_USERNAME=root
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_CACHE_DB=1
```

### 4. Run migrations

```bash
php bin/migrate.php
```

Or manually execute SQL scripts in `database/migrations/`.

### 5. Access the Admin Panel

Start the built-in PHP server:

```bash
cd admin-panel
php -S localhost:8081
```

Then open your browser at: **http://localhost:8081/index-tabs.html**

The Admin Panel provides:

- 💬 **Message Management** - Send messages across all platforms
- 📱 **RCS Interface** - Send rich cards, carousels, and suggestions
- 📚 **Documentation** - Interactive API documentation
- 📊 **Monitoring** - Alerts and system health dashboard

## Admin Panel

The **Web Admin Panel** is the easiest way to interact with the messaging APIs without coding.

### Features

- **Unified Interface**: Send messages to WhatsApp, Instagram, Messenger, and RCS from one place
- **Interactive Forms**: Dynamic forms that adapt to each platform's capabilities
- **Message History**: View all sent and received messages
- **Template Management**: Browse and use approved WhatsApp templates
- **RCS Rich Messaging**: Create rich cards, carousels, and suggestions visually
- **WhatsApp Calls** 🆕: Make and manage voice calls via WhatsApp using Infobip
  - Real-time call status monitoring
  - Call duration timer
  - Call history
  - One-click call termination
- **API Documentation**: Interactive Swagger UI for testing endpoints
- **Monitoring Dashboard**: Real-time metrics, alerts, and system health

### Access

1. **Local Development**:

   ```bash
   cd admin-panel
   php -S localhost:8080
   ```

   Open: http://localhost:8080/index-tabs.html

2. **Production**:
   Configure your web server to serve the `admin-panel` directory
   Access: https://your-domain.com/admin-panel/

### Tabs

- **💬 Messages**: Send and manage messages across all platforms
- **📞 Calls** 🆕: Make WhatsApp voice calls via Infobip
  - Direct access: http://localhost:8080/calls.html
  - Features: Real-time monitoring, call history, duration timer
  - ⚠️ Requires Voice/Calls service activated on Infobip account
- **📱 RCS**: Rich Communication Services interface
- **📚 Documentation**: API guides and interactive documentation
- **📊 Monitoring**: Alerts, metrics, and system health

### Quick Links

- **Main Dashboard**: http://localhost:8080/index-tabs.html
- **Send Messages**: http://localhost:8080/index.html
- **Make Calls**: http://localhost:8080/calls.html 🆕
- **RCS Interface**: http://localhost:8080/rcs.html
- **Monitoring**: http://localhost:8080/monitoring.html

## Platform Comparison

| Feature              | WhatsApp           | Instagram                   | Facebook Messenger        | RCS               |
| -------------------- | ------------------ | --------------------------- | ------------------------- | ----------------- |
| **Identifier**       | Phone number       | IGSID (Instagram-Scoped ID) | PSID (Page-Scoped ID)     | Phone number      |
| **HSM Templates**    | ✅ Supported       | ❌ Not supported\*          | ❌ Not supported\*        | ❌ Not supported  |
| **Free Text**        | ✅ Supported       | ✅ Supported                | ✅ Supported              | ✅ Supported      |
| **Media**            | ✅ Supported       | ✅ Supported                | ✅ Supported              | ✅ Supported      |
| **Multiple Images**  | ❌ 1 per message   | ✅ Up to 10 per message     | ❌ 1 per message\*\*      | ✅ Via carousel   |
| **Quick Replies**    | ✅ Up to 3 buttons | ✅ Up to 13 quick replies   | ✅ Up to 13 quick replies | ✅ Suggestions    |
| **Rich Cards**       | ❌ Not supported   | ❌ Not supported            | ❌ Not supported          | ✅ Supported      |
| **Carousels**        | ❌ Not supported   | ✅ Generic template         | ✅ Generic template       | ✅ Native support |
| **Messaging Window** | 24 hours           | 24 hours                    | 24 hours                  | No restriction    |
| **Image Size**       | 5MB                | 8MB                         | 25MB                      | 2MB               |
| **Video Size**       | 16MB               | 25MB                        | 25MB                      | 10MB              |
| **API**              | Provider-specific  | Meta Graph API              | Meta Graph API            | Infobip RCS API   |
| **Authentication**   | API Key            | Page Access Token           | Page Access Token         | API Key           |

\* HSM templates are automatically converted to plain text  
\*\* Messenger supports multiple images via carousel template

## RCS (Rich Communication Services)

RCS is the next generation of SMS, offering rich media and interactive features similar to modern messaging apps.

### RCS Features

- 📸 **High-quality media**: Images, videos, and files
- 🎴 **Rich cards**: Interactive cards with images, titles, descriptions, and buttons
- 🎠 **Carousels**: Scrollable cards for product catalogs
- 💬 **Suggestions**: Quick reply buttons and suggested actions
- 📎 **File sharing**: Send documents, PDFs, and other files
- ✅ **Read receipts**: Delivery and read confirmations
- 🔗 **Action buttons**: Web URLs, phone calls, location sharing

### Send RCS Text Message

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json'
    ]
]);

$response = $client->post('/rcs/send', [
    'json' => [
        'to' => '+351912345678',
        'text' => 'Hello! How can I help you today?'
    ]
]);
```

### Send RCS Rich Card

```php
$response = $client->post('/rcs/send-card', [
    'json' => [
        'to' => '+351912345678',
        'card' => [
            'title' => 'Product Name',
            'description' => 'Product description goes here',
            'media' => [
                'url' => 'https://example.com/product.jpg',
                'height' => 'TALL'
            ],
            'suggestions' => [
                [
                    'type' => 'reply',
                    'text' => 'Buy Now',
                    'postbackData' => 'BUY_PRODUCT_123'
                ],
                [
                    'type' => 'url',
                    'text' => 'View Details',
                    'url' => 'https://example.com/product/123'
                ]
            ]
        ]
    ]
]);
```

### Send RCS Carousel

```php
$response = $client->post('/rcs/send-carousel', [
    'json' => [
        'to' => '+351912345678',
        'carousel' => [
            'width' => 'MEDIUM',
            'cards' => [
                [
                    'title' => 'Product 1',
                    'description' => 'First product',
                    'media' => ['url' => 'https://example.com/product1.jpg'],
                    'suggestions' => [
                        ['type' => 'reply', 'text' => 'Buy', 'postbackData' => 'BUY_1']
                    ]
                ],
                [
                    'title' => 'Product 2',
                    'description' => 'Second product',
                    'media' => ['url' => 'https://example.com/product2.jpg'],
                    'suggestions' => [
                        ['type' => 'reply', 'text' => 'Buy', 'postbackData' => 'BUY_2']
                    ]
                ]
            ]
        ]
    ]
]);
```

### RCS Admin Panel

The Admin Panel includes a dedicated RCS interface at `/admin-panel/rcs.html` with:

- 💬 **Text Messages**: Send simple text messages
- 🎴 **Rich Cards**: Create cards with images, titles, and buttons
- 🎠 **Carousels**: Build multi-card carousels
- 📎 **File Sharing**: Send documents and files
- 💡 **Suggestions**: Add quick reply buttons

### RCS Requirements

- RCS-enabled device and carrier
- Infobip RCS account and sender ID
- Configured `INFOBIP_RCS_SENDER` in `.env`

For more details, see the [RCS documentation](docs/RCS_GUIDE.md).

## Usage Examples

### Send WhatsApp HSM Template

```php
$response = $client->post('/messages/hsm', [
    'json' => [
        'to' => '+351912345678',
        'templateName' => 'welcome_message',
        'templateLanguage' => 'pt',
        'parameters' => ['João']
    ]
]);
```

### Make WhatsApp Call

```php
// Via API
$response = $client->post('/api.php?action=initiate_call', [
    'json' => [
        'to' => '+5511999999999',
        'from' => '+351927587119'
    ]
]);

// Response
// {
//   "success": true,
//   "call_id": "abc123",
//   "status": "initiated",
//   "to": "+5511999999999"
// }

// Check call status
$status = $client->get('/api.php?action=get_call_status&call_id=abc123');

// Hangup call
$client->post('/api.php?action=hangup_call&call_id=abc123');

// Or use the web interface at admin-panel/calls.html
```

**Requirements:**

- Infobip account with Voice/Calls service activated
- If you get "Unauthorized access" error, see [Troubleshooting Guide](docs/CALLS_TROUBLESHOOTING.md)
- Contact Infobip to activate: https://www.infobip.com/contact

For more details, see the [Calls Setup Guide](docs/CALLS_SETUP.md).

### Send Instagram Message

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'instagram',
        'recipient' => '1234567890', // IGSID
        'message' => [
            'text' => 'Hello! How can I help?'
        ]
    ]
]);
```

### Send Messenger Quick Replies

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'messenger',
        'recipient' => '9876543210', // PSID
        'message' => [
            'text' => 'Choose an option:',
            'quick_replies' => [
                ['title' => 'Option 1', 'payload' => 'OPTION_1'],
                ['title' => 'Option 2', 'payload' => 'OPTION_2']
            ]
        ]
    ]
]);
```

For more examples, see the [API documentation](docs/API.md).

## API Documentation

### Interactive Documentation

Access the interactive Swagger UI through the Admin Panel:

**http://localhost:8081/api-docs.html**

Or use the API documentation tab in the Admin Panel.

### Endpoints

- `POST /messages/send` - Send a message
- `POST /messages/hsm` - Send HSM template
- `POST /rcs/send` - Send RCS text message
- `POST /rcs/send-card` - Send RCS rich card
- `POST /rcs/send-carousel` - Send RCS carousel
- `GET /messages/{id}/status` - Get message status
- `POST /templates/sync` - Sync WhatsApp templates
- `GET /health` - Health check

Full API reference: [docs/API.md](docs/API.md)

## Testing

### Run all tests

```bash
./vendor/bin/pest
```

### Run unit tests only

```bash
./vendor/bin/pest tests/Unit
```

### Run property tests only

```bash
./vendor/bin/pest tests/Property
```

### Run with coverage

```bash
./vendor/bin/pest --coverage
```

## Monitoring

### Health Check

```bash
curl https://your-domain.com/health
```

### Admin Panel Monitoring

Access the monitoring dashboard in the Admin Panel:

**http://localhost:8081/monitoring.html**

Features:

- ⏱️ **Rate Limits**: Monitor hourly and daily limits
- 🔌 **Circuit Breaker**: Check circuit breaker status
- 🚨 **Alerts**: View recent alerts by severity
- 💚 **System Health**: Overall system health status
- 📈 **Performance**: Response times and metrics

## Documentation

### 📚 Complete Documentation Index

- **[Documentation Index](docs/INDEX.md)** - Complete list of all documentation

### 🚀 Quick Start Guides

- [API Reference](docs/API.md)
- [Calls Quick Start](docs/CALLS_QUICK_START.md) 🆕
- [Instagram/Messenger Setup](docs/INSTAGRAM_SETUP.md)

### 📞 Calls (Voice)

- [Calls Setup Guide](docs/CALLS_SETUP.md) 🆕
- [Calls Troubleshooting](docs/CALLS_TROUBLESHOOTING.md) 🆕
- [Calls Feature Summary](docs/CALLS_FEATURE_SUMMARY.md) 🆕

### 📱 Meta Platform

- [Meta Credentials Setup](docs/META_CREDENTIALS_SETUP.md)
- [Production Deployment](docs/META_PRODUCTION_DEPLOYMENT.md)
- [Meta Request Adapter](docs/META_REQUEST_ADAPTER.md)

### 🔧 Operations & Troubleshooting

- [Troubleshooting Guide](docs/TROUBLESHOOTING.md)
- [Operations Runbook](docs/OPERATIONS_RUNBOOK.md)
- [Deployment Checklist](docs/DEPLOYMENT_CHECKLIST.md)

## Security

### Best Practices

1. **Never commit credentials** - Use environment variables
2. **Use HTTPS** - All communications must be encrypted
3. **Validate webhooks** - Always validate HMAC signatures
4. **Implement rate limiting** - Prevent API abuse
5. **Rotate API keys** - Rotate keys periodically
6. **Monitor logs** - Set up alerts for critical errors
7. **Update dependencies** - Keep libraries up to date

## FAQ

### WhatsApp Calls

**Q: Why do I get "Unauthorized access" when trying to make calls?**  
A: Your Infobip account doesn't have the Voice/Calls service activated. This is a separate service from WhatsApp messaging. Contact Infobip support to activate it: https://www.infobip.com/contact

**Q: How much do WhatsApp calls cost?**  
A: Costs vary by country, typically €0.02-€0.15 per minute. Check with Infobip for exact pricing.

**Q: Can I use Twilio for calls instead of Infobip?**  
A: Yes, Twilio also supports voice calls. See [CALLS_TROUBLESHOOTING.md](docs/CALLS_TROUBLESHOOTING.md) for alternatives.

### General

**Q: Which messaging platforms are supported?**  
A: WhatsApp, Instagram, Facebook Messenger, and RCS.

**Q: Do I need separate accounts for each platform?**  
A: Yes. WhatsApp/RCS use Infobip or Twilio. Instagram/Messenger use Meta (Facebook) credentials.

**Q: Can I send messages without the admin panel?**  
A: Yes, use the REST API directly. See [API.md](docs/API.md) for details.

**Q: Is there a rate limit?**  
A: Yes, each platform has its own limits. The adapter includes rate limiting to prevent abuse.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

### Documentation

- **Complete Index**: [docs/INDEX.md](docs/INDEX.md)
- **API Reference**: [docs/API.md](docs/API.md)
- **Calls Setup**: [docs/CALLS_SETUP.md](docs/CALLS_SETUP.md) 🆕
- **Troubleshooting**: [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

### Quick Access

- **Admin Panel**: http://localhost:8080/index-tabs.html
- **Make Calls**: http://localhost:8080/calls.html 🆕
- **Changelog**: [CHANGELOG.md](CHANGELOG.md)

### Contact

- **Issues**: https://github.com/DanSRamos/whatsapp-hsm-adapter/issues
- **Infobip Support**: https://www.infobip.com/contact
- **Meta Support**: https://developers.facebook.com/support

## Roadmap

- [x] Meta support (Instagram + Facebook Messenger)
- [x] Automatic platform detection (Instagram vs Messenger)
- [x] WhatsApp Voice Calls 🆕
- [ ] Call recording
- [ ] Conference calls (multiple participants)
- [ ] IVR (Interactive Voice Response)
- [ ] Call analytics and reporting
- [x] Multi-provider admin panel
- [x] RCS support (Rich Communication Services)
- [x] RCS rich cards and carousels
- [x] Interactive API documentation (Swagger UI)
- [x] Monitoring dashboard
- [ ] More WhatsApp providers (360Dialog, MessageBird)
- [ ] WhatsApp Business API Cloud support
- [ ] CRM integrations
- [ ] Message Tags support (Meta)
- [ ] Persistent Menu support (Messenger)
- [ ] Analytics and reporting dashboard

## Características

- ✅ Suporte multi-provider (Infobip, Twilio, Meta)
- ✅ Suporte para WhatsApp, Instagram e Facebook Messenger
- ✅ Envio de mensagens HSM/Template (WhatsApp)
- ✅ Envio de mensagens de texto livre
- ✅ Envio de media (imagem, documento, áudio, vídeo)
- ✅ Mensagens interativas (botões e listas)
- ✅ Quick Replies e Generic Templates (Instagram/Messenger)
- ✅ Webhooks para delivery reports e mensagens recebidas
- ✅ Sincronização automática de templates (WhatsApp)
- ✅ Detecção automática de plataforma (Instagram vs Messenger)
- ✅ Rate limiting e autenticação
- ✅ Retry automático com backoff exponencial
- ✅ Logging estruturado e monitorização
- ✅ Health checks
- ✅ Property-based testing

## Requisitos

- PHP 8.1 ou superior
- MySQL 5.7+ ou PostgreSQL 12+
- Redis 6.0+
- Composer
- Extensões PHP: PDO, Redis, JSON, cURL

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/your-org/whatsapp-hsm-adapter.git
cd whatsapp-hsm-adapter
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure as variáveis de ambiente

Copie o arquivo de exemplo e configure suas credenciais:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações:

```env
# WhatsApp Provider Configuration
WHATSAPP_PROVIDER=infobip

# Infobip Configuration
INFOBIP_API_KEY=your_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=447860099299
INFOBIP_WEBHOOK_SECRET=your_webhook_secret

# Twilio Configuration (opcional)
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_SENDER=+14155551234
TWILIO_WEBHOOK_SECRET=your_webhook_secret

# Meta Configuration (Instagram + Facebook Messenger)
META_PAGE_ACCESS_TOKEN=your_page_access_token
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_PAGE_ID=your_page_id
META_VERIFY_TOKEN=your_verify_token
META_API_VERSION=v21.0

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_adapter
DB_USERNAME=root
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
REDIS_CACHE_DB=1

# Cache Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=whatsapp_adapter_cache

# Logging Configuration
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_PATH=storage/logs

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_PER_MINUTE=100

# Retry Configuration
RETRY_MAX_ATTEMPTS=3
RETRY_INITIAL_DELAY_MS=1000
```

### 4. Execute as migrations

```bash
php bin/migrate.php
```

Ou execute manualmente os scripts SQL em `database/migrations/`:

```bash
mysql -u root -p whatsapp_adapter < database/migrations/001_create_messages_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/002_create_incoming_messages_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/003_create_templates_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/004_create_webhook_logs_table.sql
```

### 5. Configure o servidor web

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/whatsapp-hsm-adapter/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/whatsapp-hsm-adapter/public

    <Directory /path/to/whatsapp-hsm-adapter/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Configure os webhooks

Configure os seguintes URLs no painel do seu provedor:

**WhatsApp (Infobip/Twilio):**

- **Delivery Reports:** `https://your-domain.com/webhooks/delivery-reports`
- **Incoming Messages:** `https://your-domain.com/webhooks/incoming-messages`
- **Template Updates:** `https://your-domain.com/webhooks/template-updates`

**Meta (Instagram + Facebook Messenger):**

- **Webhook URL:** `https://your-domain.com/webhook/meta`
- **Eventos:** messages, messaging_postbacks, message_deliveries, message_reads

Para configurar Meta, consulte o [guia de setup do Instagram/Messenger](docs/INSTAGRAM_SETUP.md).

## Uso

### Enviar uma mensagem HSM

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json'
    ]
]);

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

### Consultar status de mensagem

```php
$response = $client->get('/messages/msg_abc123/status');
$data = json_decode($response->getBody(), true);
echo "Status: " . $data['data']['status'];
```

### Sincronizar templates

```php
$response = $client->post('/templates/sync');
$data = json_decode($response->getBody(), true);
echo "Templates sincronizados: " . $data['data']['total'];
```

Para mais exemplos, consulte a [documentação da API](docs/API.md).

## Integração Meta (Instagram + Facebook Messenger)

### Visão Geral

O adapter suporta Instagram Direct Messages e Facebook Messenger através do Meta Provider, que utiliza a Messenger Platform API unificada da Meta. Uma única configuração funciona para ambas as plataformas.

### Setup Rápido

1. **Crie um App Meta**: Acesse [Meta for Developers](https://developers.facebook.com/)
2. **Configure Facebook Page**: Crie ou conecte uma Facebook Page
3. **Conecte Instagram**: Conecte uma conta Instagram Professional/Business
4. **Gere Page Access Token**: Token de longa duração ou permanente
5. **Configure Webhooks**: URL `https://your-domain.com/webhook/meta`

Para instruções detalhadas, consulte o [Guia de Setup do Instagram/Messenger](docs/INSTAGRAM_SETUP.md).

### Diferenças: WhatsApp vs Instagram vs Messenger

| Característica          | WhatsApp           | Instagram                   | Facebook Messenger      |
| ----------------------- | ------------------ | --------------------------- | ----------------------- |
| **Identificador**       | Número de telefone | IGSID (Instagram-Scoped ID) | PSID (Page-Scoped ID)   |
| **Templates HSM**       | ✅ Suportado       | ❌ Não suportado\*          | ❌ Não suportado\*      |
| **Texto Livre**         | ✅ Suportado       | ✅ Suportado                | ✅ Suportado            |
| **Mídia**               | ✅ Suportado       | ✅ Suportado                | ✅ Suportado            |
| **Múltiplas Imagens**   | ❌ 1 por mensagem  | ✅ Até 10 por mensagem      | ❌ 1 por mensagem\*\*   |
| **Quick Replies**       | ✅ Até 3 botões    | ✅ Até 13 quick replies     | ✅ Até 13 quick replies |
| **Generic Template**    | ❌ Não suportado   | ✅ Suportado                | ✅ Suportado            |
| **Button Template**     | ❌ Não suportado   | ❌ Não suportado            | ✅ Suportado            |
| **Janela de Mensagens** | 24 horas           | 24 horas                    | 24 horas                |
| **Tamanho Imagem**      | 5MB                | 8MB                         | 25MB                    |
| **Tamanho Vídeo**       | 16MB               | 25MB                        | 25MB                    |
| **API**                 | Provider-specific  | Meta Graph API              | Meta Graph API          |
| **Autenticação**        | API Key            | Page Access Token           | Page Access Token       |

\* Templates HSM são automaticamente convertidos para texto simples  
\*\* Messenger suporta múltiplas imagens via carousel template

### Enviar Mensagem de Texto (Instagram)

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Content-Type' => 'application/json'
    ]
]);

$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'instagram',
        'recipient' => '1234567890', // IGSID
        'message' => [
            'text' => 'Olá! Como posso ajudar?'
        ]
    ]
]);

$data = json_decode($response->getBody(), true);
echo "Message ID: " . $data['data']['messageId'];
```

### Enviar Mensagem de Texto (Messenger)

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'messenger',
        'recipient' => '9876543210', // PSID
        'message' => [
            'text' => 'Olá! Como posso ajudar?'
        ]
    ]
]);
```

### Enviar Mídia (Instagram/Messenger)

```php
// Enviar imagem
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID',
        'message' => [
            'type' => 'image',
            'url' => 'https://example.com/image.jpg'
        ]
    ]
]);

// Enviar múltiplas imagens (Instagram apenas)
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'instagram',
        'recipient' => 'IGSID',
        'message' => [
            'type' => 'images',
            'urls' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
                'https://example.com/image3.jpg'
            ]
        ]
    ]
]);
```

### Enviar Quick Replies (Instagram/Messenger)

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID',
        'message' => [
            'text' => 'Escolha uma opção:',
            'quick_replies' => [
                ['title' => 'Opção 1', 'payload' => 'OPTION_1'],
                ['title' => 'Opção 2', 'payload' => 'OPTION_2'],
                ['title' => 'Opção 3', 'payload' => 'OPTION_3']
            ]
        ]
    ]
]);
```

### Enviar Generic Template (Instagram/Messenger)

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID',
        'message' => [
            'type' => 'template',
            'template_type' => 'generic',
            'elements' => [
                [
                    'title' => 'Produto 1',
                    'subtitle' => 'Descrição do produto',
                    'image_url' => 'https://example.com/product1.jpg',
                    'buttons' => [
                        ['type' => 'web_url', 'title' => 'Ver Mais', 'url' => 'https://example.com/product1']
                    ]
                ],
                [
                    'title' => 'Produto 2',
                    'subtitle' => 'Descrição do produto',
                    'image_url' => 'https://example.com/product2.jpg',
                    'buttons' => [
                        ['type' => 'web_url', 'title' => 'Ver Mais', 'url' => 'https://example.com/product2']
                    ]
                ]
            ]
        ]
    ]
]);
```

### Enviar Button Template (Messenger apenas)

```php
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'platform' => 'messenger',
        'recipient' => 'PSID',
        'message' => [
            'type' => 'template',
            'template_type' => 'button',
            'text' => 'Escolha uma ação:',
            'buttons' => [
                ['type' => 'web_url', 'title' => 'Visitar Site', 'url' => 'https://example.com'],
                ['type' => 'postback', 'title' => 'Falar com Atendente', 'payload' => 'CONTACT_AGENT'],
                ['type' => 'phone_number', 'title' => 'Ligar', 'payload' => '+351912345678']
            ]
        ]
    ]
]);
```

### Detecção Automática de Plataforma

O sistema detecta automaticamente se a mensagem é para Instagram ou Messenger baseado no formato do ID:

```php
// Não é necessário especificar 'platform' - o sistema detecta automaticamente
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID', // Sistema detecta automaticamente
        'message' => [
            'text' => 'Mensagem automática'
        ]
    ]
]);
```

### Limitações Importantes

#### Janela de 24 Horas

Você só pode enviar mensagens dentro de 24 horas após a última mensagem do usuário. Após esse período:

- ❌ Mensagens promocionais não são permitidas
- ✅ Você pode usar **Message Tags** para casos específicos:
  - `CONFIRMED_EVENT_UPDATE` - Atualizações de eventos
  - `POST_PURCHASE_UPDATE` - Atualizações pós-compra
  - `ACCOUNT_UPDATE` - Atualizações de conta

```php
// Enviar com message tag (após 24h)
$response = $client->post('/messages/send', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID',
        'message' => [
            'text' => 'Seu pedido foi enviado!',
            'tag' => 'POST_PURCHASE_UPDATE'
        ]
    ]
]);
```

#### Templates HSM

Instagram e Messenger não suportam templates HSM do WhatsApp. O sistema converte automaticamente:

```php
// Template HSM (WhatsApp)
$response = $client->post('/messages/hsm', [
    'json' => [
        'provider' => 'whatsapp',
        'to' => '+351912345678',
        'templateName' => 'welcome_message',
        'parameters' => ['João']
    ]
]);

// Mesmo template convertido para Meta (Instagram/Messenger)
// Sistema substitui {{1}} por 'João' automaticamente
$response = $client->post('/messages/hsm', [
    'json' => [
        'provider' => 'meta',
        'recipient' => 'IGSID_OR_PSID',
        'templateName' => 'welcome_message',
        'parameters' => ['João']
    ]
]);
// Resultado: "Olá João, bem-vindo!" (texto simples)
```

### Admin Panel

O admin panel suporta todas as três plataformas com interface unificada:

1. **Seletor de Provider**: Escolha entre WhatsApp, Instagram ou Messenger
2. **Campos Dinâmicos**: Interface adapta-se ao provider selecionado
3. **Visualização Unificada**: Veja mensagens de todas as plataformas
4. **Filtros**: Filtre por provider, status, data, etc.

Acesse: `https://your-domain.com/admin-panel/`

### Recursos Adicionais

- [Guia de Setup Completo](docs/INSTAGRAM_SETUP.md)
- [Guia de Troubleshooting](docs/TROUBLESHOOTING.md)
- [Documentação da API Meta](docs/META_REQUEST_ADAPTER.md)
- [Meta for Developers](https://developers.facebook.com/)

## Testes

### Executar todos os testes

```bash
./vendor/bin/pest
```

### Executar apenas unit tests

```bash
./vendor/bin/pest tests/Unit
```

### Executar apenas property tests

```bash
./vendor/bin/pest tests/Property
```

### Executar testes com cobertura

```bash
./vendor/bin/pest --coverage
```

## Monitorização

### Health Check

Verifique o status do serviço:

```bash
curl https://your-domain.com/health
```

Resposta quando tudo está OK:

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
      "message": "All providers accessible"
    }
  }
}
```

### Logs

Os logs são armazenados em `storage/logs/whatsapp-adapter.log` no formato JSON.

Visualizar logs em tempo real:

```bash
tail -f storage/logs/whatsapp-adapter.log | jq
```

## Deployment

### Docker

```dockerfile
FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

### Docker Compose

```yaml
version: "3.8"

services:
  app:
    build: .
    volumes:
      - .:/var/www
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - .:/var/www
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: whatsapp_adapter
      MYSQL_ROOT_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:alpine
    volumes:
      - redis_data:/data

volumes:
  mysql_data:
  redis_data:
```

### Kubernetes

Exemplo de deployment:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: whatsapp-adapter
spec:
  replicas: 3
  selector:
    matchLabels:
      app: whatsapp-adapter
  template:
    metadata:
      labels:
        app: whatsapp-adapter
    spec:
      containers:
        - name: app
          image: your-registry/whatsapp-adapter:latest
          ports:
            - containerPort: 9000
          env:
            - name: DB_HOST
              value: mysql-service
            - name: REDIS_HOST
              value: redis-service
          envFrom:
            - secretRef:
                name: whatsapp-secrets
```

## Segurança

### Boas Práticas

1. **Nunca faça commit de credenciais** - Use variáveis de ambiente
2. **Use HTTPS** - Todas as comunicações devem ser encriptadas
3. **Valide webhooks** - Sempre valide assinaturas HMAC
4. **Implemente rate limiting** - Previna abuso da API
5. **Rotação de API keys** - Rotacione chaves periodicamente
6. **Monitorize logs** - Configure alertas para erros críticos
7. **Atualize dependências** - Mantenha bibliotecas atualizadas

### Configuração de Firewall

Permita apenas tráfego necessário:

```bash
# Permitir HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Permitir apenas IPs da Infobip para webhooks
ufw allow from 185.45.152.0/22 to any port 443
```

## Troubleshooting

### Problema: Mensagens não são enviadas

**Solução:**

1. Verifique as credenciais do provedor no `.env`
2. Verifique os logs: `tail -f storage/logs/whatsapp-adapter.log`
3. Teste a conectividade: `curl https://your-domain.com/health`

### Problema: Webhooks não são recebidos

**Solução:**

1. Verifique se os URLs estão configurados corretamente no provedor
2. Verifique se o servidor está acessível publicamente
3. Verifique os logs de webhook: `grep webhook storage/logs/whatsapp-adapter.log`

### Problema: Rate limit excedido

**Solução:**

1. Ajuste os limites em `.env`: `RATE_LIMIT_PER_MINUTE=200`
2. Implemente retry com backoff exponencial no cliente
3. Distribua requisições ao longo do tempo

## Contribuir

Contribuições são bem-vindas! Por favor:

1. Fork o repositório
2. Crie uma branch para sua feature (`git checkout -b feature/amazing-feature`)
3. Commit suas mudanças (`git commit -m 'Add amazing feature'`)
4. Push para a branch (`git push origin feature/amazing-feature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Suporte

- **Documentação:** [docs/API.md](docs/API.md)
- **Issues:** https://github.com/your-org/whatsapp-hsm-adapter/issues
- **Email:** support@example.com

## Roadmap

- [x] Suporte para Meta (Instagram + Facebook Messenger)
- [x] Detecção automática de plataforma (Instagram vs Messenger)
- [x] Admin panel multi-provider
- [ ] Suporte para mais provedores WhatsApp (360Dialog, MessageBird)
- [ ] Interface web para gestão de templates
- [ ] Métricas e analytics por provider
- [ ] Suporte para WhatsApp Business API Cloud
- [ ] Integração com CRM populares
- [ ] Suporte para Message Tags (Meta)
- [ ] Suporte para Persistent Menu (Messenger)
