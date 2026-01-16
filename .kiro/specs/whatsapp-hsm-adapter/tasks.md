# Implementation Plan: WhatsApp HSM Adapter

## Overview

Este plano descreve a implementação de um adapter PHP para integração com APIs WhatsApp de múltiplos provedores (Infobip, Twilio, etc.). A implementação seguirá uma abordagem incremental, começando pela infraestrutura base, depois implementando o suporte multi-provider, e finalmente adicionando todas as funcionalidades de mensagens e webhooks.

## Tasks

- [x] 1. Setup do projeto e estrutura base

  - Criar estrutura de diretórios (src/, tests/, config/)
  - Configurar composer.json com dependências (Guzzle, Monolog, Pest, PHPUnit)
  - Configurar autoloading PSR-4
  - Criar arquivo .env.example com variáveis de configuração
  - Configurar PHPUnit e Pest para testes
  - _Requirements: 11.1, 11.4_

- [x] 2. Implementar camada de dados (Models e Repositories)

  - [x] 2.1 Criar modelos de domínio

    - Implementar Template, Message, IncomingMessage, SendResult, MessageStatus
    - Implementar objetos de request (HSMRequest, TextRequest, MediaRequest, etc.)
    - _Requirements: 1.4, 3.1, 5.2, 6.1, 7.1, 8.2, 9.1, 9.2_

  - [x] 2.2 Escrever testes de propriedade para modelos

    - **Property 4: Request Parameter Validation**
    - **Validates: Requirements 3.1, 3.4, 6.1, 7.7, 9.5**

  - [x] 2.3 Criar interfaces de repositórios

    - Implementar MessageRepositoryInterface e TemplateRepositoryInterface
    - _Requirements: 2.2, 2.3, 5.4, 8.5_

  - [x] 2.4 Implementar repositórios com MySQL/PostgreSQL

    - Criar migrations para tabelas (messages, incoming_messages, templates, webhook_logs)
    - Implementar MessageRepository e TemplateRepository
    - _Requirements: 2.2, 2.3, 5.4, 8.5_

  - [x] 2.5 Escrever testes de propriedade para persistência
    - **Property 3: Template Update Persistence**
    - **Property 11: Incoming Message Persistence**
    - **Validates: Requirements 2.2, 2.3, 5.4, 8.5, 10.5**

- [x] 3. Checkpoint - Verificar estrutura base

  - Garantir que todos os testes passam, perguntar ao utilizador se há questões.

- [x] 4. Implementar arquitetura multi-provider

  - [x] 4.1 Criar interface WhatsAppProviderInterface

    - Definir métodos para envio de mensagens, consulta de status, gestão de templates
    - Definir métodos para validação e processamento de webhooks
    - _Requirements: 1.1, 3.2, 4.1, 5.1, 6.2, 7.5, 8.1, 9.4, 10.1_

  - [x] 4.2 Implementar WhatsAppProviderFactory

    - Criar factory para instanciar providers baseado em configuração
    - Implementar método detectProviderFromWebhook para routing automático
    - _Requirements: 1.1, 3.2_

  - [x] 4.3 Implementar InfobipProvider

    - Implementar todos os métodos da interface para Infobip API
    - Mapear payloads para formato Infobip
    - Implementar validação de webhooks Infobip (HMAC)
    - _Requirements: 1.1, 1.2, 2.1, 2.4, 3.2, 3.6, 4.1, 5.1, 5.2, 6.2, 6.3, 7.1-7.5, 8.1, 8.2, 9.1-9.6, 10.1-10.3, 11.2, 11.3_

  - [x] 4.4 Escrever testes de propriedade para InfobipProvider

    - **Property 1: Template Response Format Consistency**
    - **Property 5: Template Parameter Substitution**
    - **Property 6: Successful Send Response**
    - **Property 12: Text Content Type Support**
    - **Property 19: API Request Authentication**
    - **Validates: Requirements 1.2, 1.4, 3.3, 3.6, 6.3, 6.4, 7.6, 9.4, 11.2**

  - [x] 4.5 Implementar TwilioProvider

    - Implementar todos os métodos da interface para Twilio API
    - Mapear payloads para formato Twilio
    - Implementar validação de webhooks Twilio
    - _Requirements: 1.1, 1.2, 2.1, 2.4, 3.2, 3.6, 4.1, 5.1, 5.2, 6.2, 6.3, 7.1-7.5, 8.1, 8.2, 9.1-9.6, 10.1-10.3, 11.2, 11.3_

  - [x] 4.6 Escrever testes de propriedade para TwilioProvider
    - **Property 1: Template Response Format Consistency**
    - **Property 5: Template Parameter Substitution**
    - **Property 6: Successful Send Response**
    - **Property 19: API Request Authentication**
    - **Validates: Requirements 1.2, 1.4, 3.3, 3.6, 6.4, 7.6, 9.4, 11.2**

