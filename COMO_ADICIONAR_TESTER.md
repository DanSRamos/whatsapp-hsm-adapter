# 🎯 Como Adicionar Tester no Meta Dashboard

## ⚡ Guia Rápido (2 minutos)

### 1️⃣ Abrir o Meta Dashboard

```
https://developers.facebook.com/apps/650370691458548/roles/
```

### 2️⃣ Clicar em "Testers"

- No menu lateral esquerdo, clicar em **"Roles"**
- Depois clicar no tab **"Testers"**

### 3️⃣ Adicionar Tester

- Clicar no botão **"Add Testers"**
- Escrever o nome ou email da conta: **BySide Development**
- Clicar em **"Submit"**

### 4️⃣ Aceitar Convite

- A conta BySide Development vai receber uma notificação
- Abrir a notificação e clicar em **"Accept"**

### 5️⃣ Testar!

- Enviar uma mensagem da conta BySide Development para CoreMedia Portugal
- Abrir ngrok: http://127.0.0.1:4040
- Ver o POST request aparecer! 🎉

---

## 📸 Passo a Passo Visual

### Passo 1: Ir para Roles

```
Meta Dashboard
├── [App ID: 650370691458548]
│   ├── Dashboard
│   ├── Messenger
│   ├── Settings
│   └── Roles ← CLICAR AQUI
│       ├── Administrators
│       ├── Developers
│       └── Testers ← DEPOIS CLICAR AQUI
```

### Passo 2: Adicionar Tester

```
┌─────────────────────────────────────────────┐
│  Testers                                    │
├─────────────────────────────────────────────┤
│                                             │
│  [+ Add Testers]  ← CLICAR AQUI            │
│                                             │
│  ┌───────────────────────────────────────┐ │
│  │ Search for people by name or email   │ │
│  │ BySide Development                    │ │ ← ESCREVER AQUI
│  └───────────────────────────────────────┘ │
│                                             │
│  [Submit]  ← DEPOIS CLICAR AQUI            │
│                                             │
└─────────────────────────────────────────────┘
```

### Passo 3: Aceitar Convite

```
Notificação no Facebook:
┌─────────────────────────────────────────────┐
│  🔔 You've been invited to test an app      │
│                                             │
│  App Name: [Teu App]                        │
│  App ID: 650370691458548                    │
│                                             │
│  [Accept] [Decline]  ← CLICAR EM ACCEPT    │
└─────────────────────────────────────────────┘
```

---

## ✅ Como Verificar se Funcionou

### Método 1: Ver na lista de Testers

1. Ir para Roles → Testers
2. Deves ver "BySide Development" na lista
3. Status deve ser "Active"

### Método 2: Enviar mensagem de teste

1. Abrir Messenger
2. Ir para a página CoreMedia Portugal
3. Enviar uma mensagem: "teste"
4. Abrir ngrok: http://127.0.0.1:4040
5. Deves ver um POST request aparecer!

### Método 3: Ver logs do PHP

```bash
tail -f storage/logs/app.log
```

Deves ver:

```
[2026-01-20 16:30:00] INFO: POST /webhooks/meta - Webhook event received
[2026-01-20 16:30:00] DEBUG: Meta webhook payload received
```

---

## 🚨 Troubleshooting

### Problema: Não encontro a opção "Add Testers"

**Solução**: Verifica se tens permissões de Administrator no app.

### Problema: Não recebo o convite

**Solução**:

1. Verifica as notificações do Facebook
2. Verifica o email associado à conta
3. Tenta adicionar pelo email em vez do nome

### Problema: Aceito o convite mas ainda não recebo webhooks

**Solução**:

1. Espera 1-2 minutos (pode demorar a propagar)
2. Faz logout e login no Facebook
3. Tenta enviar outra mensagem

### Problema: Ainda não funciona!

**Solução**: Verifica se:

- [ ] ngrok está a correr: `curl http://127.0.0.1:4040`
- [ ] PHP server está a correr: `curl http://localhost:8081`
- [ ] Webhook está verificado no Meta Dashboard
- [ ] Eventos estão subscritos (messages, messaging_postbacks, etc.)

---

## 🎯 Alternativa: Usar outra conta

Se não conseguires adicionar BySide Development, podes:

1. **Criar uma conta de teste do Facebook**

   - Ir para Roles → Test Users
   - Clicar em "Add Test Users"
   - Criar uma conta de teste
   - Usar essa conta para enviar mensagens

2. **Adicionar outra conta tua como Tester**
   - Usar uma conta pessoal do Facebook
   - Adicionar como Tester
   - Enviar mensagens dessa conta

---

## 📞 Links Úteis

- **Meta Dashboard**: https://developers.facebook.com/apps/650370691458548
- **Roles**: https://developers.facebook.com/apps/650370691458548/roles/
- **Webhooks**: https://developers.facebook.com/apps/650370691458548/webhooks/
- **ngrok Inspector**: http://127.0.0.1:4040
- **Documentação Meta**: https://developers.facebook.com/docs/messenger-platform/webhooks

---

## 🎉 Depois de Funcionar

Quando vires o primeiro POST request no ngrok, significa que está tudo a funcionar!

Podes então:

1. ✅ Continuar a desenvolver e testar
2. ✅ Adicionar mais Testers se necessário
3. ✅ Quando estiver pronto, fazer App Review para Production
4. ✅ Em Production, qualquer pessoa pode enviar mensagens

---

**Boa sorte! 🚀**
