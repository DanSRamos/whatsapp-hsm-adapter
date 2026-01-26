# 🔍 ANÁLISE: App em Modo Development - Falta de Privilégios

## 📊 O PROBLEMA

Quando um app Meta está em **modo Development**, ele tem **restrições de segurança** que impedem que pessoas normais interajam com ele.

### Como funciona:

```
App Mode: Development
├─ ✅ Administradores do app → Podem enviar/receber mensagens
├─ ✅ Desenvolvedores do app → Podem enviar/receber mensagens
├─ ✅ Testers do app → Podem enviar/receber mensagens
└─ ❌ Utilizadores normais → NÃO podem interagir com o app
```

---

## 🎯 IDENTIFICAR O PROBLEMA

### Sintomas:

1. ✅ Webhook verificado com sucesso
2. ✅ Eventos subscritos corretamente
3. ✅ Page conectada ao app
4. ❌ **Mensagens enviadas mas webhook não recebe nada**

### Causa:

A conta que está a enviar mensagens **não tem privilégios** para interagir com o app em Development mode.

---

## 🔍 VERIFICAR SE ESTE É O TEU PROBLEMA

### Passo 1: Verificar App Mode

1. Vai ao Meta Dashboard: https://developers.facebook.com/apps/650370691458548
2. Clica em **Settings** → **Basic**
3. Vê o **"App Mode"** no topo da página

**O que vês?**

- 🟢 **"Live"** → App está público, qualquer pessoa pode usar
- 🔴 **"Development"** → App está em testes, só pessoas autorizadas podem usar

---

### Passo 2: Verificar Roles da Conta

Se o app está em **Development**, verifica se a conta que está a enviar mensagens tem privilégios:

#### A) Verificar Administradores

```
Meta Dashboard → Roles → Roles
```

Procura pela conta **"BySide Development"** (ou a conta que estás a usar).

**Roles possíveis**:

- ✅ **Administrator** → Acesso total
- ✅ **Developer** → Pode testar o app
- ✅ **Tester** → Pode testar funcionalidades
- ❌ **Nenhum** → Não pode usar o app em Development

#### B) Verificar Test Users

```
Meta Dashboard → Roles → Test Users
```

Vê se a conta que está a enviar mensagens está na lista de Test Users.

---

## 🎯 SOLUÇÕES

### Solução 1: Adicionar a Conta como Tester (RECOMENDADO)

Esta é a solução mais rápida para testar o webhook.

#### Passo a Passo:

1. **Vai ao Meta Dashboard**

   ```
   https://developers.facebook.com/apps/650370691458548
   ```

2. **Roles → Test Users**

3. **Clica em "Add Test Users"**

4. **Adiciona a conta que está a enviar mensagens**

   - Se for uma conta Facebook normal, adiciona o email ou ID
   - Se for uma conta Instagram, adiciona o username

5. **Concede as permissões necessárias**:

   - ☑️ pages_messaging
   - ☑️ pages_manage_metadata
   - ☑️ pages_read_engagement

6. **Clica em "Add"**

7. **Testa novamente**:
   - Envia uma mensagem para a Page
   - Verifica o ngrok: http://127.0.0.1:4040
   - Deves ver a requisição POST aparecer!

---

### Solução 2: Adicionar como Developer

Se a conta já tem acesso ao Meta Dashboard:

1. **Roles → Roles**

2. **Clica em "Add People"**

3. **Adiciona a conta e seleciona "Developer"**

4. **A pessoa precisa aceitar o convite**

5. **Depois de aceitar, pode enviar mensagens para testar**

---

### Solução 3: Mudar para Live Mode (PRODUÇÃO)

⚠️ **ATENÇÃO**: Esta solução requer App Review do Meta e pode demorar dias/semanas!

#### Quando usar:

- Quando queres que qualquer pessoa possa usar o app
- Quando estás pronto para produção
- Quando completaste todos os testes

#### Como fazer:

1. **Settings → Basic**

2. **Clica em "Switch to Live Mode"**

