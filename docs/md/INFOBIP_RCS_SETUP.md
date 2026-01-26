# Infobip RCS Setup Guide

**Data**: 26 Janeiro 2026  
**Status**: Configuração necessária

## ✅ Configuração Atual

O RCS provider já está configurado para usar as **mesmas credenciais** do Infobip WhatsApp:

```php
// config/providers.php
'infobip-rcs' => [
    'config' => [
        'api_key' => env('INFOBIP_API_KEY'),           // ✅ Mesma API key
        'base_url' => env('INFOBIP_BASE_URL'),         // ✅ Mesma base URL
        'sender' => env('INFOBIP_RCS_SENDER', env('INFOBIP_SENDER')), // ✅ Usa INFOBIP_SENDER como fallback
        'webhook_secret' => env('INFOBIP_WEBHOOK_SECRET'), // ✅ Mesmo webhook secret
    ],
],
```

## 📝 Configuração no .env

### Opção 1: Usar Credenciais Existentes (Recomendado)

Se já tens credenciais Infobip para WhatsApp, **não precisas fazer nada**! O RCS vai usar as mesmas:

```env
# Credenciais Infobip (partilhadas entre WhatsApp e RCS)
INFOBIP_API_KEY=your_actual_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=your_whatsapp_number
INFOBIP_WEBHOOK_SECRET=your_webhook_secret
```

O RCS vai usar automaticamente:

- ✅ `INFOBIP_API_KEY` para autenticação
- ✅ `INFOBIP_BASE_URL` para endpoint
- ✅ `INFOBIP_SENDER` como sender ID (se não definires `INFOBIP_RCS_SENDER`)

### Opção 2: Sender ID Diferente para RCS

Se quiseres usar um sender ID diferente para RCS:

```env
# Credenciais Infobip
INFOBIP_API_KEY=your_actual_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=your_whatsapp_number
INFOBIP_WEBHOOK_SECRET=your_webhook_secret

# RCS Sender específico (opcional)
INFOBIP_RCS_SENDER=your_rcs_sender_id
```

## 🔑 Como Obter as Credenciais

### 1. API Key

1. Acede ao [Infobip Portal](https://portal.infobip.com/)
2. Login com a tua conta
3. Vai a **Settings** → **API Keys**
4. Copia a tua API Key
5. Cola no `.env`:
   ```env
   INFOBIP_API_KEY=paste_your_key_here
   ```

### 2. Base URL

A base URL padrão é `https://api.infobip.com`, mas pode variar conforme a tua região:

- Europa: `https://api.infobip.com`
- US: `https://api.infobip.com` (mesmo endpoint)
- Outros: Verifica no portal Infobip

```env
INFOBIP_BASE_URL=https://api.infobip.com
```

### 3. Sender ID

Para WhatsApp:

- É o teu número WhatsApp Business registado
- Formato: `+351912345678` ou `351912345678`

Para RCS:

- Pode ser o mesmo número ou um sender ID específico
- Verifica no portal Infobip qual o sender ID RCS disponível

```env
INFOBIP_SENDER=+351912345678
# Opcional: sender diferente para RCS
INFOBIP_RCS_SENDER=your_rcs_sender_id
```

### 4. Webhook Secret

Para validar webhooks recebidos:

```env
INFOBIP_WEBHOOK_SECRET=your_secret_here
```

## 🧪 Testar a Configuração

### 1. Verificar se as credenciais estão carregadas

```bash
php -r "
require 'vendor/autoload.php';
\$config = require 'config/providers.php';
print_r(\$config['providers']['infobip-rcs']['config']);
"
```

### 2. Testar endpoint RCS

```bash
curl -X POST http://localhost:8081/api/rcs/text \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+351912345678",
    "text": "Teste RCS via Infobip"
  }'
```

**Resposta esperada com credenciais corretas**:

```json
{
  "success": true,
  "data": {
    "message_id": "abc123...",
    "status": "PENDING_ACCEPTED",
    "to": "+351912345678"
  }
}
```

**Resposta com credenciais em falta**:

```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "Missing required config: api_key"
  }
}
```

### 3. Testar via Admin Panel

1. Abre: http://localhost:8081/admin-panel/rcs.html
2. Preenche o formulário de texto
3. Clica em "Send Message"
4. Verifica a resposta

## 📊 Endpoints RCS Disponíveis

Todos usam as mesmas credenciais Infobip:

1. **POST /api/rcs/text** - Enviar mensagem de texto
2. **POST /api/rcs/file** - Enviar ficheiro
3. **POST /api/rcs/card** - Enviar rich card
4. **POST /api/rcs/carousel** - Enviar carousel
5. **POST /api/rcs/suggestions** - Enviar mensagem com sugestões

## ⚠️ Notas Importantes

### Partilha de Credenciais

✅ **Vantagens**:

- Uma única API key para WhatsApp e RCS
- Gestão simplificada de credenciais
- Mesma conta Infobip para ambos os serviços

⚠️ **Atenção**:

- Certifica-te que a tua conta Infobip tem RCS ativado
- Verifica os limites de rate da API (partilhados entre serviços)
- Monitora o uso para não exceder quotas

### Verificar Serviços Ativos

No portal Infobip, verifica se tens:

- ✅ WhatsApp Business API ativo
- ✅ RCS Messaging ativo

Se RCS não estiver ativo, contacta o suporte Infobip para ativar.

## 🔒 Segurança

### Proteger Credenciais

1. **Nunca** commits o `.env` para o git
2. Usa `.env.example` como template
3. Roda as credenciais periodicamente
4. Usa diferentes API keys para dev/staging/production

### Exemplo .env.example

```env
# Infobip Configuration
INFOBIP_API_KEY=your_infobip_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=your_sender_number
INFOBIP_WEBHOOK_SECRET=your_webhook_secret_here

# Optional: Separate RCS sender
INFOBIP_RCS_SENDER=your_rcs_sender_id
```

## ✅ Checklist de Configuração

- [ ] Obter API Key do portal Infobip
- [ ] Atualizar `INFOBIP_API_KEY` no `.env`
- [ ] Verificar `INFOBIP_BASE_URL` (padrão: https://api.infobip.com)
- [ ] Configurar `INFOBIP_SENDER` com número WhatsApp
- [ ] (Opcional) Configurar `INFOBIP_RCS_SENDER` se diferente
- [ ] Testar endpoint RCS via curl
- [ ] Testar via Admin Panel
- [ ] Verificar logs para erros

## 🆘 Troubleshooting

### Erro: "Missing required config: api_key"

**Causa**: API key não configurada ou vazia  
**Solução**: Atualiza `INFOBIP_API_KEY` no `.env` com valor real

### Erro: "Authentication failed"

**Causa**: API key inválida  
**Solução**: Verifica se a API key está correta no portal Infobip

### Erro: "Sender not found"

**Causa**: Sender ID não registado  
**Solução**: Verifica no portal Infobip qual o sender ID correto

### Erro: "RCS not enabled"

**Causa**: RCS não ativo na conta  
**Solução**: Contacta suporte Infobip para ativar RCS

## 📚 Recursos

- [Infobip Portal](https://portal.infobip.com/)
- [Infobip RCS API Docs](https://www.infobip.com/docs/api/channels/rcs)
- [Infobip WhatsApp API Docs](https://www.infobip.com/docs/api/channels/whatsapp)

---

**Resumo**: O RCS já está configurado para usar as mesmas credenciais do Infobip WhatsApp. Só precisas atualizar o `.env` com as tuas credenciais reais! 🚀
