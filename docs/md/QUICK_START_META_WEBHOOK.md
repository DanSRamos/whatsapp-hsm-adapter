# ⚡ QUICK START: Configurar Webhook Meta

## 🚀 CONFIGURAÇÃO EM 5 MINUTOS

### 📋 O QUE VOCÊ PRECISA

```
✅ Callback URL: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
✅ Verify Token: d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5
```

---

## 🎯 PASSO A PASSO RÁPIDO

### 1. Acesse o Meta Dashboard

🔗 https://developers.facebook.com/apps/650370691458548

### 2. Configure Messenger

**Messenger** → **Settings** → **Webhooks** → **Add Callback URL**

Copie e cole:

```
Callback URL: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
Verify Token: d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5
```

Clique em **"Verify and Save"** ✅

### 3. Subscreva aos Eventos

Encontre sua Page → **Add Subscriptions** → Selecione:

- ✅ messages
- ✅ messaging_postbacks
- ✅ message_deliveries
- ✅ message_reads
- ✅ messaging_optins

Clique em **"Save"** ✅

### 4. Teste!

Envie uma mensagem para sua Page via Messenger e veja os logs:

```bash
tail -f storage/logs/whatsapp-adapter.log | grep meta
```

---

## 🔍 VERIFICAR SE ESTÁ FUNCIONANDO

### Opção 1: Logs

```bash
tail -f storage/logs/whatsapp-adapter.log | grep meta
```

### Opção 2: ngrok Inspector

Abra no navegador: http://127.0.0.1:4040

### Opção 3: Teste Manual

```bash
curl "http://localhost:8081/webhooks/meta?hub.mode=subscribe&hub.verify_token=d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5&hub.challenge=TEST"
# Deve retornar: TEST
```

---

## 📊 DIAGRAMA VISUAL

```
┌─────────────────────────────────────────┐
│         Meta Platform                    │
│   (Messenger + Instagram)                │
└─────────────────────────────────────────┘
              │
              │ Webhook Events
              ▼
┌─────────────────────────────────────────┐
│            ngrok                         │
│  dramaturgic-rushingly-raphael...       │
└─────────────────────────────────────────┘
              │
              │ HTTP
              ▼
┌─────────────────────────────────────────┐
│      PHP Server (localhost:8081)        │
│      /webhooks/meta endpoint            │
└─────────────────────────────────────────┘
              │
              │ Process & Store
              ▼
┌─────────────────────────────────────────┐
│         MySQL Database                   │
│      (whatsapp_adapter)                  │
└─────────────────────────────────────────┘
```

---

## ⚠️ IMPORTANTE

### ngrok URL é Temporária

Se você reiniciar o ngrok, a URL vai mudar e você precisa atualizar no Meta Dashboard!

### Verificar Serviços Rodando

```bash
ps aux | grep -E "(php|ngrok|mysql)" | grep -v grep
```

Deve mostrar:

- ✅ php -S localhost:8081
- ✅ ngrok http 8081
- ✅ mysqld

---

## 🆘 PROBLEMAS?

### Webhook não verifica

1. Verifique se o ngrok está rodando
2. Verifique se o PHP server está rodando
3. Teste localmente (comando acima)

### Não recebe mensagens

1. Verifique se subscreveu aos eventos
2. Envie uma mensagem de teste
3. Veja os logs e o ngrok inspector

### Mais ajuda

📄 Veja `WEBHOOK_CONFIGURATION_CHECKLIST.md` para checklist completo  
📄 Veja `META_DASHBOARD_SETUP_STEPS.md` para instruções detalhadas  
📄 Veja `docs/TROUBLESHOOTING.md` para solução de problemas

---

## 📞 INFORMAÇÕES ÚTEIS

| Item        | Valor              |
| ----------- | ------------------ |
| App ID      | 650370691458548    |
| Page ID     | 118491818174527    |
| Page Name   | CoreMedia Portugal |
| API Version | v21.0              |

---

**Status**: 🟢 PRONTO  
**Data**: 20 Janeiro 2025  
**Tempo estimado**: 5 minutos