3. **O Meta vai pedir**:

   - Privacy Policy URL
   - Terms of Service URL
   - App Icon
   - Descrição do app
   - Permissões que o app precisa

4. **Submete para App Review**

5. **Aguarda aprovação** (pode demorar 1-7 dias)

6. **Depois de aprovado, o app fica público**

---

## 🧪 TESTAR SE A SOLUÇÃO FUNCIONOU

### Teste 1: Verificar Privilégios

```
Meta Dashboard → Roles → Roles
```

Confirma que a conta aparece como:

- ✅ Administrator, ou
- ✅ Developer, ou
- ✅ Tester (em Test Users)

---

### Teste 2: Enviar Mensagem

1. **Envia uma mensagem** para a Page "CoreMedia Portugal" via Messenger

2. **Aguarda 10 segundos**

3. **Verifica o ngrok**: http://127.0.0.1:4040

4. **Deves ver**:
   ```
   POST /webhooks/meta
   Status: 200 OK
   Body: {"object":"page","entry":[...]}
   ```

---

### Teste 3: Verificar Logs

```bash
curl -s http://127.0.0.1:4040/api/requests/http | jq '.requests[] | select(.request.method == "POST")'
```

**Resultado esperado**: Deve aparecer pelo menos 1 requisição POST!

---

## 📊 COMPARAÇÃO: Development vs Live

| Característica     | Development Mode           | Live Mode               |
| ------------------ | -------------------------- | ----------------------- |
| **Quem pode usar** | Só admins/devs/testers     | Qualquer pessoa         |
| **Webhooks**       | Só para contas autorizadas | Para todos              |
| **Aprovação Meta** | Não necessária             | Necessária (App Review) |
| **Tempo setup**    | Imediato                   | 1-7 dias                |
| **Ideal para**     | Testes e desenvolvimento   | Produção                |

---

## 🎯 RECOMENDAÇÃO

Para **testar o webhook agora**:

1. ✅ **Adiciona a tua conta como Tester** (Solução 1)

   - Rápido (5 minutos)
   - Não requer aprovação do Meta
   - Perfeito para testes

2. ❌ **NÃO mudes para Live Mode ainda**
   - Demora dias/semanas
   - Requer App Review
   - Só quando estiveres pronto para produção

---

## 🔍 COMO SABER SE ESTE É O TEU PROBLEMA

### Checklist:

- [ ] App está em modo "Development"
- [ ] Webhook verificado com sucesso
- [ ] Eventos subscritos corretamente
- [ ] Page conectada ao app
- [ ] Mensagens enviadas mas nada chega ao webhook
- [ ] A conta que envia mensagens NÃO é admin/dev/tester

**Se marcaste todos**: Este é o teu problema! Usa a Solução 1.

---

## 💡 DICA IMPORTANTE

O Meta **não dá erro** quando uma conta sem privilégios tenta enviar mensagens!

A mensagem é enviada normalmente no Messenger, mas o Meta **simplesmente não envia o webhook** porque a conta não tem permissão.

**Isto é confuso** porque parece que o webhook não está a funcionar, mas na verdade é uma restrição de segurança do app em Development mode.

---

## 🚀 PRÓXIMA AÇÃO

1. **Verifica o App Mode**: Settings → Basic → App Mode
2. **Se estiver "Development"**: Adiciona a tua conta como Tester
3. **Testa novamente**: Envia mensagem e verifica ngrok
4. **Deve funcionar!** ✅

---

## 📞 COMANDOS ÚTEIS

### Ver App Mode (via API):

```bash
curl -X GET "https://graph.facebook.com/v21.0/650370691458548?fields=development_mode&access_token=YOUR_TOKEN"
```

### Ver Roles (via Dashboard):

```
https://developers.facebook.com/apps/650370691458548/roles/roles/
```

### Ver Test Users (via Dashboard):

```
https://developers.facebook.com/apps/650370691458548/roles/test-users/
```

---

**Última atualização**: 20 Janeiro 2026, 13:10 GMT
