# WhatsApp HSM Adapter

Um adapter PHP para integração com APIs WhatsApp de múltiplos provedores (Infobip, Twilio, etc.). Fornece uma camada de abstração unificada para envio e recepção de mensagens WhatsApp, incluindo HSM (Highly Structured Messages), mensagens de texto livre, media, e mensagens interativas.

## Características

- ✅ Suporte multi-provider (Infobip, Twilio)
- ✅ Envio de mensagens HSM/Template
- ✅ Envio de mensagens de texto livre
- ✅ Envio de media (imagem, documento, áudio, vídeo)
- ✅ Mensagens interativas (botões e listas)
- ✅ Webhooks para delivery reports e mensagens recebidas
- ✅ Sincronização automática de templates
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

Configure os seguintes URLs no painel do seu provedor WhatsApp:

- **Delivery Reports:** `https://your-domain.com/webhooks/delivery-reports`
- **Incoming Messages:** `https://your-domain.com/webhooks/incoming-messages`
- **Template Updates:** `https://your-domain.com/webhooks/template-updates`

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

- [ ] Suporte para mais provedores (360Dialog, MessageBird)
- [ ] Interface web para gestão de templates
- [ ] Métricas e analytics
- [ ] Suporte para WhatsApp Business API Cloud
- [ ] Integração com CRM populares
