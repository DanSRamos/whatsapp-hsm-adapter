# 🔴 PROBLEMA: Meta não envia mensagens para o webhook

## 📊 ANÁLISE DA SCREENSHOT

Vi a screenshot que enviaste do Meta Dashboard mostrando o modal "messages field sample".

### O que isto significa:

Este modal mostra um **exemplo de payload** que o Meta **DEVERIA** enviar para o teu webhook quando recebes uma mensagem. Mas o Meta **NÃO está a enviar** este payload!

---

## ⚠️ CONFIRMAÇÃO DO PROBLEMA

Verifiquei o ngrok e confirmo:

- ✅ 1 requisição GET do Meta (verificação) - 11:38:16
- ❌ **0 requisições POST** (mensagens)
- ❌ **Nenhum payload como o da screenshot foi recebido**

---

## 🔍 CAUSAS POSSÍVEIS

### 1️⃣ Eventos não subscritos (MAIS PROVÁVEL)

**Sintoma**: Vês o modal com o exemplo, mas não clicaste em "Subscribe" ou "Confirm"

**Como verificar**:

1. Fecha o modal
2. Vê se aparece "Subscribed Fields: messages, ..." na lista de webhooks
3. Se não aparecer, os eventos NÃO estão subscritos!

**Solução**:

1. Clica em "Edit Subscriptions"
2. Seleciona os eventos (messages, messaging_postbacks, etc.)
3. **CLICA EM "CONFIRM" ou "SUBSCRIBE"**
4. Aguarda a confirmação

---

### 2️⃣ App em modo Development

**Sintoma**: O app só aceita mensagens de admins/testers

**Como verificar**:

1. Settings → Basic
2. Vê o "App Mode" no topo
3. Se estiver "Development", só recebe mensagens de contas específicas

**Solução**:

- **Opção A**: Adiciona a tua conta como Tester
  1. Roles → Test Users
  2. Adiciona a conta que está a enviar mensagens
- **Opção B**: Muda para Live mode (requer App Review)
  1. Settings → Basic
  2. "Switch to Live Mode"
  3. Completa o App Review do Meta

---

### 3️⃣ Page não conectada ao App

**Sintoma**: A Page não está ligada ao App para receber webhooks

**Como verificar**:

1. Messenger → Settings
2. Procura "Access Tokens"
3. Vê se "CoreMedia Portugal" está na lista

**Solução**:

1. Clica em "Add or Remove Pages"
2. Seleciona "CoreMedia Portugal"
3. Concede as permissões
4. Clica em "Done"

---

### 4️⃣ Mensagens enviadas da conta errada

**Sintoma**: Estás a enviar mensagens de uma conta que não é admin/tester

**Como verificar**:

1. Roles → Roles
2. Vê se a conta "BySide Development" é Admin ou Developer
3. OU Roles → Test Users para ver se está como Tester

**Solução**:

- Envia mensagens de uma conta que seja Admin/Developer/Tester do app

---

## 🎯 DIAGNÓSTICO PASSO A PASSO

### Passo 1: Verificar Subscriptions

```
Meta Dashboard → Messenger → Settings → Webhooks
```

**O que deves ver**:

```
CoreMedia Portugal
  Callback URL: https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
  Verify Token: ••••••••
  Subscribed Fields: messages, messaging_postbacks, message_deliveries, ...
  ✅ Status: Active
```

**Se não vires "Subscribed Fields"**: Os eventos NÃO estão subscritos!

---

### Passo 2: Verificar App Mode

```
Meta Dashboard → Settings → Basic
```

**O que deves ver no topo**:

- "App Mode: Development" ← Só recebe de admins/testers
- "App Mode: Live" ← Recebe de qualquer pessoa

---

### Passo 3: Verificar Page Connection

```
Meta Dashboard → Messenger → Settings → Access Tokens
```

**O que deves ver**:

```
CoreMedia Portugal
  Page ID: 118491818174527
  Page Access Token: EAAJPgjoJZAfQ...
  Status: ✅ Connected
```

---

### Passo 4: Verificar Roles

```
Meta Dashboard → Roles → Roles
```

**Verifica se a conta que está a enviar mensagens é**:

- ✅ Administrator
- ✅ Developer
- ✅ Tester (em Roles → Test Users)

---

## 🧪 TESTE FINAL

Depois de verificar tudo acima:

1. **Envia uma mensagem** para a Page "CoreMedia Portugal" via Messenger
2. **Aguarda 10-15 segundos**
3. **Verifica o ngrok**: http://127.0.0.1:4040
4. **Deves ver uma requisição POST** com o payload similar ao da screenshot!

---

## 📸 PAYLOAD ESPERADO

Quando tudo estiver configurado corretamente, deves receber um payload assim:

```json
{
  "object": "page",
  "entry": [
    {
      "id": "118491818174527",
      "time": 1527459824,
      "messaging": [
        {
          "sender": {
            "id": "12334"
          },
          "recipient": {
            "id": "23245"
          },
          "timestamp": 1527459824,
          "message": {
            "mid": "test_message_id",
            "text": "test_message"
          }
        }
      ]
    }
  ]
}
```

---

## 💡 DICA IMPORTANTE

A screenshot que enviaste mostra o **exemplo de payload** que o Meta **deveria** enviar.

Mas o Meta **só envia** este payload se:

1. ✅ Os eventos estiverem subscritos
2. ✅ A Page estiver conectada ao App
3. ✅ O App estiver configurado corretamente
4. ✅ A mensagem for enviada de uma conta autorizada

**Verifica cada um destes pontos!**

---

## 🔍 COMANDOS PARA DEBUG

### Ver se chegou alguma requisição POST:

```bash
curl -s http://127.0.0.1:4040/api/requests/http | jq '.requests[] | select(.request.method == "POST")'
```

**Resultado atual**: Vazio (nenhuma requisição POST)

### Ver todas as requisições:

```bash
curl -s http://127.0.0.1:4040/api/requests/http | jq '.requests[0:5] | .[] | {method: .request.method, uri: .request.uri, status: .response.status_code}'
```

---

## ✅ CHECKLIST DE VERIFICAÇÃO

- [ ] Eventos subscritos no Meta Dashboard
- [ ] Botão "Confirm" ou "Subscribe" clicado
- [ ] "Subscribed Fields" visível na lista de webhooks
- [ ] Page "CoreMedia Portugal" conectada ao App
- [ ] App Mode verificado (Development ou Live)
- [ ] Conta que envia mensagens é Admin/Developer/Tester
- [ ] Mensagem enviada para a Page via Messenger
- [ ] Aguardado 10-15 segundos
- [ ] Verificado ngrok (http://127.0.0.1:4040)

---

## 🚨 PRÓXIMA AÇÃO

**Vai ao Meta Dashboard e verifica cada ponto do checklist acima!**

O problema está na configuração do Meta, não no teu código. O webhook está a funcionar perfeitamente!

---

**Última atualização**: 20 Janeiro 2026, 13:00 GMT
