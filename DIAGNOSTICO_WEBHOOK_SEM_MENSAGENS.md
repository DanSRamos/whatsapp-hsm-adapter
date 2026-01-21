# 🔍 DIAGNÓSTICO: WEBHOOK NÃO RECEBE MENSAGENS

## 📊 ANÁLISE DAS SCREENSHOTS

### ✅ O que está funcionando:

- Webhook verificado com sucesso (200 OK às 11:38:16)
- ngrok rodando e acessível
- Eventos subscritos corretamente

### ⚠️ O problema:

- **Nenhuma requisição POST recebida**
- Apenas requisições GET (verificação)

---

## 🔍 POSSÍVEIS CAUSAS

### 1️⃣ Você clicou em "Confirm" depois de subscrever?

Na screenshot que você enviou, vejo o modal "Edit Page Subscriptions" aberto.

**IMPORTANTE**: Você precisa clicar no botão azul **"Confirm"** no canto inferior direito!

Se você não clicou em "Confirm", os eventos não foram salvos!

### 2️⃣ A Page está conectada ao App?

O Meta só envia webhooks se a Page estiver conectada ao App.

**Como verificar**:

1. No Meta Dashboard, vá para **Messenger** → **Settings**
2. Procure por **"Access Tokens"**
3. Verifique se "CoreMedia Portugal" está na lista
4. Se não estiver, clique em "Add or Remove Pages"

### 3️⃣ O App está em modo Development?

Apps em modo Development só recebem mensagens de:

- Administradores do app
- Testadores do app
- Desenvolvedores do app

**Como verificar**:

1. No Meta Dashboard, vá para **Settings** → **Basic**
2. Veja o "App Mode" no topo
3. Se estiver "Development", você precisa ser admin/tester

### 4️⃣ Você está enviando mensagem da conta certa?

Você precisa enviar a mensagem:

- **PARA** a Page "CoreMedia Portugal"
- **DE** uma conta que seja admin/tester do app (se em Development mode)

---

## 🎯 SOLUÇÃO PASSO A PASSO

### Passo 1: Confirmar as Subscriptions

1. Volte para o Meta Dashboard
2. Vá para **Messenger** → **Settings** → **Webhooks**
3. Encontre "CoreMedia Portugal"
4. Clique em **"Edit Subscriptions"**
5. Verifique se os eventos estão marcados:
   - ✅ messages
   - ✅ messaging_postbacks
   - ✅ message_deliveries
   - ✅ message_reads
   - ✅ messaging_optins
6. **CLIQUE EM "CONFIRM"** (botão azul)
7. Aguarde a mensagem de confirmação

### Passo 2: Verificar se a Page está conectada

1. No Meta Dashboard, vá para **Messenger** → **Settings**
2. Procure por **"Access Tokens"**
3. Verifique se "CoreMedia Portugal" está listada
4. Se não estiver:
   - Clique em "Add or Remove Pages"
   - Selecione "CoreMedia Portugal"
   - Conceda as permissões
   - Clique em "Next" e "Done"

### Passo 3: Verificar o App Mode

1. Vá para **Settings** → **Basic**
2. Veja o "App Mode" no topo da página
3. Se estiver "Development":
   - Você precisa enviar mensagem de uma conta admin/tester
   - OU mudar para "Live" mode (requer App Review)

### Passo 4: Testar novamente

1. Envie uma mensagem para a Page via Messenger
2. Aguarde 5-10 segundos
3. Verifique o ngrok: http://127.0.0.1:4040
4. Deve aparecer uma requisição POST

---

## 🧪 TESTE DE DIAGNÓSTICO

Execute este comando para simular uma mensagem do Meta:

```bash
curl -X POST http://localhost:8081/webhooks/meta \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=test" \
  -d '{
    "object": "page",
    "entry": [{
      "id": "118493918174527",
      "time": 1234567890,
      "messaging": [{
        "sender": {"id": "123456"},
        "recipient": {"id": "118493918174527"},
        "timestamp": 1234567890,
        "message": {
          "mid": "test123",
          "text": "Teste de mensagem"
        }
      }]
    }]
  }'
```

**Resultado esperado**:

- Se retornar 200 OK: O webhook está funcionando
- Se retornar erro: Há um problema no código

---

## 📋 CHECKLIST DE VERIFICAÇÃO

Execute cada item e marque:

- [ ] Cliquei em "Confirm" depois de subscrever aos eventos
- [ ] A Page "CoreMedia Portugal" está na lista de Access Tokens
- [ ] Verifiquei o App Mode (Development ou Live)
- [ ] Estou enviando mensagem da conta correta (admin/tester)
- [ ] Aguardei 5-10 segundos depois de enviar a mensagem
- [ ] Verifiquei o ngrok (http://127.0.0.1:4040)
- [ ] Executei o teste de diagnóstico acima

---

## 🔍 COMANDOS PARA DEBUG

### Ver todas as requisições recentes:

```bash
curl -s http://127.0.0.1:4040/api/requests/http | jq '.requests[0:5] | .[] | {method: .request.method, uri: .request.uri, status: .response.status}'
```

### Ver logs do PHP:

```bash
tail -f storage/logs/whatsapp-adapter.log
```

### Testar o endpoint localmente:

```bash
curl -X POST http://localhost:8081/webhooks/meta \
  -H "Content-Type: application/json" \
  -d '{"object":"page","entry":[]}'
```

---

## 💡 DICA IMPORTANTE

O problema mais comum é **não clicar em "Confirm"** depois de selecionar os eventos!

Volte para o Meta Dashboard e confirme que você clicou no botão azul "Confirm" no modal de subscriptions.

---

**Próximo passo**: Verificar cada item do checklist acima e testar novamente!
