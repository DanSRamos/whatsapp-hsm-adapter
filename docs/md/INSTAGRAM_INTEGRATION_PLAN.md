# Plano de Integração: Instagram Messaging API

## Visão Geral

Este documento descreve o plano de implementação para integrar o Instagram Messaging API ao WhatsApp HSM Adapter existente, aproveitando a arquitetura modular baseada em providers.

## Contexto

O projeto atual possui:

- Arquitetura baseada em providers (WhatsAppProviderInterface)
- Implementação completa do Infobip provider
- Serviços de mensagens, templates e webhooks
- Repositórios para persistência de dados
- Sistema de retry e tratamento de erros
- Admin panel para gestão de mensagens

## Objetivo

Adicionar suporte para Instagram Messaging API (Meta/Facebook) como um novo provider, permitindo:

- Envio de mensagens de texto
- Envio de mídia (imagens, vídeos, áudio, documentos)
- Envio de mensagens interativas (botões, quick replies)
- Recebimento de mensagens via webhook
- Gestão de status de entrega
- Janela de mensagens de 24 horas

## Diferenças Principais: WhatsApp vs Instagram

| Característica          | WhatsApp (Infobip)                  | Instagram Messaging                          |
| ----------------------- | ----------------------------------- | -------------------------------------------- |
| **Templates/HSM**       | Obrigatório para iniciar conversa   | Não suportado                                |
| **Janela de Mensagens** | 24h após última mensagem do usuário | 24h após última mensagem do usuário          |
| **Autenticação**        | API Key                             | Page Access Token + App ID                   |
| **Identificador**       | Número de telefone                  | Instagram-scoped ID (IGSID)                  |
| **Endpoint Base**       | api.infobip.com                     | graph.facebook.com                           |
| **Webhooks**            | HMAC signature                      | App Secret verification                      |
| **Tipos de Mensagem**   | Text, Media, Interactive, Template  | Text, Media, Quick Replies, Generic Template |
| **Limite de Imagens**   | 1 por mensagem                      | Até 10 por mensagem                          |

---

## Fases de Implementação

### **FASE 1: Configuração e Estrutura Base** (Prioridade: ALTA)

**Duração estimada**: 2-3 dias

#### 1.1 Criar estrutura de diretórios para Instagram Provider

- [ ] Criar `src/Providers/Instagram/` directory
- [ ] Criar `src/Providers/Instagram/InstagramProvider.php`
- [ ] Criar `src/Providers/Instagram/InstagramWebhookHandler.php`
- [ ] Criar `src/Providers/Instagram/InstagramMessageFormatter.php`

#### 1.2 Configurar credenciais e ambiente

- [ ] Adicionar configurações Instagram ao `.env.example`:
  - `INSTAGRAM_PAGE_ACCESS_TOKEN`
  - `INSTAGRAM_APP_ID`
  - `INSTAGRAM_APP_SECRET`
  - `INSTAGRAM_PAGE_ID`
  - `INSTAGRAM_VERIFY_TOKEN` (para webhook)
- [ ] Atualizar `config/providers.php` para incluir Instagram
- [ ] Documentar processo de obtenção de credenciais Meta

#### 1.3 Criar modelos específicos do Instagram

- [ ] Criar `src/Providers/Instagram/Models/InstagramRecipient.php`
  - Suporte para IGSID (Instagram-scoped ID)
- [ ] Criar `src/Providers/Instagram/Models/InstagramAttachment.php`
  - Suporte para múltiplas imagens (até 10)
- [ ] Criar `src/Providers/Instagram/Models/InstagramQuickReply.php`

---

### **FASE 2: Implementação do Instagram Provider** (Prioridade: ALTA)

**Duração estimada**: 4-5 dias

#### 2.1 Implementar InstagramProvider base

- [ ] Implementar `WhatsAppProviderInterface` em `InstagramProvider`
- [ ] Implementar método `getName()` retornando 'instagram'
- [ ] Configurar HTTP client com base URL `https://graph.facebook.com/v21.0`
- [ ] Implementar autenticação via Page Access Token

#### 2.2 Implementar envio de mensagens de texto

- [ ] Implementar `sendText(TextRequest $request)`
  - Endpoint: `POST /{page-id}/messages`
  - Payload: `recipient.id` (IGSID) + `message.text`
- [ ] Adicionar suporte para preview de URLs
- [ ] Implementar tratamento de erros específicos do Instagram
- [ ] Adicionar logging detalhado

#### 2.3 Implementar envio de mídia

