# ✅ CHECKLIST DE CONFIGURAÇÃO DO WEBHOOK META

## 🎯 STATUS ATUAL: PRONTO PARA CONFIGURAR

---

## 📋 PRÉ-REQUISITOS (COMPLETO)

- [x] MySQL instalado e rodando
- [x] PHP Server rodando (porta 8081)
- [x] ngrok instalado e rodando
- [x] Credenciais Meta configuradas no .env
- [x] Webhook endpoint respondendo corretamente
- [x] Log file criado

**✅ Todos os pré-requisitos estão completos!**

---

## 🔧 CONFIGURAÇÃO NO META DASHBOARD

### Passo 1: Acessar Meta for Developers

- [ ] Acessei https://developers.facebook.com/apps/
- [ ] Fiz login com minha conta Facebook
- [ ] Selecionei o app (ID: 650370691458548)

### Passo 2: Configurar Messenger Webhook

- [ ] Cliquei em **Messenger** → **Settings**
- [ ] Encontrei a seção **Webhooks**
- [ ] Cliquei em **"Add Callback URL"**

### Passo 3: Adicionar Callback URL

- [ ] Colei a Callback URL:
  ```
  https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta
  ```
- [ ] Colei o Verify Token:
  ```
  d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5
  ```
- [ ] Cliquei em **"Verify and Save"**
- [ ] Vi mensagem de sucesso ✅

### Passo 4: Subscrever Eventos do Messenger

- [ ] Encontrei minha Facebook Page na seção Webhooks
- [ ] Cliquei em **"Add Subscriptions"**
- [ ] Selecionei os eventos:
  - [ ] `messages`
  - [ ] `messaging_postbacks`
  - [ ] `message_deliveries`
  - [ ] `message_reads`
  - [ ] `messaging_optins`
- [ ] Cliquei em **"Save"**

### Passo 5: Configurar Instagram (Opcional)

- [ ] Cliquei em **Instagram** → **Settings**
- [ ] Encontrei a seção **Webhooks**
- [ ] Adicionei o mesmo Callback URL e Verify Token
- [ ] Cliquei em **"Add Subscriptions"**
- [ ] Selecionei os eventos:
  - [ ] `messages`
  - [ ] `messaging_postbacks`
  - [ ] `message_deliveries`
  - [ ] `message_reads`
- [ ] Cliquei em **"Save"**

---

## 🧪 TESTES

### Teste 1: Webhook Verification

- [ ] Meta verificou o webhook com sucesso
- [ ] Vi mensagem de confirmação no dashboard

### Teste 2: Enviar Mensagem via Messenger

- [ ] Acessei minha Facebook Page (CoreMedia Portugal)
- [ ] Enviei uma mensagem de teste via Messenger
- [ ] Verifiquei os logs:
  ```bash
  tail -f storage/logs/whatsapp-adapter.log | grep meta
  ```
- [ ] Vi a mensagem nos logs

### Teste 3: Enviar Mensagem via Instagram (Opcional)

- [ ] Enviei uma mensagem via Instagram Direct
- [ ] Verifiquei os logs
- [ ] Vi a mensagem nos logs

### Teste 4: ngrok Inspector

- [ ] Acessei http://127.0.0.1:4040
- [ ] Vi as requisições do Meta chegando
- [ ] Verifiquei que o status code é 200

### Teste 5: Admin Panel

- [ ] Acessei http://localhost:8081/admin-panel/
- [ ] Verifiquei se as mensagens aparecem
- [ ] Testei enviar uma resposta

---

## 📊 INFORMAÇÕES DE REFERÊNCIA

### URLs Importantes

| Recurso         | URL                                                                |
| --------------- | ------------------------------------------------------------------ |
| Meta Dashboard  | https://developers.facebook.com/apps/650370691458548               |
| Webhook URL     | https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta |
| ngrok Inspector | http://127.0.0.1:4040                                              |
| Admin Panel     | http://localhost:8081/admin-panel/                                 |
| API Docs        | http://localhost:8081/admin-panel/api-docs.html                    |

### Credenciais

| Item         | Valor                                                            |
| ------------ | ---------------------------------------------------------------- |
| App ID       | 650370691458548                                                  |
| Page ID      | 118491818174527                                                  |
| Verify Token | d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5 |
| API Version  | v21.0                                                            |

### Comandos Úteis

```bash
# Ver logs em tempo real
tail -f storage/logs/whatsapp-adapter.log | grep meta

# Testar webhook localmente
curl "http://localhost:8081/webhooks/meta?hub.mode=subscribe&hub.verify_token=d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5&hub.challenge=TEST"

# Ver processos rodando
ps aux | grep -E "(php|ngrok|mysql)" | grep -v grep

# Reiniciar MySQL
brew services restart mysql

# Ver URL atual do ngrok
curl http://127.0.0.1:4040/api/tunnels | jq '.tunnels[0].public_url'
```

---

## 🎯 PRÓXIMOS PASSOS APÓS CONFIGURAÇÃO

### Desenvolvimento

- [ ] Implementar processamento de mensagens recebidas
- [ ] Implementar envio de respostas automáticas
- [ ] Adicionar suporte para diferentes tipos de mensagem
- [ ] Implementar tratamento de erros
- [ ] Adicionar testes automatizados

### Produção

- [ ] Configurar domínio próprio com HTTPS
- [ ] Migrar de ngrok para servidor permanente
- [ ] Configurar monitoramento e alertas
- [ ] Implementar rate limiting
- [ ] Configurar backup do banco de dados
- [ ] Documentar processos operacionais

### Compliance

- [ ] Revisar políticas de privacidade
- [ ] Implementar GDPR compliance
- [ ] Configurar retenção de dados
- [ ] Documentar fluxos de dados
- [ ] Solicitar App Review da Meta (se necessário)

---

## 📝 NOTAS

### Limitações Conhecidas

- ⚠️ URL do ngrok é temporária (muda ao reiniciar)
- ⚠️ Janela de mensagens de 24 horas (Meta)
- ⚠️ Rate limits da API Meta (~200 req/min por page)
- ⚠️ Templates HSM não suportados (Instagram/Messenger)

### Documentação Adicional

- 📄 `WEBHOOK_READY_TO_CONFIGURE.md` - Informações detalhadas do webhook
- 📄 `META_DASHBOARD_SETUP_STEPS.md` - Passo a passo detalhado
- 📄 `docs/INSTAGRAM_SETUP.md` - Guia completo de setup
- 📄 `docs/META_CREDENTIALS_SETUP.md` - Como obter credenciais
- 📄 `docs/TROUBLESHOOTING.md` - Solução de problemas

---

## ✅ CONCLUSÃO

Quando todos os itens acima estiverem marcados, sua integração com Meta (Instagram + Messenger) estará completa e funcionando!

**Data**: 20 Janeiro 2025  
**Versão**: 1.0  
**Status**: 🟢 PRONTO PARA CONFIGURAR
