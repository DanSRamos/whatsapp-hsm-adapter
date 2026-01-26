# 🔗 Configuração do Webhook Meta - Informações Completas

## ✅ Status Atual

- ✅ ngrok instalado e configurado
- ✅ Servidor PHP rodando na porta 8081
- ✅ ngrok expondo porta 8081 publicamente

---

## 🌐 Sua URL Pública do ngrok

```
https://dramaturgic-rushingly-raphael.ngrok-free.dev
```

---

## 📋 Passo a Passo para Configurar no Meta Dashboard

### 1. Gerar Verify Token

Execute este comando no terminal:

```bash
openssl rand -hex 32
```

**Copie o resultado** (será algo como: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6`)

---

### 2. Adicionar Verify Token ao .env

Edite o arquivo `.env` e adicione esta linha:

```bash
META_VERIFY_TOKEN=SEU_TOKEN_GERADO_AQUI
```

**Exemplo:**

```bash
META_VERIFY_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

---

### 3. Configurar no Meta for Developers

1. **Acesse seu app:**

   ```
   https://developers.facebook.com/apps/
   ```

2. **Navegue para:**

   - Selecione seu app
   - Vá para **Messenger** → **Settings**
   - Role até a seção **Webhooks**

3. **Clique em "Add Callback URL"**

4. **Preencha os campos:**

   **Callback URL:**

   ```
   https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
   ```

   **Verify Token:**

   ```
   [Cole o token que você gerou no passo 1]
   ```

5. **Clique em "Verify and Save"**

   O Meta vai fazer uma requisição GET para verificar seu webhook.

   ✅ Se tudo estiver correto, você verá uma mensagem de sucesso!

---

### 4. Subscrever aos Eventos do Webhook

Após adicionar o callback URL com sucesso:

#### Para Facebook Messenger:

1. Na seção **Webhooks**, encontre sua **Facebook Page**
2. Clique em **"Add Subscriptions"**
3. Selecione os eventos:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
   - ✅ `messaging_optins`
4. Clique em **"Save"**

#### Para Instagram:

1. Vá para **Instagram** → **Settings** no dashboard
2. Na seção **Webhooks**, clique em **"Add Subscriptions"**
3. Selecione os eventos:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
4. Clique em **"Save"**

---

## 🧪 Testar o Webhook

### Teste 1: Verificação Manual (GET)

Execute este comando no terminal:

```bash
curl -X GET "https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta?hub.mode=subscribe&hub.verify_token=SEU_VERIFY_TOKEN&hub.challenge=TESTE123"
```

**Resposta esperada:** `TESTE123`

---

### Teste 2: Ver Requisições em Tempo Real

Acesse a interface web do ngrok:

```
http://127.0.0.1:4040
```

Aqui você pode ver todas as requisições HTTP que chegam ao seu webhook em tempo real! 🔍

---

### Teste 3: Enviar Mensagem de Teste

1. **Via Facebook Messenger:**

   - Acesse sua Facebook Page
   - Envie uma mensagem para a Page via Messenger

2. **Via Instagram:**

   - Acesse sua conta Instagram Professional
   - Envie uma mensagem via Direct Message

3. **Verificar logs:**
   ```bash
   tail -f storage/logs/whatsapp-adapter.log | grep meta
   ```

---

## 📊 Monitoramento

### Ver logs do servidor PHP:

```bash
tail -f storage/logs/whatsapp-adapter.log
```

### Ver requisições do ngrok:

```
http://127.0.0.1:4040
```

### Verificar se o webhook está respondendo:

```bash
curl https://dramaturgic-rushingly-raphael.ngrok-free.dev/health
```

---

## ⚠️ Notas Importantes

### 1. URL do ngrok muda

Quando você reiniciar o ngrok, a URL vai mudar. Você precisará:

- Atualizar a Callback URL no Meta Dashboard
- Ou usar um plano pago do ngrok para ter URL fixa

### 2. Manter ngrok rodando

O ngrok precisa estar rodando para o webhook funcionar. Se você fechar o terminal, o túnel fecha.

### 3. Servidor PHP precisa estar rodando

Certifique-se de que o servidor PHP na porta 8081 está sempre rodando:

```bash
php -S localhost:8081 -t .
```

---

## 🔧 Comandos Úteis

### Ver processos rodando:

```bash
ps aux | grep php
ps aux | grep ngrok
```

### Parar ngrok:

```bash
pkill -f ngrok
```

### Reiniciar ngrok:

```bash
ngrok http 8081
```

### Gerar novo verify token:

```bash
openssl rand -hex 32
```

---

## 📝 Checklist de Configuração

- [ ] ngrok rodando e URL copiada
- [ ] Verify token gerado
- [ ] Verify token adicionado ao `.env`
- [ ] Callback URL configurada no Meta Dashboard
- [ ] Webhook verificado com sucesso
- [ ] Eventos subscritos (Messenger)
- [ ] Eventos subscritos (Instagram)
- [ ] Teste de mensagem realizado
- [ ] Logs verificados

---

## 🆘 Troubleshooting

### Erro: "Webhook verification failed"

- Verifique se o `META_VERIFY_TOKEN` no `.env` está correto
- Verifique se o servidor PHP está rodando
- Verifique os logs: `tail -f storage/logs/whatsapp-adapter.log`

### Erro: "Connection refused"

- Verifique se o servidor PHP está rodando na porta 8081
- Verifique se o ngrok está apontando para a porta correta

### Webhook não recebe mensagens

- Verifique se subscreveu aos eventos corretos
- Verifique se o ngrok está rodando
- Acesse http://127.0.0.1:4040 para ver as requisições

---

**Criado em**: Janeiro 2025  
**URL ngrok**: https://dramaturgic-rushingly-raphael.ngrok-free.dev  
**Porta local**: 8081
