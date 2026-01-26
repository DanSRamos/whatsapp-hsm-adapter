# 🎯 COMO ADICIONAR TESTER (SOLUÇÃO RÁPIDA)

## ⚠️ PROBLEMA

O teu app está em **modo Development** e a conta que está a enviar mensagens **não tem privilégios** para interagir com ele.

**Resultado**: Mensagens enviadas mas webhook não recebe nada!

---

## ✅ SOLUÇÃO (5 MINUTOS)

### 1️⃣ Abre o Meta Dashboard

```
https://developers.facebook.com/apps/650370691458548
```

---

### 2️⃣ Vai para Roles

```
Menu lateral → Roles → Test Users
```

---

### 3️⃣ Clica em "Add Test Users"

Vai aparecer um formulário.

---

### 4️⃣ Adiciona a Conta

**Opção A: Se for uma conta Facebook**

- Escreve o **email** ou **nome** da conta
- Exemplo: `daniel@byside.pt` ou `BySide Development`

**Opção B: Se for uma conta Instagram**

- Escreve o **username** da conta
- Exemplo: `@byside_dev`

---

### 5️⃣ Seleciona as Permissões

Marca estas permissões:

- ☑️ **pages_messaging** ← IMPORTANTE!
- ☑️ **pages_manage_metadata**
- ☑️ **pages_read_engagement**

---

### 6️⃣ Clica em "Add" ou "Invite"

A conta vai receber um convite (se for uma conta real).

Se for uma Test User criada pelo Meta, já está pronta!

---

### 7️⃣ Aceita o Convite (se necessário)

Se adicionaste uma conta real:

1. A pessoa vai receber uma notificação no Facebook
2. Precisa aceitar o convite
3. Depois de aceitar, pode testar o app

---

## 🧪 TESTE AGORA

### Passo 1: Verifica se a Conta Aparece

```
Roles → Test Users
```

Deves ver a conta na lista:

```
✅ BySide Development
   Status: Active
   Permissions: pages_messaging, pages_manage_metadata, ...
```

---

### Passo 2: Envia Mensagem de Teste

1. **Envia uma mensagem** para a Page "CoreMedia Portugal" via Messenger
2. **Aguarda 10 segundos**
3. **Abre o ngrok**: http://127.0.0.1:4040
4. **Deves ver uma requisição POST** aparecer!

---

### Passo 3: Verifica o Payload

No ngrok, clica na requisição POST e vê o body:

```json
{
  "object": "page",
  "entry": [
    {
      "id": "118491818174527",
      "messaging": [
        {
          "sender": {
            "id": "..."
          },
          "message": {
            "text": "Tua mensagem aqui"
          }
        }
      ]
    }
  ]
}
```

**Se vires isto**: ✅ Funcionou!

---

## ❓ ALTERNATIVA: Usar a Tua Própria Conta

Se és **Administrator** do app, podes usar a tua própria conta sem adicionar como Tester!

### Como verificar:

```
Roles → Roles
```

Procura pela tua conta:

- ✅ Se aparecer como "Administrator" ou "Developer" → Podes testar!
- ❌ Se não aparecer → Adiciona como Tester (passos acima)

---

## 🔍 TROUBLESHOOTING

### Problema 1: "Não consigo adicionar a conta"

**Solução**: Cria um Test User do Meta:

1. **Roles → Test Users**
2. **Clica em "Create Test User"**
3. **Preenche os dados**:
   - Name: `Test User 1`
   - Password: (escolhe uma password)
4. **Clica em "Create"**
5. **Usa esta conta para enviar mensagens**

---

### Problema 2: "A conta não recebe o convite"

**Solução**: Verifica o email/notificações:

1. Verifica o email da conta
2. Verifica as notificações no Facebook
3. Procura por "App Invitation" ou "Convite de App"

---

### Problema 3: "Ainda não funciona depois de adicionar"

**Verifica**:

1. ✅ A conta aceitou o convite?
2. ✅ As permissões estão corretas? (pages_messaging)
3. ✅ Os eventos estão subscritos? (messages, messaging_postbacks)
4. ✅ A Page está conectada ao app?

---

## 📊 ANTES vs DEPOIS

### ANTES (não funciona):

```
Conta: BySide Development
Role: Nenhum
Status: ❌ Não autorizada

Resultado:
- Mensagem enviada no Messenger ✅
- Webhook recebe POST ❌
```

### DEPOIS (funciona):

```
Conta: BySide Development
Role: Tester
Status: ✅ Autorizada

Resultado:
- Mensagem enviada no Messenger ✅
- Webhook recebe POST ✅
```

---

## 💡 RESUMO

1. ✅ **Vai para Roles → Test Users**
2. ✅ **Clica em "Add Test Users"**
3. ✅ **Adiciona a conta** (email ou username)
4. ✅ **Seleciona permissões** (pages_messaging)
5. ✅ **Clica em "Add"**
6. ✅ **Aceita o convite** (se necessário)
7. ✅ **Testa novamente** (envia mensagem)
8. ✅ **Verifica ngrok** (deve aparecer POST)

---

## 🚀 AÇÃO IMEDIATA

**Vai agora ao Meta Dashboard e adiciona a tua conta como Tester!**

Link direto: https://developers.facebook.com/apps/650370691458548/roles/test-users/

---

**Última atualização**: 20 Janeiro 2026, 13:10 GMT
