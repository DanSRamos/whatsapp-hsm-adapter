# Instagram & Facebook Messenger Setup Guide

Guia completo para configurar a integração com Instagram Messaging API e Facebook Messenger API usando o WhatsApp HSM Adapter.

## Índice

- [Visão Geral](#visão-geral)
- [Pré-requisitos](#pré-requisitos)
- [Passo 1: Criar App Meta (Facebook)](#passo-1-criar-app-meta-facebook)
- [Passo 2: Configurar Facebook Page](#passo-2-configurar-facebook-page)
- [Passo 3: Conectar Instagram Professional Account](#passo-3-conectar-instagram-professional-account)
- [Passo 4: Gerar Page Access Token](#passo-4-gerar-page-access-token)
- [Passo 5: Configurar Webhooks](#passo-5-configurar-webhooks)
- [Passo 6: Configurar Variáveis de Ambiente](#passo-6-configurar-variáveis-de-ambiente)
- [Passo 7: Testar a Integração](#passo-7-testar-a-integração)
- [Permissões Necessárias](#permissões-necessárias)
- [Limitações e Restrições](#limitações-e-restrições)
- [Troubleshooting](#troubleshooting)
- [Recursos Adicionais](#recursos-adicionais)

## Visão Geral

O Meta Provider utiliza a **Messenger Platform API** da Meta, que é uma API unificada que suporta tanto Instagram Direct Messages quanto Facebook Messenger. Isso significa que você configura uma única integração que funciona para ambas as plataformas.

### Arquitetura da Integração

```
┌─────────────────────────────────────────────────────────┐
│                    Sua Aplicação                         │
│              (WhatsApp HSM Adapter)                      │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│              Meta Graph API (v21.0)                      │
│         https://graph.facebook.com/v21.0                 │
└─────────────────────────────────────────────────────────┘
                          │
                ┌─────────┴─────────┐
                ▼                   ▼
┌─────────────────────┐   ┌─────────────────────┐
│  Instagram Direct   │   │ Facebook Messenger  │
│     Messages        │   │                     │
└─────────────────────┘   └─────────────────────┘
```

### O que você vai precisar

- **App ID**: Identificador único do seu app Meta
- **App Secret**: Chave secreta para autenticação
- **Page ID**: ID da sua Facebook Page
- **Page Access Token**: Token de acesso para enviar mensagens
- **Verify Token**: Token personalizado para validação de webhooks

## Pré-requisitos

Antes de começar, certifique-se de ter:

- ✅ Uma conta Facebook ativa
- ✅ Uma Facebook Page (ou permissão para criar uma)
- ✅ Uma conta Instagram Professional ou Business (para Instagram messaging)
- ✅ Acesso administrativo à Facebook Page e conta Instagram
- ✅ Um servidor com URL pública e HTTPS (para webhooks)
- ✅ PHP 8.1+ instalado no servidor

## Passo 1: Criar App Meta (Facebook)

### 1.1 Acessar Meta for Developers

1. Acesse [Meta for Developers](https://developers.facebook.com/)
2. Faça login com sua conta Facebook
3. Clique em **"My Apps"** no canto superior direito

### 1.2 Criar Novo App

1. Clique em **"Create App"**
2. Selecione o tipo de app: **"Business"**
3. Clique em **"Next"**

### 1.3 Configurar Detalhes do App

Preencha as informações:

- **App Name**: Nome descritivo (ex: "Meu Sistema de Mensagens")
- **App Contact Email**: Seu email de contato
- **Business Account**: Selecione ou crie uma conta business

4. Clique em **"Create App"**

### 1.4 Obter App ID e App Secret

1. No dashboard do app, vá para **Settings** → **Basic**
2. Copie o **App ID** (você vai precisar para `META_APP_ID`)
3. Clique em **"Show"** ao lado de **App Secret**
4. Copie o **App Secret** (você vai precisar para `META_APP_SECRET`)

⚠️ **Importante**: Nunca compartilhe seu App Secret publicamente!

## Passo 2: Configurar Facebook Page

### 2.1 Criar ou Usar uma Page Existente

#### Opção A: Criar Nova Page

1. Acesse [facebook.com/pages/create](https://www.facebook.com/pages/create)
2. Escolha uma categoria (ex: "Empresa Local", "Marca ou Produto")
3. Preencha os detalhes:
   - Nome da Page
   - Categoria
   - Descrição
4. Clique em **"Create Page"**

#### Opção B: Usar Page Existente

1. Certifique-se de ter acesso administrativo à Page
2. Anote o nome da Page para os próximos passos

### 2.2 Obter Page ID

**Método 1: Via About**

1. Acesse sua Facebook Page
2. Clique em **"About"** na barra lateral esquerda
3. Role para baixo até encontrar **"Page ID"**
4. Copie o ID (você vai precisar para `META_PAGE_ID`)

**Método 2: Via URL**

1. Acesse `https://www.facebook.com/NOME_DA_SUA_PAGE`
2. O Page ID pode estar na URL ou no código fonte da página

**Método 3: Via Graph API Explorer**

1. Acesse [Graph API Explorer](https://developers.facebook.com/tools/explorer/)
2. Execute: `GET /me/accounts`
3. Encontre sua Page na resposta e copie o `id`

### 2.3 Adicionar Messenger ao App

1. No dashboard do seu app Meta, role até **"Add Products"**
2. Encontre **"Messenger"** e clique em **"Set Up"**
3. Isso adiciona o produto Messenger ao seu app

## Passo 3: Conectar Instagram Professional Account

### 3.1 Converter Conta para Professional

Se sua conta Instagram ainda não é Professional:

1. Abra o app Instagram no celular
2. Vá para **Perfil** → **Menu** (☰) → **Settings**
3. Toque em **Account**
4. Toque em **Switch to Professional Account**
5. Escolha uma categoria
6. Escolha **Business** ou **Creator**
7. Complete o setup

### 3.2 Conectar Instagram à Facebook Page

1. No app Instagram, vá para **Settings** → **Account**
2. Toque em **Linked Accounts**
3. Selecione **Facebook**
4. Faça login e conecte à sua Facebook Page

**Ou via Facebook:**

1. Acesse sua Facebook Page
2. Vá para **Settings** → **Instagram**
3. Clique em **Connect Account**
4. Faça login no Instagram e autorize

### 3.3 Adicionar Instagram ao App Meta

1. No dashboard do seu app Meta, vá para **Instagram** → **Settings**
2. Clique em **"Add or Remove Instagram Accounts"**
3. Selecione sua conta Instagram Professional
4. Conecte à sua Facebook Page
5. Conceda as permissões necessárias:
   - `instagram_basic`
   - `instagram_manage_messages`
   - `pages_show_list`

✅ **Verificação**: Sua conta Instagram agora está conectada à Facebook Page e ao app Meta.

## Passo 4: Gerar Page Access Token

### 4.1 Gerar Token de Curta Duração

1. No dashboard do app, vá para **Messenger** → **Settings**
2. Role até **"Access Tokens"**
3. Clique em **"Add or Remove Pages"**
4. Selecione sua Facebook Page
5. Conceda as permissões:
   - ✅ `pages_messaging`
   - ✅ `pages_read_engagement`
   - ✅ `pages_manage_metadata`
   - ✅ `pages_show_list`
6. Clique em **"Generate Token"** ao lado da sua Page
7. Copie o token gerado

⚠️ **Atenção**: Este é um token de curta duração (expira em algumas horas).

### 4.2 Converter para Token de Longa Duração

Execute este comando no terminal (substitua os valores):

```bash
curl -X GET "https://graph.facebook.com/v21.0/oauth/access_token?\
grant_type=fb_exchange_token&\
client_id=SEU_APP_ID&\
client_secret=SEU_APP_SECRET&\
fb_exchange_token=SEU_TOKEN_CURTA_DURACAO"
```

**Resposta esperada:**

```json
{
  "access_token": "SEU_TOKEN_LONGA_DURACAO",
  "token_type": "bearer",
  "expires_in": 5183944
}
```

O token de longa duração é válido por **60 dias**.

### 4.3 Obter Token Permanente (Opcional)

Para um token que não expira:

```bash
curl -X GET "https://graph.facebook.com/v21.0/SEU_PAGE_ID?\
fields=access_token&\
access_token=SEU_TOKEN_LONGA_DURACAO"
```

**Resposta:**

```json
{
  "access_token": "SEU_TOKEN_PERMANENTE",
  "id": "SEU_PAGE_ID"
}
```

✅ Use este token permanente como `META_PAGE_ACCESS_TOKEN`.

## Passo 5: Configurar Webhooks

### 5.1 Preparar Endpoint de Webhook

Certifique-se de que seu servidor está configurado e acessível via HTTPS:

- URL do webhook: `https://seu-dominio.com/webhook/meta`
- Deve responder a requisições GET (verificação) e POST (eventos)

### 5.2 Criar Verify Token

Crie um token personalizado (string aleatória):

```bash
# Gerar token aleatório
openssl rand -hex 32
```

Anote este valor para usar como `META_VERIFY_TOKEN`.

### 5.3 Configurar Webhook no App Meta

1. No dashboard do app, vá para **Messenger** → **Settings**
2. Role até **"Webhooks"**
3. Clique em **"Add Callback URL"**
4. Preencha:
   - **Callback URL**: `https://seu-dominio.com/webhook/meta`
   - **Verify Token**: O token que você criou no passo 5.2
5. Clique em **"Verify and Save"**

Meta vai fazer uma requisição GET para verificar seu endpoint.

### 5.4 Subscrever Eventos de Webhook

Após adicionar o callback URL, subscreva aos eventos:

**Para Facebook Messenger:**

1. Na seção Webhooks, encontre sua Page
2. Clique em **"Add Subscriptions"**
3. Selecione:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
   - ✅ `messaging_optins`
4. Clique em **"Save"**

**Para Instagram:**

1. Vá para **Instagram** → **Settings** no dashboard
2. Na seção Webhooks, clique em **"Add Subscriptions"**
3. Selecione:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
4. Clique em **"Save"**

### 5.5 Testar Webhook Localmente (Desenvolvimento)

Para testar localmente, use [ngrok](https://ngrok.com/):

```bash
# Instalar ngrok
brew install ngrok  # macOS
# ou baixe de https://ngrok.com/download

# Expor porta local
ngrok http 8000

# Use a URL gerada (ex: https://abc123.ngrok.io/webhook/meta)
```

## Passo 6: Configurar Variáveis de Ambiente

### 6.1 Adicionar ao .env

Edite seu arquivo `.env` e adicione:

```bash
# Meta Configuration (Instagram + Facebook Messenger)
META_PAGE_ACCESS_TOKEN=seu_page_access_token_permanente
META_APP_ID=seu_app_id
META_APP_SECRET=seu_app_secret
META_PAGE_ID=seu_page_id
META_VERIFY_TOKEN=seu_verify_token_personalizado
META_API_VERSION=v21.0
```

### 6.2 Verificar Configuração

Execute o script de verificação:

```bash
php bin/verify-meta-config.php
```

Ou verifique manualmente:

```php
<?php
require 'vendor/autoload.php';

$config = require 'config/meta.php';

echo "✓ Page Access Token: " . (strlen($config['page_access_token']) > 0 ? 'OK' : 'MISSING') . "\n";
echo "✓ App ID: " . ($config['app_id'] ? 'OK' : 'MISSING') . "\n";
echo "✓ App Secret: " . (strlen($config['app_secret']) > 0 ? 'OK' : 'MISSING') . "\n";
echo "✓ Page ID: " . ($config['page_id'] ? 'OK' : 'MISSING') . "\n";
echo "✓ Verify Token: " . (strlen($config['verify_token']) > 0 ? 'OK' : 'MISSING') . "\n";
```

## Passo 7: Testar a Integração

### 7.1 Testar Verificação de Webhook

Meta deve ter verificado automaticamente. Para testar manualmente:

```bash
curl -X GET "https://seu-dominio.com/webhook/meta?\
hub.mode=subscribe&\
hub.verify_token=SEU_VERIFY_TOKEN&\
hub.challenge=TESTE123"
```

**Resposta esperada:** `TESTE123`

### 7.2 Testar Envio de Mensagem (Instagram)

Primeiro, obtenha um IGSID enviando uma mensagem para sua conta Instagram via Direct Message.

```bash
curl -X POST https://seu-dominio.com/api/messages/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_API_KEY" \
  -d '{
    "provider": "meta",
    "platform": "instagram",
    "recipient": "IGSID_DO_USUARIO",
    "message": {
      "text": "Olá! Esta é uma mensagem de teste do Instagram."
    }
  }'
```

### 7.3 Testar Envio de Mensagem (Messenger)

Obtenha um PSID enviando uma mensagem para sua Facebook Page via Messenger.

```bash
curl -X POST https://seu-dominio.com/api/messages/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_API_KEY" \
  -d '{
    "provider": "meta",
    "platform": "messenger",
    "recipient": "PSID_DO_USUARIO",
    "message": {
      "text": "Olá! Esta é uma mensagem de teste do Messenger."
    }
  }'
```

### 7.4 Testar Recebimento de Mensagem

1. Envie uma mensagem para sua Facebook Page via Messenger
2. Envie uma mensagem para sua conta Instagram via Direct Message
3. Verifique os logs:

```bash
tail -f storage/logs/whatsapp-adapter.log | grep meta
```

4. Verifique no admin panel se as mensagens aparecem

### 7.5 Testar Envio de Mídia

```bash
curl -X POST https://seu-dominio.com/api/messages/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_API_KEY" \
  -d '{
    "provider": "meta",
    "recipient": "IGSID_OU_PSID",
    "message": {
      "type": "image",
      "url": "https://example.com/image.jpg"
    }
  }'
```

### 7.6 Testar Quick Replies

```bash
curl -X POST https://seu-dominio.com/api/messages/send \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_API_KEY" \
  -d '{
    "provider": "meta",
    "recipient": "IGSID_OU_PSID",
    "message": {
      "text": "Escolha uma opção:",
      "quick_replies": [
        {"title": "Opção 1", "payload": "OPTION_1"},
        {"title": "Opção 2", "payload": "OPTION_2"}
      ]
    }
  }'
```

## Permissões Necessárias

### Permissões da Facebook Page

- ✅ `pages_messaging` - Enviar e receber mensagens
- ✅ `pages_read_engagement` - Ler interações da página
- ✅ `pages_manage_metadata` - Gerenciar metadados
- ✅ `pages_show_list` - Listar páginas

### Permissões do Instagram

- ✅ `instagram_basic` - Acesso básico ao perfil
- ✅ `instagram_manage_messages` - Gerenciar mensagens
- ✅ `pages_show_list` - Listar páginas conectadas

### App Review

Algumas permissões requerem **App Review** da Meta:

- `pages_messaging` - Geralmente requer review
- `instagram_manage_messages` - Geralmente requer review

**Processo de Review:**

1. No dashboard do app, vá para **App Review** → **Permissions and Features**
2. Encontre as permissões necessárias
3. Clique em **"Request Advanced Access"**
4. Preencha o formulário explicando o uso
5. Forneça vídeo demonstrativo se solicitado
6. Aguarde aprovação (pode levar alguns dias)

⚠️ **Nota**: Durante desenvolvimento, você pode usar **Standard Access** que permite testar com contas de teste.

## Limitações e Restrições

### Janela de Mensagens de 24 Horas

- ✅ Você pode enviar mensagens **dentro de 24 horas** após a última mensagem do usuário
- ❌ Após 24 horas, você **não pode** enviar mensagens promocionais
- ⚠️ Após 24 horas, você pode usar **Message Tags** para casos específicos:
  - `CONFIRMED_EVENT_UPDATE` - Atualizações de eventos confirmados
  - `POST_PURCHASE_UPDATE` - Atualizações pós-compra
  - `ACCOUNT_UPDATE` - Atualizações de conta

**Exemplo de erro quando janela expirou:**

```json
{
  "error": {
    "message": "This message is sent outside of allowed window",
    "type": "OAuthException",
    "code": 2022
  }
}
```

### Limites de Mídia

**Instagram:**

- Imagens: Máximo 8MB (PNG, JPEG)
- Vídeos: Máximo 25MB (MP4, MOV)
- Áudio: Máximo 25MB (AAC, M4A)
- Documentos: Máximo 25MB (PDF)
- Múltiplas imagens: Até 10 por mensagem

**Facebook Messenger:**

- Imagens: Máximo 25MB (PNG, JPEG, GIF)
- Vídeos: Máximo 25MB (MP4, MOV)
- Áudio: Máximo 25MB (MP3, M4A)
- Documentos: Máximo 25MB (PDF, DOC, DOCX)
- Múltiplas imagens: 1 por mensagem (ou usar carousel)

### Rate Limits

- **Por Page**: ~200 requisições por minuto
- **Por App**: Varia baseado no uso
- **Mensagens**: Sem limite específico, mas sujeito a throttling

**Dica**: Implemente retry com exponential backoff para lidar com rate limits.

### Templates HSM

- ❌ Instagram e Messenger **não suportam** templates HSM do WhatsApp
- ✅ O sistema converte automaticamente templates para texto simples
- ⚠️ Placeholders `{{1}}`, `{{2}}` são substituídos pelos parâmetros

### Tipos de Mensagem

**Suportados:**

- ✅ Texto simples
- ✅ Imagens, vídeos, áudio, documentos
- ✅ Quick Replies (até 13)
- ✅ Generic Template (cards com botões)
- ✅ Button Template (apenas Messenger)

**Não Suportados:**

- ❌ Templates HSM/WhatsApp
- ❌ Location messages (em desenvolvimento)
- ❌ Contact cards (em desenvolvimento)

## Troubleshooting

### Erro: "Invalid OAuth access token"

**Causa**: Token expirado ou inválido.

**Solução:**

1. Verifique se está usando o Page Access Token (não User Access Token)
2. Gere um novo token de longa duração
3. Verifique se o token tem as permissões corretas

```bash
# Testar token
curl -X GET "https://graph.facebook.com/v21.0/me?access_token=SEU_TOKEN"
```

### Erro: "Account not eligible for messages" (36103)

**Causa**: Conta Instagram não é Professional/Business ou não está conectada.

**Solução:**

1. Converta a conta para Professional/Business
2. Conecte a conta à Facebook Page
3. Verifique se o usuário aceitou a solicitação de mensagem

### Erro: "Feature not available" (2534068)

**Causa**: Recurso não disponível na sua região ou para sua conta.

**Solução:**

1. Verifique se o recurso está disponível na sua região
2. Verifique se seu app tem as permissões necessárias
3. Consulte a documentação da Meta para disponibilidade

### Webhook não recebe mensagens

**Causa**: URL não acessível, assinatura inválida, ou eventos não subscritos.

**Solução:**

1. Verifique se a URL é HTTPS e acessível publicamente
2. Teste a URL: `curl https://seu-dominio.com/webhook/meta`
3. Verifique os logs do servidor
4. Confirme que subscreveu aos eventos corretos
5. Verifique a validação de assinatura HMAC

```bash
# Testar webhook localmente com ngrok
ngrok http 8000
# Use a URL gerada no dashboard Meta
```

### Erro: "This message is sent outside of allowed window" (2022)

**Causa**: Tentando enviar mensagem após 24 horas da última mensagem do usuário.

**Solução:**

1. Verifique o timestamp da última mensagem
2. Use Message Tags se aplicável
3. Aguarde o usuário enviar nova mensagem

### Erro: "Permission denied" (10)

**Causa**: App não tem permissões necessárias.

**Solução:**

1. Vá para **App Review** no dashboard
2. Solicite as permissões necessárias
3. Aguarde aprovação da Meta

### Mensagens não aparecem no admin panel

**Causa**: Webhook não está processando corretamente.

**Solução:**

1. Verifique os logs: `tail -f storage/logs/whatsapp-adapter.log`
2. Verifique se o webhook está salvando no banco de dados
3. Teste o processamento manualmente:

```php
<?php
// test-webhook.php
require 'vendor/autoload.php';

$payload = json_decode(file_get_contents('test-webhook-payload.json'), true);
$handler = new \App\Providers\Meta\MetaWebhookHandler(/* ... */);
$message = $handler->processIncomingMessage($payload);
var_dump($message);
```

### Rate limit excedido

**Causa**: Muitas requisições em curto período.

**Solução:**

1. Implemente rate limiting no cliente
2. Use exponential backoff para retries
3. Distribua requisições ao longo do tempo
4. Considere usar queue para processar mensagens

```php
// Exemplo de retry com backoff
$maxRetries = 3;
$attempt = 0;
$delay = 1; // segundos

while ($attempt < $maxRetries) {
    try {
        $result = $provider->sendText($request);
        break;
    } catch (RateLimitException $e) {
        $attempt++;
        if ($attempt >= $maxRetries) throw $e;
        sleep($delay);
        $delay *= 2; // Exponential backoff
    }
}
```

## Recursos Adicionais

### Documentação Oficial

- [Meta for Developers](https://developers.facebook.com/)
- [Messenger Platform API](https://developers.facebook.com/docs/messenger-platform)
- [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram)
- [Webhooks Reference](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Send API Reference](https://developers.facebook.com/docs/messenger-platform/reference/send-api)

### Ferramentas Úteis

- [Graph API Explorer](https://developers.facebook.com/tools/explorer/) - Testar chamadas à API
- [Webhook Tester](https://developers.facebook.com/tools/webhooks/) - Testar webhooks
- [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/) - Verificar tokens
- [ngrok](https://ngrok.com/) - Expor servidor local para testes

### Comunidade e Suporte

- [Meta Developer Community](https://developers.facebook.com/community/)
- [Stack Overflow - facebook-graph-api](https://stackoverflow.com/questions/tagged/facebook-graph-api)
- [Meta Developer Support](https://developers.facebook.com/support/)

### Exemplos de Código

Veja exemplos completos em:

- `examples/meta-send-text.php` - Enviar mensagem de texto
- `examples/meta-send-media.php` - Enviar mídia
- `examples/meta-quick-replies.php` - Enviar quick replies
- `examples/meta-webhook-handler.php` - Processar webhooks

### Changelog da API

Acompanhe mudanças na API:

- [Messenger Platform Changelog](https://developers.facebook.com/docs/messenger-platform/changelog)
- [Instagram API Changelog](https://developers.facebook.com/docs/instagram-api/changelog)

## Checklist de Setup

Use este checklist para garantir que tudo está configurado:

- [ ] App Meta criado
- [ ] App ID e App Secret obtidos
- [ ] Facebook Page criada/conectada
- [ ] Page ID obtido
- [ ] Instagram Professional Account criado
- [ ] Instagram conectado à Facebook Page
- [ ] Page Access Token gerado (longa duração ou permanente)
- [ ] Messenger adicionado ao app
- [ ] Instagram adicionado ao app
- [ ] Webhook URL configurado
- [ ] Verify Token criado
- [ ] Eventos de webhook subscritos (Messenger)
- [ ] Eventos de webhook subscritos (Instagram)
- [ ] Variáveis de ambiente configuradas
- [ ] Teste de envio de mensagem (Instagram) realizado
- [ ] Teste de envio de mensagem (Messenger) realizado
- [ ] Teste de recebimento de mensagem realizado
- [ ] Permissões solicitadas para App Review (se necessário)

## Próximos Passos

Após completar o setup:

1. **Teste em Produção**: Envie mensagens reais para usuários
2. **Monitore Logs**: Acompanhe erros e performance
3. **Configure Alertas**: Receba notificações de erros críticos
4. **Otimize Performance**: Implemente cache e rate limiting
5. **Documente Fluxos**: Crie documentação para sua equipe
6. **Treine Usuários**: Ensine sua equipe a usar o sistema

## Suporte

Se precisar de ajuda:

1. Consulte a [documentação da API](docs/API.md)
2. Verifique o [guia de troubleshooting](docs/TROUBLESHOOTING.md)
3. Abra uma issue no GitHub
4. Entre em contato com o suporte

---

**Última Atualização**: Janeiro 2025  
**Versão da API**: v21.0  
**Autor**: WhatsApp HSM Adapter Team
