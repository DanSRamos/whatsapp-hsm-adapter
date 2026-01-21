# WhatsApp HSM Adapter

Um adapter PHP para integração com APIs WhatsApp de múltiplos provedores (Infobip, Twilio, etc.). Fornece uma camada de abstração unificada para envio e recepção de mensagens WhatsApp, incluindo HSM (Highly Structured Messages), mensagens de texto livre, media, e mensagens interativas.

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