- [ ] Implementar `sendMedia(MediaRequest $request)`
  - Suporte para image, video, audio, file
  - Endpoint: `POST /{page-id}/messages`
  - Payload: `message.attachment.type` + `message.attachment.payload.url`
- [ ] Implementar envio de múltiplas imagens (até 10)
- [ ] Validar limites de tamanho:
  - Imagens: 8MB
  - Vídeos/Áudio: 25MB
  - Documentos: 25MB
- [ ] Adicionar validação de formatos suportados

#### 2.4 Implementar mensagens interativas

- [ ] Implementar `sendInteractiveButtons()` usando Quick Replies
  - Máximo 13 quick replies
  - Payload: `message.quick_replies[]`
- [ ] Implementar `sendInteractiveList()` usando Generic Template
  - Suporte para cards com imagens
  - Botões por card
- [ ] Adicionar validação de limites (texto, botões, etc.)

#### 2.5 Adaptar método sendTemplate()

- [ ] Implementar `sendTemplate()` com comportamento especial:
  - Instagram não suporta templates HSM
  - Converter para mensagem de texto simples
  - Substituir placeholders manualmente
  - Adicionar warning no log
  - Retornar erro se template for obrigatório

---

### **FASE 3: Webhooks e Mensagens Recebidas** (Prioridade: ALTA)

**Duração estimada**: 3-4 dias

#### 3.1 Implementar validação de webhook

- [ ] Implementar `validateWebhook(ServerRequestInterface $request)`
  - Verificar `X-Hub-Signature-256` header
  - Validar usando App Secret
  - Implementar verificação GET para setup inicial
- [ ] Adicionar endpoint de verificação: `GET /webhook/instagram`
  - Responder com `hub.challenge` se `hub.verify_token` correto

#### 3.2 Processar mensagens recebidas

- [ ] Implementar `processIncomingMessage(array $payload)`
  - Extrair IGSID do remetente
  - Identificar tipo de mensagem (text, media, quick_reply, etc.)
  - Mapear para `IncomingMessage` model
- [ ] Suportar diferentes tipos de conteúdo:
  - Texto simples
  - Mídia (image, video, audio, file)
  - Quick reply responses
  - Postback de botões
- [ ] Extrair contexto de resposta (reply to message)

#### 3.3 Processar delivery reports

- [ ] Implementar `processDeliveryReport(array $payload)`
  - Status: sent, delivered, read
  - Mapear para `DeliveryReport` model
- [ ] Atualizar status no repositório
- [ ] Adicionar timestamps de entrega e leitura

#### 3.4 Implementar tratamento de erros de webhook

- [ ] Tratar mensagens fora da janela de 24h
- [ ] Tratar conta não elegível (erro 36103)
- [ ] Tratar feature não disponível (erro 2534068)
- [ ] Implementar retry logic para webhooks

---

### **FASE 4: Gestão de Status e Consultas** (Prioridade: MÉDIA)

**Duração estimada**: 2 dias

#### 4.1 Implementar consulta de status de mensagem

- [ ] Implementar `getMessageStatus(string $messageId)`
  - **NOTA**: Instagram não tem endpoint direto para status
  - Consultar no repositório local (atualizado via webhook)
  - Retornar último status conhecido
- [ ] Adicionar cache de status
- [ ] Implementar timeout para status desconhecidos

#### 4.2 Implementar gestão de templates (adaptado)

- [ ] Implementar `getTemplates()` retornando array vazio
  - Instagram não suporta templates
  - Adicionar mensagem explicativa no log
- [ ] Implementar `getTemplate(string $templateId)` retornando null
- [ ] Implementar `processTemplateUpdate()` como no-op

---

### **FASE 5: Integração com MessageService** (Prioridade: ALTA)

**Duração estimada**: 2 dias

#### 5.1 Atualizar WhatsAppProviderFactory

- [ ] Adicionar suporte para provider 'instagram'
- [ ] Configurar instanciação do InstagramProvider
- [ ] Adicionar validação de configuração Instagram

#### 5.2 Adaptar MessageService

- [ ] Verificar compatibilidade com Instagram provider
- [ ] Adicionar validação específica para Instagram:
  - Verificar janela de 24h
  - Validar IGSID format
  - Validar limites de mídia
- [ ] Atualizar logging para diferenciar providers

#### 5.3 Criar adapter de requests

- [ ] Criar `InstagramRequestAdapter` para converter:
  - Números de telefone → IGSID (se necessário)
  - Templates → Mensagens de texto
  - Formatos de mídia
- [ ] Adicionar validação de conversão

---