- [x] 5. Checkpoint - Verificar providers

  - Garantir que todos os testes passam, perguntar ao utilizador se há questões.

- [x] 6. Implementar camada de serviços

  - [x] 6.1 Implementar RetryHandler

    - Implementar lógica de retry com backoff exponencial
    - Implementar detecção de erros retryable vs permanentes
    - Respeitar header Retry-After
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [x] 6.2 Escrever testes de propriedade para RetryHandler

    - **Property 23: Retry with Exponential Backoff**
    - **Property 24: Maximum Retry Attempts**
    - **Property 25: No Retry on Permanent Errors**
    - **Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5**

  - [x] 6.3 Implementar TemplateService

    - Implementar getAllTemplates com cache
    - Implementar getTemplateById
    - Implementar syncTemplates (sincronização manual de templates)
    - Implementar processTemplateUpdate (webhook)
    - Implementar invalidateCache
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 2.7_

  - [x] 6.4 Escrever testes unitários para TemplateService

    - Testar cache hit/miss
    - Testar invalidação de cache
    - Testar processamento de updates via webhook
    - Testar sincronização manual de templates
    - Testar sincronização com provedor específico
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 2.3, 2.7_

  - [x] 6.5 Implementar MessageService

    - Implementar sendHSM, sendText, sendMedia
    - Implementar sendInteractiveButtons, sendInteractiveList
    - Implementar getMessageStatus
    - Implementar processDeliveryReport, processIncomingMessage
    - Integrar com RetryHandler
    - _Requirements: 3.1-3.6, 4.1-4.3, 5.1-5.4, 6.1-6.4, 7.1-7.7, 8.1-8.5, 9.1-9.6, 10.1-10.5_

  - [x] 6.6 Escrever testes de propriedade para MessageService
    - **Property 7: Error Response Handling**
    - **Property 8: Message Status Query Response**
    - **Property 9: Invalid Message ID Handling**
    - **Property 10: Incoming Message Content Extraction**
    - **Validates: Requirements 1.3, 3.5, 4.1, 4.2, 4.3, 5.2, 8.2, 8.3, 10.1, 10.2, 10.3**

- [x] 7. Checkpoint - Verificar serviços

  - Garantir que todos os testes passam, perguntar ao utilizador se há questões.

- [x] 8. Implementar validações

  - [x] 8.1 Implementar validadores de media

    - Validar formatos de imagem (JPEG, PNG)
    - Validar formatos de documento (PDF, DOC, DOCX, XLS, XLSX)
    - Validar formatos de áudio (MP3, OGG, AMR)
    - Validar formatos de vídeo (MP4, 3GP)
    - Validar tamanhos e durações máximas
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.7_

  - [x] 8.2 Escrever testes de propriedade para validação de media

    - **Property 13: Media Validation**
    - **Property 14: Media Upload Method Support**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.7**

  - [x] 8.3 Implementar validadores de mensagens interativas

    - Validar número máximo de botões (3)
    - Validar número máximo de itens em lista (10)
    - Validar unicidade de IDs e presença de texto descritivo
    - _Requirements: 9.1, 9.2, 9.3, 9.5_

  - [x] 8.4 Escrever testes de propriedade para validação interativa
    - **Property 15: Interactive Button Count Validation**
    - **Property 16: Interactive List Item Count Validation**
    - **Property 17: Interactive Element Uniqueness**
    - **Property 18: Interactive Button Type Support**
    - **Validates: Requirements 9.1, 9.2, 9.3, 9.5, 9.6**

- [x] 9. Implementar camada HTTP (Controllers e Routing)

  - [x] 9.1 Criar RouterInterface e implementação

    - Implementar routing básico para endpoints
    - _Requirements: 1.1, 3.1, 4.1, 6.1, 7.1, 9.1_

  - [x] 9.2 Implementar TemplateController

    - Implementar GET /api/templates
    - Implementar GET /api/templates/{templateId}
    - Implementar POST /api/templates/sync (sincronização manual)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.7_

  - [x] 9.3 Escrever testes unitários para TemplateController

    - Testar resposta com templates válidos
    - Testar resposta com erro da API
    - Testar sincronização manual de templates
    - Testar sincronização com provedor específico
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.7_

  - [x] 9.4 Implementar MessageController

    - Implementar POST /api/messages/hsm
    - Implementar POST /api/messages/text
    - Implementar POST /api/messages/media
    - Implementar POST /api/messages/interactive/buttons
    - Implementar POST /api/messages/interactive/list
    - Implementar GET /api/messages/{messageId}/status
    - _Requirements: 3.1-3.5, 4.1-4.3, 6.1-6.4, 7.1-7.7, 9.1-9.6_

  - [x] 9.5 Escrever testes unitários para MessageController

    - Testar envio bem-sucedido de cada tipo de mensagem
    - Testar validação de parâmetros
    - Testar consulta de status
    - _Requirements: 3.1-3.5, 4.1-4.3, 6.1-6.4_

  - [x] 9.6 Implementar WebhookController

    - Implementar POST /webhooks/delivery-reports
    - Implementar POST /webhooks/incoming-messages
    - Implementar POST /webhooks/template-updates
    - Integrar com detectProviderFromWebhook
    - _Requirements: 2.1, 2.4, 2.5, 5.1, 5.3, 5.5, 8.1, 8.4, 10.1, 10.4_

  - [x] 9.7 Escrever testes de propriedade para WebhookController
    - **Property 2: Webhook Authentication Validation**
    - **Validates: Requirements 2.4, 2.5, 5.3, 5.5, 8.4, 10.4, 11.3**

