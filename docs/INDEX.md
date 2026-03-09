# Índice da Documentação

## 📞 Chamadas WhatsApp (NOVO)

### Guias Principais

- **[CALLS_SETUP.md](CALLS_SETUP.md)** - Configuração completa de chamadas via WhatsApp
- **[CALLS_QUICK_START.md](CALLS_QUICK_START.md)** - Guia rápido para começar em 3 passos
- **[CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)** - Solução de problemas e erros comuns
- **[CALLS_FEATURE_SUMMARY.md](CALLS_FEATURE_SUMMARY.md)** - Resumo da funcionalidade implementada

### ⚠️ Importante

Para usar chamadas, você precisa de uma conta Infobip com o serviço **Voice/Calls** ativado. Se você receber erro "Unauthorized access", consulte o guia de troubleshooting.

## 📱 Meta (Instagram + Facebook Messenger)

### Configuração

- **[INSTAGRAM_SETUP.md](INSTAGRAM_SETUP.md)** - Como configurar Instagram Messaging
- **[META_CREDENTIALS_SETUP.md](META_CREDENTIALS_SETUP.md)** - Obter credenciais da Meta
- **[META_PRODUCTION_DEPLOYMENT.md](META_PRODUCTION_DEPLOYMENT.md)** - Deploy em produção

### Documentação Técnica

- **[META_REQUEST_ADAPTER.md](META_REQUEST_ADAPTER.md)** - Adaptador de requisições Meta
- **[TROUBLESHOOTING_META.md](admin-panel/TROUBLESHOOTING_META.md)** - Troubleshooting específico Meta

## 📚 API e Desenvolvimento

### API

- **[API.md](API.md)** - Documentação completa da API REST
- **[openapi.yaml](openapi.yaml)** - Especificação OpenAPI 3.0
- **[OPENAPI_QUICK_START.md](OPENAPI_QUICK_START.md)** - Guia rápido OpenAPI

### Operações

- **[OPERATIONS_RUNBOOK.md](OPERATIONS_RUNBOOK.md)** - Runbook de operações
- **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist de deploy
- **[UPDATE_PROCEDURE.md](UPDATE_PROCEDURE.md)** - Procedimento de atualização
- **[ROLLBACK_PROCEDURE.md](ROLLBACK_PROCEDURE.md)** - Procedimento de rollback

## 🔧 Troubleshooting Geral

- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Guia geral de troubleshooting
- **[CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)** - Troubleshooting de chamadas
- **[TROUBLESHOOTING_META.md](admin-panel/TROUBLESHOOTING_META.md)** - Troubleshooting Meta

## 📊 Admin Panel

- **[admin-panel/README_TABS.md](../admin-panel/README_TABS.md)** - Documentação do painel administrativo
- **[admin-panel/FEATURES.md](../admin-panel/FEATURES.md)** - Funcionalidades do painel
- **[admin-panel/I18N_README.md](../admin-panel/I18N_README.md)** - Internacionalização

## 🚀 Início Rápido

### Para Mensagens

1. Configure as credenciais no `.env`
2. Acesse `http://localhost:8080/admin-panel/index-tabs.html`
3. Use a aba "💬 Mensagens" para enviar mensagens

### Para Chamadas

1. **Ative o serviço Voice na Infobip** (obrigatório)
2. Configure as credenciais no `.env`
3. Acesse `http://localhost:8080/admin-panel/calls.html`
4. Digite o número e clique em "Iniciar Chamada"

**Se tiver erro "Unauthorized access":**

- Consulte [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)
- Entre em contato: https://www.infobip.com/contact

## 📖 Documentação por Tópico

### Mensagens WhatsApp

- Configuração básica: [README.md](../README.md)
- Templates HSM: [API.md](API.md#templates)
- Troubleshooting: [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

### Chamadas WhatsApp

- Setup: [CALLS_SETUP.md](CALLS_SETUP.md)
- Guia rápido: [CALLS_QUICK_START.md](CALLS_QUICK_START.md)
- Troubleshooting: [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)

### Instagram

- Setup: [INSTAGRAM_SETUP.md](INSTAGRAM_SETUP.md)
- Credenciais: [META_CREDENTIALS_SETUP.md](META_CREDENTIALS_SETUP.md)
- Troubleshooting: [TROUBLESHOOTING_META.md](admin-panel/TROUBLESHOOTING_META.md)

### Facebook Messenger

- Setup: [INSTAGRAM_SETUP.md](INSTAGRAM_SETUP.md) (usa mesma configuração)
- Credenciais: [META_CREDENTIALS_SETUP.md](META_CREDENTIALS_SETUP.md)

### RCS

- Implementação: [md/RCS_IMPLEMENTATION_SUMMARY.md](md/RCS_IMPLEMENTATION_SUMMARY.md)
- Setup Infobip: [md/INFOBIP_RCS_SETUP.md](md/INFOBIP_RCS_SETUP.md)

## 🔗 Links Úteis

### Infobip

- Portal: https://portal.infobip.com
- Documentação: https://www.infobip.com/docs
- Suporte: https://www.infobip.com/contact
- **Ativar Voice**: https://www.infobip.com/contact

### Meta

- Developers: https://developers.facebook.com
- Graph API: https://developers.facebook.com/docs/graph-api
- Instagram API: https://developers.facebook.com/docs/instagram-api

### Twilio (Alternativa)

- Documentação: https://www.twilio.com/docs
- Voice API: https://www.twilio.com/docs/voice

## 📝 Notas de Versão

### v2.0.0 - Chamadas WhatsApp

- ✅ Adicionada funcionalidade de chamadas via WhatsApp
- ✅ Interface web para gerenciar chamadas
- ✅ API endpoints para chamadas
- ✅ Documentação completa
- ⚠️ Requer serviço Voice ativado na Infobip

### v1.x - Mensagens Multi-Plataforma

- ✅ Suporte a WhatsApp, Instagram, Messenger
- ✅ Templates HSM
- ✅ Mensagens interativas
- ✅ RCS

## 🆘 Precisa de Ajuda?

1. **Erro com chamadas?** → [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)
2. **Erro com mensagens?** → [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
3. **Erro com Instagram/Messenger?** → [TROUBLESHOOTING_META.md](admin-panel/TROUBLESHOOTING_META.md)
4. **Dúvidas sobre API?** → [API.md](API.md)
5. **Problemas de deploy?** → [OPERATIONS_RUNBOOK.md](OPERATIONS_RUNBOOK.md)

## 📧 Contato e Suporte

- **Infobip**: support@infobip.com
- **Meta**: https://developers.facebook.com/support
- **Issues**: Abra uma issue no repositório
