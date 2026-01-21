# Multi-Platform Messaging Admin Panel

Interface web para gerir mensagens via WhatsApp, Instagram e Facebook Messenger.

## 🚀 Funcionalidades

### WhatsApp (via Infobip)

- ✅ Listar todos os templates HSM disponíveis na conta Infobip
- ✅ Enviar mensagens HSM para qualquer número
- ✅ Enviar mensagens de texto, mídia e interativas
- ✅ Receber e visualizar mensagens de resposta via webhook

### Instagram (via Meta)

- ✅ Enviar mensagens de texto para usuários do Instagram
- ✅ Enviar imagens, vídeos, áudio e documentos
- ✅ Enviar até 10 imagens em uma única mensagem
- ✅ Enviar Quick Replies (botões de resposta rápida)
- ✅ Receber mensagens e respostas via webhook
- ✅ Detecção automática de janela de 24 horas

### Facebook Messenger (via Meta)

- ✅ Enviar mensagens de texto para usuários do Messenger
- ✅ Enviar imagens, vídeos, áudio e documentos
- ✅ Enviar Quick Replies (até 13 botões)
- ✅ Enviar Button Template (botões de URL, postback, call)
- ✅ Receber mensagens e respostas via webhook
- ✅ Detecção automática de janela de 24 horas

### Geral

- ✅ Interface visual moderna e responsiva
- ✅ Atualização automática de mensagens a cada 10 segundos
- ✅ Filtro por provider (WhatsApp/Instagram/Messenger)
- ✅ Visualização diferenciada por plataforma com badges coloridos
- ✅ Comparação de recursos entre plataformas

## 📋 Requisitos

- PHP 7.4 ou superior
- Extensão cURL habilitada
- Servidor web (Apache, Nginx, ou PHP built-in server)
- **Para WhatsApp**: Conta Infobip com API Key
- **Para Instagram/Messenger**: App Meta configurado com Page Access Token (veja [Setup Guide](../docs/INSTAGRAM_SETUP.md))

## 🔧 Instalação

1. **Copie os ficheiros para o servidor web:**

   ```bash
   cp -r admin-panel /var/www/html/
   ```

2. **Configure as permissões:**

   ```bash
   chmod 755 admin-panel
   chmod 666 admin-panel/messages.json
   chmod 666 admin-panel/webhook.log
   ```

3. **Configure as credenciais:**

   Edite o ficheiro `api.php` e configure:

   **Para WhatsApp (Infobip):**

   - `infobip_api_key`: Sua chave API da Infobip
   - `infobip_sender`: Número do sender (ex: 351927587119)

   **Para Instagram/Messenger (Meta):**

   - `meta_page_access_token`: Token de acesso da Facebook Page
   - `meta_page_id`: ID da sua Facebook Page
   - `meta_app_secret`: App Secret do seu app Meta
   - `meta_verify_token`: Token personalizado para validação de webhook

   📖 **Guia completo de setup Meta**: Consulte [docs/INSTAGRAM_SETUP.md](../docs/INSTAGRAM_SETUP.md) para instruções detalhadas sobre como obter estas credenciais.

## 🌐 Uso Local (Desenvolvimento)

Para testar localmente, use o servidor built-in do PHP:

```bash
cd admin-panel
php -S localhost:8080
```

Depois acesse: http://localhost:8080

## 📡 Configurar Webhook (Produção)

Para receber mensagens de resposta, você precisa configurar webhooks para cada provider.

### WhatsApp (Infobip)

Configure o webhook na Infobip:

- URL: `https://seu-dominio.com/admin-panel/webhook.php`
- Método: POST
- Tipo: Incoming Messages

### Instagram e Messenger (Meta)

Configure o webhook no Meta App:

1. Acesse [Meta for Developers](https://developers.facebook.com/)
2. Vá para seu app → **Messenger** → **Settings**
3. Na seção **Webhooks**, clique em **"Add Callback URL"**
4. Configure:
   - **Callback URL**: `https://seu-dominio.com/webhook/meta`
   - **Verify Token**: Token personalizado (configure em `api.php`)
5. Subscreva aos eventos:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`

📖 **Guia completo**: Consulte [docs/INSTAGRAM_SETUP.md](../docs/INSTAGRAM_SETUP.md) para instruções detalhadas.

### Opção: Desenvolvimento Local com ngrok

Para testar webhooks localmente:

1. **Instale o ngrok:**

   ```bash
   brew install ngrok  # macOS
   # ou baixe de https://ngrok.com/download
   ```

2. **Inicie o servidor PHP:**

   ```bash
   cd admin-panel
   php -S localhost:8080
   ```

3. **Crie um túnel ngrok:**

   ```bash
   ngrok http 8080
   ```

4. **Configure nos providers:**
   - **Infobip**: `https://abc123.ngrok.io/webhook.php`
   - **Meta**: `https://abc123.ngrok.io/webhook/meta`

## 📱 Como Usar

### Selecionar Provider

1. Abra a interface no navegador
2. No topo do painel de envio, selecione o provider desejado:
   - **WhatsApp (Infobip)** - Para mensagens WhatsApp via Infobip
   - **Instagram (Meta)** - Para mensagens Instagram Direct
   - **Facebook Messenger (Meta)** - Para mensagens Messenger

A interface adapta-se automaticamente mostrando apenas os campos relevantes para cada provider.

### 1. Enviar Mensagens WhatsApp

#### 1.1 Listar Templates

- Os templates HSM são carregados automaticamente ao selecionar WhatsApp
- Clique em "🔄 Atualizar Templates" para recarregar

#### 1.2 Enviar Mensagem HSM

1. Selecione **WhatsApp (Infobip)** como provider
2. Clique num template da lista à esquerda
3. Insira o número de destino (com código do país, ex: 351961725398)
4. Selecione o idioma
5. Clique em "📨 Enviar Mensagem"

### 2. Enviar Mensagens Instagram

#### 2.1 Obter IGSID do Destinatário

O **IGSID** (Instagram-Scoped ID) é obtido automaticamente quando um usuário envia uma mensagem para sua conta Instagram:

1. Peça ao usuário para enviar uma mensagem Direct para sua conta Instagram Professional
2. A mensagem aparecerá no painel inferior com o IGSID
3. Copie o IGSID para usar no envio

#### 2.2 Enviar Mensagem de Texto

1. Selecione **Instagram (Meta)** como provider
2. Cole o IGSID do destinatário
3. Selecione **Texto** como tipo de mensagem
4. Digite sua mensagem
5. Clique em "📨 Enviar Mensagem"

⚠️ **Janela de 24 horas**: Você só pode enviar mensagens dentro de 24 horas após a última mensagem do usuário.

#### 2.3 Enviar Mídia

1. Selecione **Instagram (Meta)** como provider
2. Cole o IGSID do destinatário
3. Selecione **Mídia** como tipo de mensagem
4. Escolha o tipo de mídia:
   - **Imagem** (PNG, JPEG - máx 8MB)
   - **Vídeo** (MP4, MOV - máx 25MB)
   - **Áudio** (AAC, M4A - máx 25MB)
   - **Documento** (PDF - máx 25MB)
5. Cole a URL da mídia (deve ser acessível publicamente)
6. Clique em "📨 Enviar Mensagem"

#### 2.4 Enviar Múltiplas Imagens

Instagram permite enviar até 10 imagens em uma única mensagem:

1. Selecione **Instagram (Meta)** como provider
2. Cole o IGSID do destinatário
3. Selecione **Múltiplas Imagens** como tipo de mensagem
4. Cole as URLs das imagens (uma por linha, máximo 10)
5. Clique em "📨 Enviar Mensagem"

Exemplo:

```
https://example.com/image1.jpg
https://example.com/image2.jpg
https://example.com/image3.jpg
```

#### 2.5 Enviar Quick Replies

Quick Replies são botões de resposta rápida (máximo 13):

1. Selecione **Instagram (Meta)** como provider
2. Cole o IGSID do destinatário
3. Selecione **Quick Replies** como tipo de mensagem
4. Digite a mensagem de texto
5. Adicione os botões no formato: `Título|payload` (um por linha)
6. Clique em "📨 Enviar Mensagem"

Exemplo:

```
Sim|yes
Não|no
Mais informações|more_info
```

### 3. Enviar Mensagens Facebook Messenger

#### 3.1 Obter PSID do Destinatário

O **PSID** (Page-Scoped ID) é obtido automaticamente quando um usuário envia uma mensagem para sua Facebook Page:

1. Peça ao usuário para enviar uma mensagem para sua Facebook Page via Messenger
2. A mensagem aparecerá no painel inferior com o PSID
3. Copie o PSID para usar no envio

#### 3.2 Enviar Mensagem de Texto

1. Selecione **Facebook Messenger (Meta)** como provider
2. Cole o PSID do destinatário
3. Selecione **Texto** como tipo de mensagem
4. Digite sua mensagem
5. Clique em "📨 Enviar Mensagem"

⚠️ **Janela de 24 horas**: Você só pode enviar mensagens dentro de 24 horas após a última mensagem do usuário.

#### 3.3 Enviar Mídia

1. Selecione **Facebook Messenger (Meta)** como provider
2. Cole o PSID do destinatário
3. Selecione **Mídia** como tipo de mensagem
4. Escolha o tipo de mídia:
   - **Imagem** (PNG, JPEG, GIF - máx 25MB)
   - **Vídeo** (MP4, MOV - máx 25MB)
   - **Áudio** (MP3, M4A - máx 25MB)
   - **Documento** (PDF, DOC - máx 25MB)
5. Cole a URL da mídia (deve ser acessível publicamente)
6. Clique em "📨 Enviar Mensagem"

#### 3.4 Enviar Quick Replies

Quick Replies são botões de resposta rápida (máximo 13):

1. Selecione **Facebook Messenger (Meta)** como provider
2. Cole o PSID do destinatário
3. Selecione **Quick Replies** como tipo de mensagem
4. Digite a mensagem de texto
5. Adicione os botões no formato: `Título|payload` (um por linha)
6. Clique em "📨 Enviar Mensagem"

#### 3.5 Enviar Button Template

Button Template permite enviar botões de ação (máximo 3):

1. Selecione **Facebook Messenger (Meta)** como provider
2. Cole o PSID do destinatário
3. Selecione **Button Template** como tipo de mensagem
4. Digite o texto principal
5. Adicione os botões no formato: `tipo|título|valor` (um por linha)
6. Clique em "📨 Enviar Mensagem"

Tipos de botões:

- **url** - Abre um link: `url|Visitar Site|https://example.com`
- **postback** - Envia payload: `postback|Confirmar|confirm_action`
- **phone_number** - Inicia chamada: `phone_number|Ligar|+351961234567`

Exemplo:

```
url|Visitar Site|https://example.com
postback|Confirmar Pedido|confirm_order
phone_number|Ligar Agora|+351961234567
```

### 4. Ver Mensagens Recebidas

- As mensagens recebidas aparecem no painel inferior
- Atualização automática a cada 10 segundos
- Clique em "🔄 Atualizar Mensagens" para atualizar manualmente
- Use o filtro de provider para ver apenas mensagens de uma plataforma específica

#### Identificação Visual

Cada mensagem tem um badge colorido indicando a plataforma:

- 🟢 **WhatsApp** - Badge verde
- 🔴 **Instagram** - Badge rosa/vermelho
- 🔵 **Messenger** - Badge azul

#### Informações Exibidas

- **WhatsApp**: Número de telefone do remetente
- **Instagram**: IGSID do remetente
- **Messenger**: PSID do remetente
- **Timestamp**: Data e hora da mensagem
- **Conteúdo**: Texto ou tipo de mídia recebida

## 📂 Estrutura de Ficheiros

```
admin-panel/
├── index.html          # Interface web (frontend)
├── api.php            # Backend API (templates, envio)
├── webhook.php        # Endpoint para receber webhooks
├── messages.json      # Armazenamento de mensagens (criado automaticamente)
├── webhook.log        # Log de webhooks recebidos (criado automaticamente)
└── README.md          # Esta documentação
```

## 📊 Comparação de Recursos

Entenda as diferenças entre as plataformas:

| Recurso                 | WhatsApp           | Instagram         | Messenger         |
| ----------------------- | ------------------ | ----------------- | ----------------- |
| **Templates HSM**       | ✅ Sim             | ❌ Não            | ❌ Não            |
| **Mensagens de Texto**  | ✅ Sim             | ✅ Sim            | ✅ Sim            |
| **Imagens**             | ✅ Sim             | ✅ Sim (máx 8MB)  | ✅ Sim (máx 25MB) |
| **Múltiplas Imagens**   | ❌ Não             | ✅ Sim (até 10)   | ⚠️ Via Carousel   |
| **Vídeos**              | ✅ Sim             | ✅ Sim (máx 25MB) | ✅ Sim (máx 25MB) |
| **Áudio**               | ✅ Sim             | ✅ Sim (máx 25MB) | ✅ Sim (máx 25MB) |
| **Documentos**          | ✅ Sim             | ✅ Sim (máx 25MB) | ✅ Sim (máx 25MB) |
| **Quick Replies**       | ✅ Sim             | ✅ Sim (máx 13)   | ✅ Sim (máx 13)   |
| **Button Template**     | ❌ Não             | ❌ Não            | ✅ Sim (máx 3)    |
| **Janela de Mensagens** | ❌ Sem restrição   | ⚠️ 24 horas       | ⚠️ 24 horas       |
| **Identificador**       | Número de telefone | IGSID             | PSID              |
| **Iniciar Conversa**    | ✅ Sim             | ❌ Não            | ❌ Não            |

### Legendas

- ✅ **Sim**: Recurso totalmente suportado
- ❌ **Não**: Recurso não suportado
- ⚠️ **Limitado**: Recurso com limitações ou restrições

### Notas Importantes

#### Janela de 24 Horas (Instagram e Messenger)

- Você só pode enviar mensagens dentro de 24 horas após a última mensagem do usuário
- Após 24 horas, você precisa aguardar o usuário enviar nova mensagem
- Message Tags podem ser usados em casos específicos (atualizações de pedidos, eventos, etc.)

#### Iniciar Conversas

- **WhatsApp**: Você pode iniciar conversas usando templates HSM aprovados
- **Instagram/Messenger**: O usuário deve enviar a primeira mensagem
- Você não pode enviar mensagens não solicitadas no Instagram/Messenger

#### Identificadores

- **WhatsApp**: Usa número de telefone (ex: 351961725398)
- **Instagram**: Usa IGSID - Instagram-Scoped ID (ex: 1234567890)
- **Messenger**: Usa PSID - Page-Scoped ID (ex: 9876543210)

## ❓ FAQ - Perguntas Frequentes

### Geral

**P: Posso usar o mesmo painel para todas as plataformas?**  
R: Sim! O painel suporta WhatsApp, Instagram e Messenger. Basta selecionar o provider desejado no dropdown.

**P: Preciso de contas separadas para cada plataforma?**  
R: Sim. Você precisa:

- Conta Infobip para WhatsApp
- App Meta + Facebook Page para Instagram e Messenger
- Conta Instagram Professional para Instagram

**P: As mensagens são armazenadas em banco de dados?**  
R: Por padrão, as mensagens são armazenadas em `messages.json` (arquivo local). Para produção, recomenda-se usar banco de dados (MySQL, PostgreSQL).

### Instagram

**P: Como obtenho o IGSID de um usuário?**  
R: O IGSID é obtido automaticamente quando o usuário envia uma mensagem Direct para sua conta Instagram. A mensagem aparecerá no painel com o IGSID.

**P: Posso enviar mensagens para qualquer usuário do Instagram?**  
R: Não. O usuário deve enviar a primeira mensagem para você. Você não pode iniciar conversas não solicitadas.

**P: Por que não posso enviar mensagens após 24 horas?**  
R: Instagram tem uma janela de mensagens de 24 horas. Após esse período, você precisa aguardar o usuário enviar nova mensagem.

**P: Posso usar templates HSM no Instagram?**  
R: Não. Instagram não suporta templates HSM. Use mensagens de texto, mídia ou interativas.

**P: Quantas imagens posso enviar de uma vez?**  
R: Até 10 imagens em uma única mensagem no Instagram.

**P: Qual o tamanho máximo de imagens?**  
R: 8MB para imagens no Instagram. Vídeos e áudio podem ter até 25MB.

### Facebook Messenger

**P: Como obtenho o PSID de um usuário?**  
R: O PSID é obtido automaticamente quando o usuário envia uma mensagem para sua Facebook Page via Messenger. A mensagem aparecerá no painel com o PSID.

**P: Qual a diferença entre Instagram e Messenger?**  
R: Ambos usam a mesma API Meta, mas têm algumas diferenças:

- Instagram permite até 10 imagens por mensagem
- Messenger permite Button Template (botões de URL, postback, call)
- Messenger tem limite de 25MB para imagens (vs 8MB no Instagram)

**P: Posso enviar mensagens para usuários que não têm Facebook?**  
R: Não. O usuário precisa ter uma conta Facebook e enviar mensagem para sua Page primeiro.

**P: O que é Button Template?**  
R: É um tipo de mensagem do Messenger que permite enviar até 3 botões de ação (abrir URL, enviar postback, ou iniciar chamada).

### WhatsApp

**P: Preciso aprovar templates antes de usar?**  
R: Sim. Templates HSM precisam ser aprovados pela Infobip/WhatsApp antes de serem usados.

**P: Posso enviar mensagens sem templates?**  
R: Sim, mas apenas dentro de 24 horas após a última mensagem do usuário. Para iniciar conversas, você precisa usar templates aprovados.

**P: Como adiciono novos templates?**  
R: Templates são gerenciados no portal da Infobip. Após criar e aprovar um template lá, ele aparecerá automaticamente no painel.

### Webhooks

**P: Como testo webhooks localmente?**  
R: Use ngrok para expor seu servidor local:

```bash
ngrok http 8080
```

Use a URL gerada (ex: https://abc123.ngrok.io) na configuração do webhook.

**P: Por que meu webhook não recebe mensagens?**  
R: Verifique:

1. URL é HTTPS e acessível publicamente
2. Eventos corretos estão subscritos no provider
3. Validação de assinatura está correta
4. Logs do servidor para erros

**P: Posso usar o mesmo webhook para todas as plataformas?**  
R: Não. WhatsApp (Infobip) e Meta (Instagram/Messenger) usam endpoints diferentes:

- WhatsApp: `/webhook.php`
- Meta: `/webhook/meta`

### Segurança

**P: Como protejo minhas credenciais?**  
R:

1. Nunca commite credenciais no Git
2. Use arquivo `.env` (excluído do Git)
3. Em produção, use variáveis de ambiente
4. Rotacione tokens regularmente

**P: Preciso validar webhooks?**  
R: Sim! Sempre valide a assinatura dos webhooks para garantir que vêm do provider legítimo.

**P: Como adiciono autenticação ao painel?**  
R: Para produção, adicione:

1. Sistema de login/senha
2. Sessões PHP ou tokens JWT
3. Proteção contra CSRF
4. Rate limiting

### Produção

**P: Este painel é adequado para produção?**  
R: O painel é ideal para desenvolvimento e testes. Para produção, recomenda-se:

1. Adicionar autenticação
2. Usar banco de dados em vez de JSON
3. Implementar rate limiting
4. Adicionar monitoramento e alertas
5. Usar HTTPS com certificado válido

**P: Como monitoro o uso?**  
R: Verifique:

1. Logs do servidor: `tail -f storage/logs/whatsapp-adapter.log`
2. Logs de webhook: `tail -f admin-panel/webhook.log`
3. Métricas de API dos providers
4. Implemente dashboard de monitoramento

**P: Como faço backup das mensagens?**  
R:

1. Arquivo JSON: `cp messages.json messages.backup.json`
2. Banco de dados: Use ferramentas de backup do DB
3. Automatize backups com cron jobs
4. Armazene backups em local seguro

## 🔒 Segurança

⚠️ **IMPORTANTE**: Este painel é para desenvolvimento/testes. Para produção:

### 1. Adicione Autenticação

**Proteja o acesso com login/senha:**

```php
// Exemplo simples com sessões PHP
session_start();
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit;
}
```

**Ou use tokens JWT:**

```php
// Valide token em cada requisição
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!validateJWT($token)) {
    http_response_code(401);
    exit('Unauthorized');
}
```

### 2. Proteja Credenciais

**Mova configurações para arquivo `.env`:**

```bash
# .env
INFOBIP_API_KEY=sua_chave_aqui
META_PAGE_ACCESS_TOKEN=seu_token_aqui
META_APP_SECRET=seu_secret_aqui
```

**Nunca commite credenciais no Git:**

```bash
# .gitignore
.env
config.php
messages.json
webhook.log
```

### 3. Valide Webhooks

**WhatsApp (Infobip):**

```php
// Valide assinatura do webhook
$signature = $_SERVER['HTTP_X_INFOBIP_SIGNATURE'] ?? '';
$body = file_get_contents('php://input');
$expectedSignature = hash_hmac('sha256', $body, $apiSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}
```

**Instagram/Messenger (Meta):**

```php
// Valide X-Hub-Signature-256
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$body = file_get_contents('php://input');
$expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $appSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}
```

### 4. Use HTTPS

- Sempre use HTTPS em produção
- Configure certificado SSL válido (Let's Encrypt é gratuito)
- Redirecione HTTP para HTTPS automaticamente

### 5. Implemente Rate Limiting

```php
// Exemplo simples de rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$requests = getRequestCount($ip); // Implemente com Redis ou arquivo

if ($requests > 100) { // 100 requisições por hora
    http_response_code(429);
    exit('Too many requests');
}
```

### 6. Sanitize Inputs

```php
// Sempre sanitize inputs do usuário
$phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
$message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
```

### 7. Proteja Dados Sensíveis

- Não logue IGSIDs/PSIDs completos (use hash ou mascaramento)
- Não logue tokens de acesso
- Implemente rotação de tokens
- Use criptografia para dados sensíveis no banco

### 8. Configure Permissões de Arquivos

```bash
# Permissões recomendadas
chmod 755 admin-panel/
chmod 644 admin-panel/*.php
chmod 644 admin-panel/*.html
chmod 666 admin-panel/messages.json
chmod 666 admin-panel/webhook.log
```

### 9. Monitore Atividades Suspeitas

- Implemente logging de todas as ações
- Configure alertas para atividades anormais
- Monitore tentativas de acesso não autorizado
- Revise logs regularmente

### 10. Mantenha Sistema Atualizado

- Atualize PHP regularmente
- Atualize dependências
- Aplique patches de segurança
- Monitore vulnerabilidades conhecidas

## 🐛 Troubleshooting

### WhatsApp (Infobip)

#### Templates não carregam

- Verifique se a API key está correta em `api.php`
- Verifique se o número do sender está correto
- Verifique logs do servidor: `tail -f /var/log/apache2/error.log`

#### Mensagens não são enviadas

- Verifique se o número de destino está no formato correto (sem + ou 00)
- Verifique se o template está aprovado na Infobip
- Verifique se o idioma do template está correto

#### Webhook não recebe mensagens

- Verifique se a URL do webhook está configurada na Infobip
- Verifique se a URL é acessível publicamente (use ngrok para testes)
- Verifique o ficheiro `webhook.log` para ver se há requisições
- Verifique permissões do ficheiro `messages.json`

### Instagram e Messenger (Meta)

#### Erro: "Invalid OAuth access token"

**Causa**: Token expirado ou inválido.

**Solução**:

1. Verifique se está usando o Page Access Token (não User Access Token)
2. Gere um novo token de longa duração
3. Verifique se o token tem as permissões corretas

```bash
# Testar token
curl -X GET "https://graph.facebook.com/v21.0/me?access_token=SEU_TOKEN"
```

#### Erro: "Account not eligible for messages" (36103)

**Causa**: Conta Instagram não é Professional/Business ou não está conectada.

**Solução**:

1. Converta a conta Instagram para Professional/Business
2. Conecte a conta à Facebook Page
3. Verifique se o usuário iniciou a conversa primeiro

#### Erro: "This message is sent outside of allowed window" (2022)

**Causa**: Tentando enviar mensagem após 24 horas da última mensagem do usuário.

**Solução**:

1. Verifique o timestamp da última mensagem do usuário
2. Aguarde o usuário enviar nova mensagem
3. Use Message Tags se aplicável (casos específicos)

O painel mostra um aviso quando a janela de 24 horas está próxima de expirar.

#### Webhook não recebe mensagens (Meta)

**Causa**: URL não acessível, assinatura inválida, ou eventos não subscritos.

**Solução**:

1. Verifique se a URL é HTTPS e acessível publicamente
2. Teste a URL: `curl https://seu-dominio.com/webhook/meta`
3. Verifique os logs do servidor
4. Confirme que subscreveu aos eventos corretos no Meta App
5. Verifique a validação de assinatura HMAC no código

```bash
# Testar webhook localmente com ngrok
ngrok http 8080
# Use a URL gerada no dashboard Meta
```

#### Não consigo obter IGSID/PSID

**Causa**: Usuário não enviou mensagem primeiro.

**Solução**:

1. Peça ao usuário para enviar uma mensagem Direct (Instagram) ou Messenger (Facebook)
2. A mensagem aparecerá no painel com o IGSID/PSID
3. Copie o ID para usar no envio

**Nota**: Você não pode iniciar conversas com usuários que nunca enviaram mensagem para você.

#### Mídia não é enviada

**Causa**: URL inacessível ou formato/tamanho inválido.

**Solução**:

1. Verifique se a URL da mídia é acessível publicamente
2. Teste a URL no navegador
3. Verifique o tamanho do arquivo:
   - Instagram: Imagens máx 8MB, outros máx 25MB
   - Messenger: Todos os tipos máx 25MB
4. Verifique o formato do arquivo (PNG, JPEG, MP4, etc.)

#### Quick Replies não aparecem

**Causa**: Formato incorreto ou limite excedido.

**Solução**:

1. Verifique o formato: `Título|payload` (um por linha)
2. Máximo de 13 quick replies
3. Título máximo de 20 caracteres
4. Não use caracteres especiais no payload

#### Button Template não funciona (Messenger)

**Causa**: Formato incorreto ou tipo de botão inválido.

**Solução**:

1. Verifique o formato: `tipo|título|valor`
2. Tipos válidos: `url`, `postback`, `phone_number`
3. Máximo de 3 botões
4. URLs devem começar com `http://` ou `https://`
5. Números de telefone devem incluir código do país

#### Mensagens não aparecem no painel

**Causa**: Webhook não está processando corretamente.

**Solução**:

1. Verifique os logs: `tail -f storage/logs/whatsapp-adapter.log`
2. Verifique se o webhook está salvando no banco de dados
3. Verifique permissões do arquivo `messages.json`
4. Teste o processamento manualmente com payload de exemplo

## 📊 Logs

### Ver logs do webhook:

```bash
tail -f admin-panel/webhook.log
```

### Ver mensagens armazenadas:

```bash
cat admin-panel/messages.json | jq
```

### Ver logs da aplicação:

```bash
tail -f storage/logs/whatsapp-adapter.log
```

### Filtrar logs por provider:

```bash
# WhatsApp
tail -f storage/logs/whatsapp-adapter.log | grep infobip

# Instagram
tail -f storage/logs/whatsapp-adapter.log | grep instagram

# Messenger
tail -f storage/logs/whatsapp-adapter.log | grep messenger
```

### Monitorar webhooks em tempo real:

```bash
# Todos os webhooks
tail -f admin-panel/webhook.log

# Apenas erros
tail -f admin-panel/webhook.log | grep ERROR

# Apenas sucessos
tail -f admin-panel/webhook.log | grep SUCCESS
```

## 🔄 Atualização

Para atualizar configurações:

### WhatsApp (Infobip)

1. Edite `api.php`
2. Atualize as variáveis no array `$config`:
   ```php
   'infobip_api_key' => 'nova_chave',
   'infobip_sender' => 'novo_sender'
   ```
3. Não é necessário reiniciar o servidor

### Instagram/Messenger (Meta)

1. Edite `api.php`
2. Atualize as variáveis Meta:
   ```php
   'meta_page_access_token' => 'novo_token',
   'meta_page_id' => 'novo_page_id',
   'meta_app_secret' => 'novo_secret'
   ```
3. Não é necessário reiniciar o servidor

### Atualizar Token Meta (Expirado)

Se seu Page Access Token expirou:

1. Gere novo token no Meta App dashboard
2. Converta para token de longa duração:
   ```bash
   curl -X GET "https://graph.facebook.com/v21.0/oauth/access_token?\
   grant_type=fb_exchange_token&\
   client_id=SEU_APP_ID&\
   client_secret=SEU_APP_SECRET&\
   fb_exchange_token=SEU_TOKEN_CURTA_DURACAO"
   ```
3. Atualize em `api.php`

### Limpar Cache de Mensagens

```bash
# Backup primeiro
cp admin-panel/messages.json admin-panel/messages.backup.json

# Limpar
echo "[]" > admin-panel/messages.json
```

### Limpar Logs

```bash
# Backup primeiro
cp admin-panel/webhook.log admin-panel/webhook.backup.log

# Limpar
echo "" > admin-panel/webhook.log
```

## 📞 Suporte

### Documentação

- **API Geral**: [docs/API.md](../docs/API.md)
- **Setup Instagram/Messenger**: [docs/INSTAGRAM_SETUP.md](../docs/INSTAGRAM_SETUP.md)
- **Credenciais Meta**: [docs/META_CREDENTIALS_SETUP.md](../docs/META_CREDENTIALS_SETUP.md)
- **Troubleshooting**: [docs/TROUBLESHOOTING.md](../docs/TROUBLESHOOTING.md)

### Problemas com Providers

**Infobip (WhatsApp)**:

- Documentação: https://www.infobip.com/docs/api
- Suporte: https://www.infobip.com/contact

**Meta (Instagram/Messenger)**:

- Documentação: https://developers.facebook.com/docs/messenger-platform
- Developer Community: https://developers.facebook.com/community/
- Suporte: https://developers.facebook.com/support/

### Recursos Úteis

**Ferramentas de Teste**:

- [Graph API Explorer](https://developers.facebook.com/tools/explorer/) - Testar chamadas à Meta API
- [Webhook Tester](https://developers.facebook.com/tools/webhooks/) - Testar webhooks Meta
- [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/) - Verificar tokens Meta
- [ngrok](https://ngrok.com/) - Expor servidor local para testes

**Comunidade**:

- Stack Overflow: [facebook-graph-api](https://stackoverflow.com/questions/tagged/facebook-graph-api)
- Stack Overflow: [whatsapp-business-api](https://stackoverflow.com/questions/tagged/whatsapp-business-api)

### Reportar Problemas

Se encontrar bugs ou tiver sugestões:

1. Verifique a documentação primeiro
2. Consulte o guia de troubleshooting
3. Abra uma issue no GitHub (se aplicável)
4. Entre em contato com o suporte técnico

### Contribuir

Contribuições são bem-vindas! Para contribuir:

1. Fork o repositório
2. Crie uma branch para sua feature
3. Faça commit das suas mudanças
4. Abra um Pull Request
5. Aguarde review

## 📝 Notas

### Armazenamento de Mensagens

- As mensagens são armazenadas em `messages.json` (ficheiro local)
- Para produção, considere usar uma base de dados (MySQL, PostgreSQL)
- O painel não requer base de dados para funcionar
- Ideal para testes e desenvolvimento rápido

### Limitações Conhecidas

**WhatsApp**:

- Templates precisam ser aprovados antes do uso
- Limite de caracteres por mensagem
- Rate limits da Infobip aplicam-se

**Instagram**:

- Janela de mensagens de 24 horas
- Usuário deve enviar primeira mensagem
- Templates HSM não suportados
- Imagens limitadas a 8MB
- Máximo 10 imagens por mensagem

**Messenger**:

- Janela de mensagens de 24 horas
- Usuário deve enviar primeira mensagem
- Templates HSM não suportados
- Button Template limitado a 3 botões

### Performance

- Atualização automática de mensagens a cada 10 segundos
- Use cache para melhorar performance em produção
- Implemente paginação para grandes volumes de mensagens
- Considere usar WebSockets para atualizações em tempo real

### Escalabilidade

Para ambientes de alta carga:

1. **Use banco de dados** em vez de JSON
2. **Implemente queue** para processar webhooks assincronamente
3. **Use cache** (Redis, Memcached) para dados frequentes
4. **Distribua carga** com load balancer
5. **Monitore performance** com ferramentas de APM

### Compatibilidade

- **PHP**: 7.4 ou superior (recomendado 8.1+)
- **Navegadores**: Chrome, Firefox, Safari, Edge (versões recentes)
- **Mobile**: Interface responsiva funciona em dispositivos móveis
- **APIs**: Infobip API v1, Meta Graph API v21.0

### Próximas Funcionalidades

Funcionalidades planejadas para futuras versões:

- [ ] Suporte para mais providers (Twilio, Vonage)
- [ ] Dashboard de analytics e métricas
- [ ] Agendamento de mensagens
- [ ] Templates personalizados
- [ ] Integração com CRM
- [ ] API REST completa
- [ ] Autenticação multi-usuário
- [ ] Suporte para chatbots
- [ ] Exportação de relatórios

### Changelog

**v2.0.0** (Janeiro 2025)

- ✨ Adicionado suporte para Instagram Messaging API
- ✨ Adicionado suporte para Facebook Messenger API
- ✨ Interface multi-provider com seleção de plataforma
- ✨ Detecção automática de plataforma (Instagram vs Messenger)
- ✨ Suporte para múltiplas imagens (Instagram)
- ✨ Suporte para Button Template (Messenger)
- ✨ Badges coloridos por provider
- ✨ Filtro de mensagens por provider
- 📚 Documentação expandida com FAQ e guias

**v1.0.0** (Dezembro 2024)

- 🎉 Versão inicial com suporte WhatsApp (Infobip)
- ✅ Listagem de templates HSM
- ✅ Envio de mensagens HSM
- ✅ Recebimento via webhook
- ✅ Interface web responsiva

---

**Última Atualização**: Janeiro 2025  
**Versão**: 2.0.0  
**Autor**: WhatsApp HSM Adapter Team  
**Licença**: MIT