- [x] 10. Checkpoint - Verificar controllers

  - Garantir que todos os testes passam, perguntar ao utilizador se há questões.

- [x] 11. Implementar segurança e middleware

  - [x] 11.1 Implementar middleware de autenticação

    - Validar API keys em pedidos
    - _Requirements: 11.2_

  - [x] 11.2 Implementar rate limiting

    - Usar Redis para tracking
    - Implementar limites por IP e por API key
    - _Requirements: 11.5_

  - [x] 11.3 Escrever testes de propriedade para rate limiting

    - **Property 20: Rate Limiting Enforcement**
    - **Validates: Requirements 11.5**

  - [x] 11.4 Implementar WebhookValidator
    - Validar assinaturas HMAC
    - Validar IPs de origem (whitelist)
    - _Requirements: 2.4, 5.3, 8.4, 10.4, 11.3_

- [-] 12. Implementar logging e monitorização

  - [x] 12.1 Configurar Monolog

    - Configurar handlers (file, syslog)
    - Configurar formatters (JSON)
    - Configurar níveis de log
    - _Requirements: 12.1, 12.2, 12.3, 12.5_

  - [x] 12.2 Implementar logging em todos os componentes

    - Adicionar logging em services
    - Adicionar logging em controllers
    - Adicionar logging em providers
    - Garantir que informações sensíveis não são registadas
    - _Requirements: 12.1, 12.2, 12.3, 12.5_

  - [x] 12.3 Escrever testes de propriedade para logging

    - **Property 21: Comprehensive Logging**
    - **Validates: Requirements 12.1, 12.2, 12.3, 12.5**

  - [x] 12.4 Implementar notificações de erros críticos

    - Configurar notificações por email/Slack
    - Implementar detecção de erros críticos
    - _Requirements: 12.4_

  - [x] 12.5 Escrever testes de propriedade para notificações
    - **Property 22: Critical Error Notification**
    - **Validates: Requirements 12.4**

- [x] 13. Implementar configuração e deployment

  - [x] 13.1 Criar arquivos de configuração

    - Criar config/whatsapp.php com configuração de providers
    - Criar config/database.php
    - Criar config/cache.php
    - Criar config/logging.php
    - _Requirements: 11.1_

  - [x] 13.2 Criar endpoint de health check

    - Implementar GET /health
    - Verificar conectividade com database
    - Verificar conectividade com Redis
    - Verificar conectividade com providers
    - _Requirements: 12.1_

  - [x] 13.3 Escrever testes unitários para health check

    - Testar resposta quando tudo está OK
    - Testar resposta quando há problemas
    - _Requirements: 12.1_

  - [x] 13.4 Criar documentação da API
    - Documentar todos os endpoints
    - Incluir exemplos de payloads
    - Documentar códigos de erro
    - Documentar configuração de webhooks

- [x] 14. Checkpoint final - Testes de integração

  - [x] 14.1 Escrever testes de integração end-to-end

    - Testar fluxo completo de envio de HSM
    - Testar fluxo completo de recepção de mensagem
    - Testar fluxo completo de webhook de delivery report
    - Testar troca de provider em runtime
    - _Requirements: 1.1-1.4, 2.1-2.5, 3.1-3.6, 4.1-4.4, 5.1-5.5, 6.1-6.6, 7.1-7.7, 8.1-8.5, 9.1-9.6, 10.1-10.5_

  - [x] 14.2 Executar todos os testes

    - Executar suite completa de unit tests
    - Executar suite completa de property tests
    - Executar testes de integração
    - Verificar cobertura de código

  - [x] 14.3 Revisão final
    - Garantir que todos os testes passam
    - Verificar que todos os requisitos estão implementados
    - Perguntar ao utilizador se há questões ou ajustes necessários

## Notes

- Cada task referencia requisitos específicos para rastreabilidade
- Checkpoints garantem validação incremental
- Property tests validam propriedades universais de correção
- Unit tests validam exemplos específicos e casos extremos
- A implementação segue uma abordagem bottom-up: dados → lógica → API
- Todas as tasks de teste são obrigatórias para garantir qualidade e correção do código
