# ✅ Spec de Integração Instagram Criada

## 📦 O Que Foi Criado

Criei uma **spec completa** para integração do Instagram Messaging API ao seu WhatsApp HSM Adapter.

### Estrutura de Arquivos

```
.kiro/specs/instagram-messaging-integration/
├── README.md           (146 linhas) - Visão geral da spec
├── requirements.md     (225 linhas) - 15 requisitos funcionais
├── design.md           (715 linhas) - Design técnico detalhado
└── tasks.md            (511 linhas) - 32 tarefas implementáveis

Total: 1,597 linhas de documentação técnica
```

### Documentos Adicionais

```
./
├── INSTAGRAM_INTEGRATION_PLAN.md     - Plano detalhado completo
├── INSTAGRAM_INTEGRATION_SUMMARY.md  - Resumo executivo
└── INSTAGRAM_SPEC_CREATED.md         - Este arquivo
```

---

## 📋 Requirements (15 Requisitos)

### Requisitos Principais

1. **Configuração e Autenticação** (7 critérios)

   - Page Access Token, App ID, App Secret, Page ID
   - Validação de credenciais

2. **Envio de Mensagens de Texto** (7 critérios)

   - POST para `/{page-id}/messages`
   - IGSID do destinatário
   - Preview de URLs

3. **Envio de Mídia** (10 critérios)

   - Imagens (PNG, JPEG) - máx 8MB
   - Vídeos (MP4, OGG, AVI, MOV, WEBM) - máx 25MB
   - Áudio (AAC, M4A, WAV, MP4) - máx 25MB
   - Documentos (PDF) - máx 25MB
   - **Até 10 imagens por mensagem**

4. **Mensagens Interativas** (7 critérios)

   - Quick Replies (máx 13)
   - Generic Template com cards

5. **Adaptação de Templates HSM** (5 critérios)

   - Conversão para texto simples
   - Substituição de placeholders {{1}}, {{2}}

6. **Webhooks** (10 critérios)

   - Validação X-Hub-Signature-256
   - Verificação GET inicial
   - Processar mensagens recebidas

7. **Delivery Reports** (6 critérios)

   - Status: sent, delivered, read
   - Atualização no repositório

8. **Consulta de Status** (5 critérios)

   - Busca no repositório local
   - Timestamps de entrega/leitura

9. **Janela de 24 Horas** (5 critérios)

   - Validação antes de enviar
   - Erro descritivo se expirada

10. **Tratamento de Erros** (6 critérios)
    - Erro 36103: Conta não elegível
    - Erro 2534068: Feature não disponível
    - Rate limits com retry

11-15. **Integração, Admin Panel, Logging, Documentação**

---

## 🏗️ Design (Arquitetura Técnica)

### Componentes Principais

#### 1. InstagramProvider

```php
class InstagramProvider implements WhatsAppProviderInterface
{
    // Implementa todos os métodos da interface
    // API Base: https://graph.facebook.com/v21.0
    // Autenticação: Bearer {Page Access Token}
}
```

#### 2. InstagramWebhookHandler

```php
class InstagramWebhookHandler
{
    // Valida X-Hub-Signature-256
    // Processa mensagens recebidas
    // Processa delivery reports
}
```

#### 3. InstagramMessageFormatter

```php
class InstagramMessageFormatter
{
    // Formata mensagens para API Instagram
    // Converte templates HSM para texto
    // Formata Quick Replies e Generic Templates
}
```

#### 4. Models

- `InstagramRecipient` - Validação de IGSID
- `InstagramAttachment` - Validação de mídia
- `InstagramQuickReply` - Validação de quick replies

### 15 Propriedades de Corretude

Propriedades testáveis via Property-Based Testing:

1. ✅ Authentication Header Consistency
2. ✅ IGSID Format Validation
3. ✅ Media Size Validation
4. ✅ Multiple Images Limit (10)
5. ✅ Quick Replies Limit (13)
6. ✅ Template Placeholder Substitution
7. ✅ Webhook Signature Validation
8. ✅ Webhook Verification Challenge
9. ✅ Message Status Persistence
10. ✅ Messaging Window Validation (24h)
11. ✅ Provider Factory Resolution
12. ✅ Interface Implementation Completeness
13. ✅ Message Persistence with Provider Metadata
14. ✅ Error Code Mapping
15. ✅ Transient Error Marking

### Estratégia de Testes

**Unit Tests** (PHPUnit):

