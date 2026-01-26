# 🎯 COMO SUBSCREVER AOS EVENTOS (PASSO A PASSO)

## ⚠️ PROBLEMA ATUAL

Enviaste uma mensagem mas **nada chegou ao webhook**!

**Causa**: Os eventos provavelmente não estão subscritos no Meta Dashboard.

---

## 🔧 SOLUÇÃO (5 MINUTOS)

### 1️⃣ Abre o Meta Dashboard

```
https://developers.facebook.com/apps/650370691458548
```

---

### 2️⃣ Vai para Webhooks

```
Menu lateral → Messenger → Settings → Webhooks
```

---

### 3️⃣ Encontra a tua Page

Procura por: **"CoreMedia Portugal"**

Deves ver algo assim:

```
CoreMedia Portugal
  Callback URL: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
  Verify Token: ••••••••
```

---

### 4️⃣ Clica em "Edit Subscriptions"

Vai aparecer um modal com checkboxes.

---

### 5️⃣ Seleciona os Eventos

Marca estes checkboxes:

- ☑️ **messages** ← IMPORTANTE!
- ☑️ **messaging_postbacks**
- ☑️ **message_deliveries**
- ☑️ **message_reads**
- ☑️ **messaging_optins**

---

### 6️⃣ CLICA EM "CONFIRM" OU "SUBSCRIBE"

⚠️ **ESTE É O PASSO MAIS IMPORTANTE!**

Procura um botão azul no canto inferior direito do modal:

- "Confirm"
- "Subscribe"
- "Save"

**CLICA NELE!**

Se não clicares, os eventos **NÃO são guardados**!

---

### 7️⃣ Verifica se Funcionou

Depois de clicar em "Confirm", deves ver:

```
CoreMedia Portugal
  Callback URL: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
  Verify Token: ••••••••
  Subscribed Fields: messages, messaging_postbacks, message_deliveries, ...
  ✅ Status: Active
```

**Se vires "Subscribed Fields"**: ✅ Está configurado!

**Se NÃO vires "Subscribed Fields"**: ❌ Repete os passos acima!

---

## 🧪 TESTE AGORA

1. **Envia uma mensagem** para a Page "CoreMedia Portugal" via Messenger
2. **Aguarda 10 segundos**
3. **Abre o ngrok**: http://127.0.0.1:4040
4. **Deves ver uma requisição POST** aparecer!

---

## 📊 COMO SABER SE FUNCIONOU

### No ngrok (http://127.0.0.1:4040):

**ANTES** (não funciona):

```
GET /webhooks/meta?hub.mode=subscribe... ← Só verificação
```

**DEPOIS** (funciona):

```
GET /webhooks/meta?hub.mode=subscribe... ← Verificação
POST /webhooks/meta ← MENSAGEM! ✅
```

---

## ❓ AINDA NÃO FUNCIONA?

Se depois de subscrever aos eventos ainda não funcionar, verifica:

### A) App Mode

```
Settings → Basic → App Mode
```

- Se estiver **"Development"**: Só recebe mensagens de admins/testers
- Se estiver **"Live"**: Recebe mensagens de qualquer pessoa

**Solução**: Adiciona a tua conta como Tester:

```
Roles → Test Users → Add Test Users
```

---

### B) Page Conectada

```
Messenger → Settings → Access Tokens
```

Verifica se "CoreMedia Portugal" está na lista.

Se não estiver:

1. Clica em "Add or Remove Pages"
2. Seleciona "CoreMedia Portugal"
3. Concede as permissões
4. Clica em "Done"

---

## 💡 RESUMO

1. ✅ Subscrever aos eventos (messages, messaging_postbacks, etc.)
2. ✅ **CLICAR EM "CONFIRM"** ← IMPORTANTE!
3. ✅ Verificar "Subscribed Fields" aparece
4. ✅ Enviar mensagem de teste
5. ✅ Ver requisição POST no ngrok

---

## 🚀 AÇÃO IMEDIATA

**Vai agora ao Meta Dashboard e subscreve aos eventos!**

Não te esqueças de clicar em "Confirm"! 🔵

---

**Última atualização**: 20 Janeiro 2026, 13:00 GMT
