# WhatsApp HSM Admin Panel

Interface web para gerir templates HSM e mensagens WhatsApp via Infobip.

## 🚀 Funcionalidades

- ✅ Listar todos os templates HSM disponíveis na conta Infobip
- ✅ Enviar mensagens HSM para qualquer número
- ✅ Receber e visualizar mensagens de resposta via webhook
- ✅ Interface visual moderna e responsiva
- ✅ Atualização automática de mensagens a cada 10 segundos

## 📋 Requisitos

- PHP 7.4 ou superior
- Extensão cURL habilitada
- Servidor web (Apache, Nginx, ou PHP built-in server)

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

3. **Edite o ficheiro `api.php` e configure:**
   - `infobip_api_key`: Sua chave API da Infobip
   - `infobip_sender`: Número do sender (ex: 351927587119)

## 🌐 Uso Local (Desenvolvimento)

Para testar localmente, use o servidor built-in do PHP:

```bash
cd admin-panel
php -S localhost:8080
```

Depois acesse: http://localhost:8080

## 📡 Configurar Webhook (Produção)

Para receber mensagens de resposta, você precisa configurar o webhook na Infobip:

### Opção 1: Servidor Público

1. Faça upload dos ficheiros para um servidor com HTTPS
2. Configure o webhook na Infobip:
   - URL: `https://seu-dominio.com/admin-panel/webhook.php`
   - Método: POST
   - Tipo: Incoming Messages

### Opção 2: Desenvolvimento Local com ngrok

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

4. **Configure na Infobip:**
   - Copie a URL HTTPS do ngrok (ex: https://abc123.ngrok.io)
   - Configure webhook: `https://abc123.ngrok.io/webhook.php`

## 📱 Como Usar

### 1. Listar Templates

- Abra a interface no navegador
- Os templates são carregados automaticamente
- Clique em "🔄 Atualizar Templates" para recarregar

### 2. Enviar Mensagem HSM

1. Clique num template da lista à esquerda
2. Insira o número de destino (com código do país, ex: 351961725398)
3. Selecione o idioma
4. Clique em "📨 Enviar Mensagem"

### 3. Ver Respostas

- As mensagens recebidas aparecem no painel inferior
- Atualização automática a cada 10 segundos
- Clique em "🔄 Atualizar Mensagens" para atualizar manualmente

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

## 🔒 Segurança

⚠️ **IMPORTANTE**: Este painel é para desenvolvimento/testes. Para produção:

1. **Adicione autenticação:**

   - Proteja o acesso com login/senha
   - Use sessões PHP ou tokens JWT

2. **Proteja a API Key:**

   - Mova a configuração para ficheiro `.env`
   - Nunca commite a API key no Git

3. **Valide o webhook:**

   - Implemente validação de assinatura da Infobip
   - Verifique IPs de origem

4. **Use HTTPS:**
   - Sempre use HTTPS em produção
   - Configure certificado SSL válido

## 🐛 Troubleshooting

### Templates não carregam

- Verifique se a API key está correta em `api.php`
- Verifique se o número do sender está correto
- Verifique logs do servidor: `tail -f /var/log/apache2/error.log`

### Mensagens não são enviadas

- Verifique se o número de destino está no formato correto (sem + ou 00)
- Verifique se o template está aprovado na Infobip
- Verifique se o idioma do template está correto

### Webhook não recebe mensagens

- Verifique se a URL do webhook está configurada na Infobip
- Verifique se a URL é acessível publicamente (use ngrok para testes)
- Verifique o ficheiro `webhook.log` para ver se há requisições
- Verifique permissões do ficheiro `messages.json`

## 📊 Logs

### Ver logs do webhook:

```bash
tail -f admin-panel/webhook.log
```

### Ver mensagens armazenadas:

```bash
cat admin-panel/messages.json | jq
```

## 🔄 Atualização

Para atualizar a API key ou configurações:

1. Edite `api.php`
2. Atualize as variáveis no array `$config`
3. Não é necessário reiniciar o servidor

## 📞 Suporte

Para problemas com a API Infobip:

- Documentação: https://www.infobip.com/docs/api
- Suporte: https://www.infobip.com/contact

## 📝 Notas

- As mensagens são armazenadas em `messages.json` (ficheiro local)
- Para produção, considere usar uma base de dados (MySQL, PostgreSQL)
- O painel não requer base de dados para funcionar
- Ideal para testes e desenvolvimento rápido