### **FASE 6: Atualização do Admin Panel** (Prioridade: MÉDIA)

**Duração estimada**: 3 dias

#### 6.1 Adicionar seletor de provider

- [ ] Atualizar `admin-panel/index.html`:
  - Dropdown para selecionar provider (WhatsApp/Instagram)
  - Mostrar/ocultar campos específicos por provider
  - Templates apenas para WhatsApp
- [ ] Atualizar `admin-panel/api.php`:
  - Aceitar parâmetro `provider`
  - Rotear para provider correto

#### 6.2 Adaptar interface de envio

- [ ] Criar seção específica para Instagram:
  - Campo para IGSID do destinatário
  - Sem suporte para templates
  - Suporte para múltiplas imagens
  - Quick replies em vez de botões
- [ ] Adicionar validações client-side
- [ ] Mostrar limitações da API (24h window, etc.)

#### 6.3 Atualizar visualização de mensagens

- [ ] Diferenciar mensagens por provider
- [ ] Mostrar IGSID em vez de número de telefone
- [ ] Adicionar ícones/badges por provider
- [ ] Filtrar mensagens por provider

---

### **FASE 7: Testes Unitários** (Prioridade: ALTA)

**Duração estimada**: 3 dias

#### 7.1 Testes do InstagramProvider

- [ ] Criar `tests/Unit/Providers/Instagram/InstagramProviderTest.php`
- [ ] Testar envio de texto
- [ ] Testar envio de mídia (todos os tipos)
- [ ] Testar envio de quick replies
- [ ] Testar tratamento de erros
- [ ] Testar autenticação

#### 7.2 Testes de webhook

- [ ] Criar `tests/Unit/Providers/Instagram/InstagramWebhookHandlerTest.php`
- [ ] Testar validação de webhook
- [ ] Testar processamento de mensagens recebidas
- [ ] Testar processamento de delivery reports
- [ ] Testar verificação inicial (GET request)

#### 7.3 Testes de formatação

- [ ] Criar `tests/Unit/Providers/Instagram/InstagramMessageFormatterTest.php`
- [ ] Testar conversão de templates para texto
- [ ] Testar formatação de quick replies
- [ ] Testar formatação de attachments

---

### **FASE 8: Testes de Integração** (Prioridade: ALTA)

**Duração estimada**: 2 dias

#### 8.1 Testes end-to-end

- [ ] Criar `tests/Integration/InstagramMessageFlowTest.php`
- [ ] Testar fluxo completo: envio → webhook → status
- [ ] Testar múltiplos tipos de mensagem
- [ ] Testar erro de janela de 24h

#### 8.2 Testes com MessageService

- [ ] Criar `tests/Integration/InstagramMessageServiceTest.php`
- [ ] Testar integração via MessageService
- [ ] Testar switch entre providers
- [ ] Testar fallback em caso de erro

---

### **FASE 9: Documentação** (Prioridade: MÉDIA)

**Duração estimada**: 2 dias

#### 9.1 Documentação técnica

- [ ] Criar `docs/INSTAGRAM_SETUP.md`:
  - Como obter credenciais Meta
  - Como configurar Facebook Page
  - Como conectar Instagram Professional Account
  - Como configurar webhooks
  - Permissões necessárias
- [ ] Atualizar `README.md` com informações Instagram
- [ ] Documentar diferenças entre WhatsApp e Instagram

#### 9.2 Documentação da API

- [ ] Atualizar documentação de endpoints
- [ ] Adicionar exemplos de payloads Instagram
- [ ] Documentar limitações e restrições
- [ ] Criar guia de troubleshooting

#### 9.3 Atualizar admin panel docs

- [ ] Atualizar `admin-panel/README.md`
- [ ] Adicionar screenshots com Instagram
- [ ] Documentar fluxo de uso para Instagram

---

### **FASE 10: Deploy e Monitoramento** (Prioridade: BAIXA)

**Duração estimada**: 1-2 dias

#### 10.1 Preparar para produção

- [ ] Configurar rate limits específicos do Instagram
- [ ] Configurar retry policies
- [ ] Adicionar circuit breaker para Instagram API
- [ ] Configurar alertas de erro

#### 10.2 Monitoramento

- [ ] Adicionar métricas específicas Instagram:
  - Taxa de sucesso de envio
  - Tempo de resposta da API
  - Erros de janela de 24h
  - Webhooks recebidos
- [ ] Configurar dashboards
- [ ] Configurar alertas

#### 10.3 Documentação de deploy