- InstagramProviderTest
- InstagramWebhookHandlerTest
- InstagramMessageFormatterTest
- InstagramModelsTest

**Property-Based Tests** (Eris):

- 100+ iterações por propriedade
- Validação de todas as 15 propriedades

**Integration Tests**:

- InstagramMessageFlowTest (end-to-end)
- InstagramMessageServiceTest

---

## ✅ Tasks (32 Tarefas em 10 Fases)

### FASE 1: Configuração (3 tarefas)

- [ ] 1. Setup inicial do Instagram Provider
- [ ] 2. Configurar credenciais e ambiente
- [ ] 3. Criar modelos específicos do Instagram

### FASE 2: Provider Core (5 tarefas)

- [ ] 4. Implementar InstagramProvider base
  - [ ] 4.1 Implementar WhatsAppProviderInterface
  - [ ] 4.2 Implementar autenticação
- [ ] 5. Implementar envio de texto
  - [ ] 5.1 Implementar método sendText()
  - [ ] 5.2 Adicionar tratamento de erros
- [ ] 6. Implementar envio de mídia
  - [ ] 6.1 Implementar método sendMedia()
  - [ ] 6.2 Implementar múltiplas imagens
  - [ ] 6.3 Adicionar validações
- [ ] 7. Implementar mensagens interativas
  - [ ] 7.1 Quick Replies
  - [ ] 7.2 Generic Template
- [ ] 8. Adaptar método sendTemplate()

### FASE 3: Webhooks (4 tarefas)

- [ ] 9. Implementar validação de webhook
  - [ ] 9.1 Implementar validateWebhook()
  - [ ] 9.2 Verificação GET inicial
- [ ] 10. Processar mensagens recebidas
  - [ ] 10.1 Implementar processIncomingMessage()
  - [ ] 10.2 Suportar tipos de conteúdo
  - [ ] 10.3 Extrair contexto
- [ ] 11. Processar delivery reports
  - [ ] 11.1 Implementar processDeliveryReport()
  - [ ] 11.2 Atualizar repositório
- [ ] 12. Tratamento de erros de webhook

### FASE 4: Status (2 tarefas)

- [ ] 13. Implementar consulta de status
  - [ ] 13.1 Implementar getMessageStatus()
  - [ ] 13.2 Adicionar cache
  - [ ] 13.3 Timeout para desconhecidos
- [ ] 14. Gestão de templates (adaptado)

### FASE 5: Integração (3 tarefas)

- [ ] 15. Atualizar WhatsAppProviderFactory
  - [ ] 15.1 Adicionar suporte 'instagram'
  - [ ] 15.2 Validação de configuração
- [ ] 16. Adaptar MessageService
  - [ ] 16.1 Verificar compatibilidade
  - [ ] 16.2 Validações específicas
- [ ] 17. Criar adapter de requests

### FASE 6: Admin Panel (3 tarefas)

- [ ] 18. Adicionar seletor de provider
  - [ ] 18.1 Atualizar frontend
  - [ ] 18.2 Atualizar backend
- [ ] 19. Adaptar interface de envio
  - [ ] 19.1 Seção específica Instagram
  - [ ] 19.2 Validações client-side
  - [ ] 19.3 Mostrar limitações
- [ ] 20. Atualizar visualização de mensagens

### FASE 7: Testes Unitários (3 tarefas)

- [ ] 21. Testes do InstagramProvider (5 sub-tarefas)
- [ ] 22. Testes de webhook (4 sub-tarefas)
- [ ] 23. Testes de formatação

### FASE 8: Testes de Integração (2 tarefas)

- [ ] 24. Testes end-to-end (3 sub-tarefas)
- [ ] 25. Testes com MessageService

### FASE 9: Documentação (3 tarefas)

- [ ] 26. Documentação técnica (3 sub-tarefas)
- [ ] 27. Documentação da API
- [ ] 28. Atualizar admin panel docs

### FASE 10: Deploy (3 tarefas)

- [ ] 29. Preparar para produção (4 sub-tarefas)
- [ ] 30. Monitoramento (2 sub-tarefas)
- [ ] 31. Documentação de deploy

### CHECKPOINT FINAL

- [ ] 32. Validação completa

---

## 📊 Estimativa de Esforço

