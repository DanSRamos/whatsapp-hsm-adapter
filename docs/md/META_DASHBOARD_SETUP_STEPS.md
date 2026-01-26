# 🚀 PASSOS PARA CONFIGURAR NO META DASHBOARD

## ⚡ INFORMAÇÕES RÁPIDAS

**Copie e cole estes valores no Meta Dashboard:**

```
Callback URL:
https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta

Verify Token:
d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5
```

---

## 📋 PASSO A PASSO

### 1️⃣ Acesse o Meta for Developers

🔗 https://developers.facebook.com/apps/

### 2️⃣ Selecione seu App

- **App ID**: 650370691458548
- **App Name**: (o nome do seu app)

### 3️⃣ Configure Messenger Webhook

1. No menu lateral, clique em **Messenger** → **Settings**
2. Role até a seção **Webhooks**
3. Clique em **"Add Callback URL"**

### 4️⃣ Preencha os Campos

**Callback URL:**

```
https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
```

**Verify Token:**

```
d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5
```

### 5️⃣ Clique em "Verify and Save"

✅ O Meta vai fazer uma requisição GET para verificar seu webhook.  
✅ Se tudo estiver correto, você verá uma mensagem de sucesso!

### 6️⃣ Subscrever aos Eventos (Messenger)

Após verificar o webhook:

1. Na seção **Webhooks**, encontre sua **Facebook Page**
2. Clique em **"Add Subscriptions"**
3. Selecione os eventos:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
   - ✅ `messaging_optins`
4. Clique em **"Save"**

### 7️⃣ Configure Instagram Webhook (Opcional)

Se você também quer receber mensagens do Instagram:

1. No menu lateral, clique em **Instagram** → **Settings**
2. Role até a seção **Webhooks**
3. Clique em **"Add Callback URL"** (se ainda não configurado)
4. Use os mesmos valores acima
5. Clique em **"Add Subscriptions"**
6. Selecione os eventos:
   - ✅ `messages`
   - ✅ `messaging_postbacks`
   - ✅ `message_deliveries`
   - ✅ `message_reads`
7. Clique em **"Save"**

---

## 🧪 TESTAR A INTEGRAÇÃO

### Opção 1: Enviar Mensagem via Messenger

1. Acesse sua **Facebook Page**: CoreMedia Portugal
2. Envie uma mensagem via Messenger
3. Verifique os logs:
   ```bash
   tail -f storage/logs/whatsapp-adapter.log | grep meta
   ```

### Opção 2: Enviar Mensagem via Instagram

1. Acesse sua conta Instagram Professional
2. Envie uma mensagem via Direct Message
3. Verifique os logs (mesmo comando acima)

### Opção 3: Ver Requisições no ngrok

Abra no navegador:

```
http://127.0.0.1:4040
```

Aqui você pode ver **TODAS** as requisições HTTP que chegam ao seu webhook em tempo real! 🔍

---

## ⚠️ IMPORTANTE: ngrok URL Temporária

A URL do ngrok é **temporária** e muda toda vez que você reinicia o ngrok:

```
https://dramaturgic-rushingly-raphael.ngrok-free.dev
```

**Se você reiniciar o ngrok:**

1. A URL vai mudar
2. Você precisa atualizar no Meta Dashboard
3. Repita os passos 3-5 acima com a nova URL

**Para produção**, você deve:

- Usar um domínio próprio com HTTPS
- Configurar um servidor permanente
- Não usar ngrok

---

## 📊 INFORMAÇÕES DA SUA CONFIGURAÇÃO

### App Meta

- **App ID**: 650370691458548
- **App Secret**: 8a7d5669cc9a004f5c3a9360d59d4fac

### Facebook Page

- **Page ID**: 118491818174527
- **Page Name**: CoreMedia Portugal

### Tokens

- **Page Access Token**: EAAJPgjoJZAfQBQpDf34Qv4jYsn8e0YSVin0GKj446Ym0IZCeE2DVW5SZArE2GWZAGlRfbTLFsAWCrZCbLaZBoIamKlnRcRs559GT9jso3ZAY7MYmYqst3jkIS5hXZCXM9uVAhrY3ljmwe6IMNL8gUajhJ7pG0UrS2MLy1YTA5Y30OYODMilkKhAJtddRVZBrZBypf4DZAf6vfT9o
- **Verify Token**: d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5

### API

- **API Version**: v21.0
- **Webhook URL**: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta

---

## 🔧 COMANDOS ÚTEIS

### Ver logs em tempo real

```bash
tail -f storage/logs/whatsapp-adapter.log | grep meta
```

### Ver todos os processos rodando

```bash
ps aux | grep -E "(php|ngrok|mysql)" | grep -v grep
```

### Testar webhook localmente

```bash
curl "http://localhost:8081/webhooks/meta?hub.mode=subscribe&hub.verify_token=d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5&hub.challenge=TEST123"
# Deve retornar: TEST123
```

### Reiniciar MySQL

```bash
brew services restart mysql
```

### Reiniciar PHP Server

```bash
# Parar: Ctrl+C no terminal onde está rodando
# Iniciar:
php -d opcache.enable=0 -S localhost:8081 -t public
```

### Reiniciar ngrok

```bash
# Parar: Ctrl+C no terminal onde está rodando
# Iniciar:
ngrok http 8081
# ⚠️ A URL vai mudar! Atualize no Meta Dashboard
```

---

## 📞 PRÓXIMOS PASSOS

1. ✅ Configure o webhook no Meta Dashboard (siga os passos acima)
2. ✅ Subscreva aos eventos do Messenger
3. ✅ (Opcional) Subscreva aos eventos do Instagram
4. ✅ Teste enviando uma mensagem
5. ✅ Verifique os logs
6. ✅ Verifique o ngrok inspector (http://127.0.0.1:4040)

---

## 🆘 PROBLEMAS COMUNS

### "Webhook verification failed"

**Causa**: Verify token incorreto ou URL inacessível.

**Solução**:

1. Verifique se copiou o verify token corretamente
2. Teste a URL localmente (comando acima)
3. Verifique se o ngrok está rodando
4. Verifique se o PHP server está rodando

### "URL not accessible"

**Causa**: ngrok não está rodando ou URL mudou.

**Solução**:

1. Verifique se o ngrok está rodando: `ps aux | grep ngrok`
2. Acesse http://127.0.0.1:4040 para ver a URL atual
3. Atualize a URL no Meta Dashboard

### Webhook não recebe mensagens

**Causa**: Eventos não subscritos ou Page não conectada.

**Solução**:

1. Verifique se subscreveu aos eventos (passo 6)
2. Verifique se a Page está conectada ao app
3. Envie uma mensagem de teste
4. Verifique os logs

---

**Data**: 20 Janeiro 2025  
**Status**: ✅ PRONTO PARA CONFIGURAR
