# 🚀 Guia de Configuração do ngrok para Meta Webhook

## Passo 1: Criar Conta no ngrok (Gratuito)

1. **Acesse o site de cadastro:**

   ```
   https://dashboard.ngrok.com/signup
   ```

2. **Opções de cadastro:**

   - Login com Google (mais rápido)
   - Login com GitHub
   - Cadastro com email

3. **Complete o cadastro** (é gratuito e não requer cartão de crédito)

---

## Passo 2: Obter seu Authtoken

1. **Após fazer login, acesse:**

   ```
   https://dashboard.ngrok.com/get-started/your-authtoken
   ```

2. **Copie o authtoken** que aparece na página
   - Será algo como: `2abc123def456ghi789jkl012mno345_6pqr789stu012vwx345yz`

---

## Passo 3: Configurar o Authtoken no Terminal

**Cole este comando no terminal** (substitua `SEU_TOKEN_AQUI` pelo token que você copiou):

```bash
ngrok config add-authtoken SEU_TOKEN_AQUI
```

**Exemplo:**

```bash
ngrok config add-authtoken 2abc123def456ghi789jkl012mno345_6pqr789stu012vwx345yz
```

Você verá uma mensagem de confirmação:

```
Authtoken saved to configuration file: /Users/danielramos/.ngrok2/ngrok.yml
```

---

## Passo 4: Iniciar o Túnel ngrok

Depois de configurar o authtoken, execute:

```bash
ngrok http 8081
```

Você verá uma interface como esta:

```
ngrok

Session Status                online
Account                       Seu Nome (Plan: Free)
Version                       3.35.0
Region                        United States (us)
Latency                       45ms
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123def456.ngrok-free.app -> http://localhost:8081

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

---

## Passo 5: Copiar a URL Pública

Na linha **"Forwarding"**, você verá uma URL como:

```
https://abc123def456.ngrok-free.app
```

**Esta é sua URL pública!** 🎉

---

## Passo 6: Configurar no Meta Dashboard

1. **Acesse seu app no Meta for Developers:**

   ```
   https://developers.facebook.com/apps/
   ```

2. **Vá para Messenger → Settings → Webhooks**

3. **Clique em "Add Callback URL"**

4. **Preencha:**
   - **Callback URL**: `https://abc123def456.ngrok-free.app/webhooks/meta`
     (substitua pela sua URL do ngrok + `/webhooks/meta`)
   - **Verify Token**: Gere um token com o comando abaixo

---

## Passo 7: Gerar Verify Token

Execute este comando para gerar um token aleatório:

```bash
openssl rand -hex 32
```

Copie o resultado (será algo como: `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6`)

---

## Passo 8: Adicionar ao .env

Edite seu arquivo `.env` e adicione:

```bash
META_VERIFY_TOKEN=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
```

(use o token que você gerou no Passo 7)

---

## Passo 9: Testar o Webhook

1. **No Meta Dashboard**, após adicionar a Callback URL, clique em **"Verify and Save"**

2. **O Meta vai fazer uma requisição GET** para verificar seu webhook

3. **Se tudo estiver correto**, você verá uma mensagem de sucesso ✅

---

## 🎯 Comandos Rápidos de Referência

### Iniciar ngrok:

```bash
ngrok http 8081
```

### Ver interface web do ngrok:

```
http://127.0.0.1:4040
```

(mostra todas as requisições em tempo real)

### Parar ngrok:

Pressione `Ctrl+C` no terminal

### Gerar novo verify token:

```bash
openssl rand -hex 32
```

---

## ⚠️ Notas Importantes

1. **URL muda a cada reinício**: Quando você para e inicia o ngrok novamente, a URL muda. Você precisará atualizar no Meta Dashboard.

2. **Plano gratuito**: O plano gratuito do ngrok tem algumas limitações:

   - 1 túnel simultâneo
   - URL aleatória (muda a cada reinício)
   - 40 conexões por minuto

3. **Para URL fixa**: Considere o plano pago do ngrok ($8/mês) que oferece:

   - URL personalizada e fixa
   - Múltiplos túneis
   - Mais conexões

4. **Alternativa para produção**: Para produção, use um servidor com domínio próprio e HTTPS.

---

## 🐛 Troubleshooting

### Erro: "authentication failed"

- Você não configurou o authtoken
- Execute: `ngrok config add-authtoken SEU_TOKEN`

### Erro: "tunnel not found"

- Verifique se o servidor está rodando na porta 8081
- Execute: `php -S localhost:8081 -t public`

### Erro: "connection refused"

- Seu servidor PHP não está rodando
- Inicie o servidor primeiro

### Webhook não verifica

- Verifique se o `META_VERIFY_TOKEN` no `.env` está correto
- Verifique os logs: `tail -f storage/logs/whatsapp-adapter.log`

---

## 📞 Próximos Passos

Depois de configurar o webhook:

1. ✅ Subscrever aos eventos (messages, messaging_postbacks, etc.)
2. ✅ Testar enviando uma mensagem para sua Facebook Page
3. ✅ Verificar se o webhook recebe a mensagem
4. ✅ Testar envio de mensagem via API

---

**Criado em**: Janeiro 2025  
**Versão**: 1.0