- [ ] Criar checklist de deploy
- [ ] Documentar rollback procedure
- [ ] Criar runbook para problemas comuns

---

## Requisitos Técnicos

### Dependências Adicionais

```json
{
  "facebook/graph-sdk": "^6.0" // Opcional, para facilitar integração
}
```

### Configuração Mínima Necessária

```env
# Instagram Messaging API
INSTAGRAM_PAGE_ACCESS_TOKEN=your_page_access_token
INSTAGRAM_APP_ID=your_app_id
INSTAGRAM_APP_SECRET=your_app_secret
INSTAGRAM_PAGE_ID=your_page_id
INSTAGRAM_VERIFY_TOKEN=your_custom_verify_token
INSTAGRAM_API_VERSION=v21.0
```

### Permissões Meta Necessárias

- `pages_messaging` - Enviar e receber mensagens
- `instagram_basic` - Acesso básico ao Instagram
- `instagram_manage_messages` - Gerenciar mensagens do Instagram
- `pages_read_engagement` - Ler engajamento da página

---

## Riscos e Mitigações

| Risco                        | Impacto | Probabilidade | Mitigação                                          |
| ---------------------------- | ------- | ------------- | -------------------------------------------------- |
| Conta Instagram não elegível | Alto    | Média         | Validar elegibilidade antes, documentar requisitos |
| Janela de 24h expirada       | Médio   | Alta          | Implementar verificação prévia, notificar usuário  |
| Rate limits da Meta API      | Médio   | Média         | Implementar rate limiting, retry com backoff       |
| Webhooks não recebidos       | Alto    | Baixa         | Implementar polling de fallback, alertas           |
| Mudanças na API Meta         | Médio   | Média         | Usar versão específica da API, monitorar changelog |
| Templates não suportados     | Baixo   | Alta          | Documentar claramente, converter para texto        |

---

## Estimativa de Esforço

| Fase                      | Duração        | Complexidade |
| ------------------------- | -------------- | ------------ |
| Fase 1: Configuração      | 2-3 dias       | Baixa        |
| Fase 2: Provider Core     | 4-5 dias       | Alta         |
| Fase 3: Webhooks          | 3-4 dias       | Alta         |
| Fase 4: Status/Queries    | 2 dias         | Média        |
| Fase 5: Integração        | 2 dias         | Média        |
| Fase 6: Admin Panel       | 3 dias         | Média        |
| Fase 7: Testes Unitários  | 3 dias         | Média        |
| Fase 8: Testes Integração | 2 dias         | Média        |
| Fase 9: Documentação      | 2 dias         | Baixa        |
| Fase 10: Deploy           | 1-2 dias       | Baixa        |
| **TOTAL**                 | **24-30 dias** | -            |

**Sprints sugeridos**:

- Sprint 1 (1 semana): Fases 1-2 (Setup + Provider Core)
- Sprint 2 (1 semana): Fases 3-4 (Webhooks + Status)
- Sprint 3 (1 semana): Fases 5-6 (Integração + Admin Panel)
- Sprint 4 (1 semana): Fases 7-10 (Testes + Docs + Deploy)

---

## Checklist de Pré-requisitos

Antes de iniciar a implementação, certifique-se de ter:

- [ ] Conta Meta for Developers criada
- [ ] App Meta criado no App Dashboard
- [ ] Facebook Page criada e configurada
- [ ] Instagram Professional Account criado
- [ ] Instagram Account conectado à Facebook Page
- [ ] Permissões `instagram_manage_messages` aprovadas
- [ ] Page Access Token gerado
- [ ] App Secret obtido
- [ ] Webhook URL pública disponível (ngrok para dev)
- [ ] Conta Instagram com mensagens habilitadas (verificar elegibilidade)

---

## Próximos Passos

1. **Revisar e aprovar este plano**
2. **Obter credenciais Meta necessárias**
3. **Configurar ambiente de desenvolvimento**
4. **Iniciar Fase 1: Configuração e Estrutura Base**
5. **Executar testes incrementais a cada fase**

---

## Referências

- [Instagram Messaging API - Meta Developers](https://developers.facebook.com/docs/messenger-platform/instagram/)
- [Send Messages - Instagram](https://developers.facebook.com/docs/messenger-platform/instagram/features/send-message/)
- [Webhooks - Messenger Platform](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Graph API Reference](https://developers.facebook.com/docs/graph-api/)
- [Instagram API Permissions](https://developers.facebook.com/docs/permissions/reference)

---

**Documento criado em**: 2025-01-16  
**Versão**: 1.0  
**Autor**: Kiro AI Assistant