| Fase                  | Duração        | Complexidade |
| --------------------- | -------------- | ------------ |
| Fase 1: Configuração  | 2-3 dias       | Baixa        |
| Fase 2: Provider Core | 4-5 dias       | Alta         |
| Fase 3: Webhooks      | 3-4 dias       | Alta         |
| Fase 4: Status        | 2 dias         | Média        |
| Fase 5: Integração    | 2 dias         | Média        |
| Fase 6: Admin Panel   | 3 dias         | Média        |
| Fase 7: Testes Unit   | 3 dias         | Média        |
| Fase 8: Testes Int    | 2 dias         | Média        |
| Fase 9: Documentação  | 2 dias         | Baixa        |
| Fase 10: Deploy       | 1-2 dias       | Baixa        |
| **TOTAL**             | **24-30 dias** | -            |

### Sprints Sugeridos

**Sprint 1** (Semana 1): Fases 1-2 - Setup + Provider Core  
**Sprint 2** (Semana 2): Fases 3-4 - Webhooks + Status  
**Sprint 3** (Semana 3): Fases 5-6 - Integração + Admin Panel  
**Sprint 4** (Semana 4): Fases 7-10 - Testes + Docs + Deploy

---

## 🔑 Principais Diferenças: WhatsApp vs Instagram

| Aspecto           | WhatsApp (Infobip)   | Instagram (Meta)    |
| ----------------- | -------------------- | ------------------- |
| **Templates**     | ✅ Obrigatório (HSM) | ❌ Não suportado    |
| **Autenticação**  | API Key              | Page Access Token   |
| **Identificador** | Número de telefone   | IGSID               |
| **API Base**      | api.infobip.com      | graph.facebook.com  |
| **Imagens/msg**   | 1                    | Até 10              |
| **Webhooks**      | HMAC SHA-256         | X-Hub-Signature-256 |
| **Status Query**  | Endpoint direto      | Via webhooks        |
| **Janela**        | 24h                  | 24h                 |

---

## 🎯 Próximos Passos

### 1. Revisar a Spec ✅

- [x] Requirements criados
- [x] Design criado
- [x] Tasks criadas
- [ ] **Aprovação necessária**

### 2. Obter Credenciais Meta

- [ ] Criar conta Meta for Developers
- [ ] Criar App Meta
- [ ] Criar/configurar Facebook Page
- [ ] Criar Instagram Professional Account
- [ ] Conectar Instagram à Page
- [ ] Solicitar permissão `instagram_manage_messages`
- [ ] Gerar Page Access Token
- [ ] Obter App Secret

### 3. Configurar Ambiente

- [ ] Adicionar variáveis ao `.env`
- [ ] Configurar webhook URL (ngrok para dev)
- [ ] Testar conexão com API

### 4. Iniciar Implementação

- [ ] Criar branch `feature/instagram-integration`
- [ ] Começar pela Fase 1: Configuração
- [ ] Seguir tasks.md sequencialmente

---

## 📚 Documentação de Referência

### Arquivos Criados

1. `.kiro/specs/instagram-messaging-integration/README.md` - Visão geral
2. `.kiro/specs/instagram-messaging-integration/requirements.md` - Requisitos
3. `.kiro/specs/instagram-messaging-integration/design.md` - Design técnico
4. `.kiro/specs/instagram-messaging-integration/tasks.md` - Tarefas
5. `INSTAGRAM_INTEGRATION_PLAN.md` - Plano detalhado
6. `INSTAGRAM_INTEGRATION_SUMMARY.md` - Resumo executivo

### Links Úteis

- [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram/)
- [Send Messages](https://developers.facebook.com/docs/messenger-platform/instagram/features/send-message/)
- [Webhooks](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Graph API](https://developers.facebook.com/docs/graph-api/)

---

## ✨ Benefícios da Integração

1. **Multi-canal**: WhatsApp + Instagram na mesma plataforma
2. **Arquitetura reutilizada**: Mesma estrutura de providers
3. **Admin panel unificado**: Gerenciar ambos em um lugar
4. **Código compartilhado**: MessageService, repositórios, etc.
5. **Fácil expansão**: Adicionar novos providers no futuro

---

## 🚀 Pronto para Começar!

Toda a documentação está criada e pronta para uso. Você pode:

1. **Revisar a spec** em `.kiro/specs/instagram-messaging-integration/`
2. **Obter credenciais** Meta seguindo o guia
3. **Iniciar implementação** seguindo as tasks

**Boa sorte com a integração! 🎉**

---

**Criado em**: 2025-01-16  
**Por**: Kiro AI Assistant  
**Versão**: 1.0
