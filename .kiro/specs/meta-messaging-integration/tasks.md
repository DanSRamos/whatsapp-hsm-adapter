# Implementation Plan: Meta Messaging Integration (Instagram + Facebook Messenger)

## Overview

Este plano implementa a integração do Instagram Messaging API e Facebook Messenger API ao WhatsApp HSM Adapter existente, aproveitando a arquitetura modular baseada em providers.

**Nota Importante**: Instagram e Facebook Messenger usam a mesma Messenger Platform API da Meta, compartilhando endpoints, autenticação e webhooks. Portanto, serão implementados como um único provider Meta que suporta ambas as plataformas com detecção automática.

## Tasks

### FASE 1: Configuração e Estrutura Base

- [x] 1. Setup inicial do Meta Provider

  - Criar estrutura de diretórios `src/Providers/Meta/`
  - Criar arquivo base `MetaProvider.php`
  - Criar `MetaWebhookHandler.php`
  - Criar `MetaMessageFormatter.php`
  - _Requirements: Estrutura modular de providers_

- [x] 2. Configurar credenciais e ambiente

  - Adicionar variáveis Meta ao `.env.example`
  - Criar `config/meta.php` com configurações
  - Atualizar `config/providers.php` para incluir Meta
  - Documentar processo de obtenção de credenciais Meta
  - _Requirements: Autenticação Meta API_

- [x] 3. Criar modelos específicos do Meta

  - Criar `src/Providers/Meta/Models/MetaRecipient.php` (IGSID ou PSID)
  - Criar `src/Providers/Meta/Models/MetaAttachment.php`
  - Criar `src/Providers/Meta/Models/MetaQuickReply.php`
  - Adicionar validação de IGSID e PSID format
  - _Requirements: Modelos de dados Meta_

- [x] 3.1 Implementar MetaPlatformDetector
  - Criar `src/Providers/Meta/MetaPlatformDetector.php`
  - Implementar detecção por estrutura de webhook (messaging vs instagram)
  - Implementar detecção por formato de ID
  - Retornar limites específicos por plataforma (imagens, tamanhos)
  - _Requirements: Detecção automática de plataforma_

### FASE 2: Implementação do Meta Provider Core

- [x] 4. Implementar MetaProvider base

  - [x] 4.1 Implementar WhatsAppProviderInterface

    - Criar classe `MetaProvider implements WhatsAppProviderInterface`
    - Implementar construtor com HttpClient, config e logger
    - Implementar método `getName()` retornando 'meta'
    - Configurar base URL `https://graph.facebook.com/v21.0`
    - _Requirements: Provider interface_

  - [x] 4.2 Implementar autenticação
    - Configurar Page Access Token nos headers
    - Implementar método privado `getAuthHeaders()`
    - Adicionar validação de token
    - _Requirements: Autenticação Meta_

- [x] 5. Implementar envio de mensagens de texto

  - [x] 5.1 Implementar método sendText()

    - Criar payload com recipient.id (IGSID) e message.text
    - Implementar POST para `/{page-id}/messages`
    - Adicionar suporte para preview de URLs
    - Mapear resposta para ProviderSendResult
    - _Requirements: Envio de texto_

  - [x] 5.2 Adicionar tratamento de erros
    - Tratar erro 36103 (conta não elegível)
    - Tratar erro 2534068 (feature não disponível)
    - Tratar erro de janela de 24h expirada
    - Adicionar logging detalhado
    - _Requirements: Error handling_

- [x] 6. Implementar envio de mídia

  - [x] 6.1 Implementar método sendMedia()

    - Suportar tipos: image, video, audio, file
    - Criar payload com attachment.type e attachment.payload.url
    - Implementar POST para `/{page-id}/messages`
    - _Requirements: Envio de mídia_

  - [x] 6.2 Implementar envio de múltiplas imagens

    - Suportar até 10 imagens por mensagem
    - Criar array de attachments
    - Validar limite de imagens
    - _Requirements: Múltiplas imagens_

  - [x] 6.3 Adicionar validações de mídia
    - Validar tamanho: imagens 8MB, vídeos/áudio 25MB
    - Validar formatos suportados
    - Adicionar mensagens de erro descritivas
    - _Requirements: Validação de mídia_

- [x] 7. Implementar mensagens interativas

  - [x] 7.1 Implementar sendInteractiveButtons() com Quick Replies

    - Criar payload com message.quick_replies[]
    - Validar máximo de 13 quick replies
    - Mapear botões para quick_reply format
    - _Requirements: Quick replies_

  - [x] 7.2 Implementar sendInteractiveList() com Generic Template

    - Criar payload com message.attachment.payload.elements[]
    - Suportar cards com imagens e botões
    - Validar limites de texto e botões
    - _Requirements: Generic template_

  - [x] 7.3 Implementar Button Template (específico Messenger)
    - Criar payload com template_type: 'button'
    - Suportar botões de URL, postback e call
    - Validar máximo de 3 botões
    - Detectar automaticamente se é Messenger
    - _Requirements: Button template Messenger_

- [x] 8. Adaptar método sendTemplate()
  - Implementar conversão de template HSM para texto simples
  - Substituir placeholders {{1}}, {{2}} manualmente
  - Adicionar warning no log sobre templates não suportados
  - Retornar erro se template for crítico
  - _Requirements: Compatibilidade com templates_

### FASE 3: Webhooks e Mensagens Recebidas

- [x] 9. Implementar validação de webhook

  - [x] 9.1 Implementar validateWebhook()

    - Verificar header X-Hub-Signature-256
    - Validar HMAC usando App Secret
    - Implementar hash_equals para comparação segura
    - _Requirements: Validação de webhook_

  - [x] 9.2 Implementar verificação GET inicial
    - Criar endpoint GET /webhook/meta (suporta Meta e Messenger)
    - Verificar hub.verify_token
    - Responder com hub.challenge se válido
    - _Requirements: Setup de webhook_

- [x] 10. Processar mensagens recebidas

  - [x] 10.1 Implementar processIncomingMessage()

    - Extrair IGSID (Meta) ou PSID (Messenger) do remetente
    - Detectar automaticamente a plataforma (Meta vs Messenger)
    - Identificar tipo de mensagem (text, attachments, quick_reply)
    - Mapear para IncomingMessage model
    - Incluir metadata da plataforma
    - _Requirements: Mensagens recebidas_

  - [x] 10.2 Suportar diferentes tipos de conteúdo

    - Processar texto simples
    - Processar mídia (image, video, audio, file)
    - Processar quick_reply responses
    - Processar postback de botões
    - _Requirements: Tipos de mensagem_

  - [x] 10.3 Extrair contexto de resposta
    - Identificar reply_to message_id
    - Adicionar contextMessageId ao IncomingMessage
    - _Requirements: Contexto de mensagem_

- [x] 11. Processar delivery reports

  - [x] 11.1 Implementar processDeliveryReport()

    - Processar status: sent, delivered, read
    - Extrair timestamps
    - Mapear para DeliveryReport model
    - _Requirements: Status de entrega_

  - [x] 11.2 Atualizar repositório
    - Atualizar status no MessageRepository
    - Adicionar timestamps de entrega e leitura
    - _Requirements: Persistência de status_

- [x] 12. Implementar tratamento de erros de webhook
  - Tratar mensagens fora da janela de 24h
  - Tratar conta não elegível
  - Implementar retry logic para webhooks
  - Adicionar dead letter queue para falhas
  - _Requirements: Resiliência de webhooks_

### FASE 4: Gestão de Status e Consultas

- [x] 13. Implementar consulta de status de mensagem

  - [x] 13.1 Implementar getMessageStatus()

    - Consultar status no repositório local
    - Retornar último status conhecido
    - Adicionar nota sobre limitação da API Meta
    - _Requirements: Consulta de status_

  - [x] 13.2 Adicionar cache de status

    - Implementar cache Redis/Memcached
    - Configurar TTL apropriado
    - Implementar fallback para repositório
    - _Requirements: Performance_

  - [x] 13.3 Implementar timeout para status desconhecidos
    - Retornar status UNKNOWN após timeout
    - Adicionar logging de status desconhecidos
    - _Requirements: Tratamento de edge cases_

- [x] 14. Implementar gestão de templates (adaptado)
  - Implementar getTemplates() retornando array vazio
  - Implementar getTemplate() retornando null
  - Implementar processTemplateUpdate() como no-op
  - Adicionar mensagens explicativas no log
  - _Requirements: Compatibilidade de interface_

### FASE 5: Integração com MessageService

- [x] 15. Atualizar WhatsAppProviderFactory

  - [x] 15.1 Adicionar suporte para provider 'instagram'

    - Adicionar case 'instagram' no factory
    - Configurar instanciação do MetaProvider
    - Passar configurações corretas
    - _Requirements: Factory pattern_

  - [x] 15.2 Adicionar validação de configuração
    - Validar presença de credenciais Meta
    - Validar formato de Page Access Token
    - Lançar exceção descritiva se inválido
    - _Requirements: Validação de config_

- [x] 16. Adaptar MessageService

  - [x] 16.1 Verificar compatibilidade

    - Testar todos os métodos com Meta provider
    - Verificar logging diferenciado
    - _Requirements: Compatibilidade_

  - [x] 16.2 Adicionar validações específicas Meta
    - Verificar janela de 24h antes de enviar
    - Validar formato IGSID
    - Validar limites de mídia
    - _Requirements: Validações específicas_

- [x] 17. Criar adapter de requests
  - Criar MetaRequestAdapter
  - Converter templates para texto simples
  - Validar e converter formatos de mídia
  - Adicionar logging de conversões
  - _Requirements: Adaptação de requests_

### FASE 6: Atualização do Admin Panel

- [x] 18. Adicionar seletor de provider

  - [x] 18.1 Atualizar frontend (index.html)

    - Adicionar dropdown para selecionar provider (WhatsApp/Meta/Messenger)
    - Mostrar/ocultar campos específicos por provider
    - Ocultar templates quando Meta ou Messenger selecionado
    - Mostrar campo IGSID para Meta, PSID para Messenger
    - _Requirements: UI multi-provider_

  - [x] 18.2 Atualizar backend (api.php)
    - Aceitar parâmetro 'provider' nos endpoints
    - Rotear para provider correto
    - Validar provider suportado (whatsapp/meta/messenger)
    - _Requirements: API multi-provider_

- [x] 19. Adaptar interface de envio

  - [x] 19.1 Criar seção específica Meta

    - Campo para IGSID do destinatário (Meta)
    - Campo para PSID do destinatário (Messenger)
    - Remover campo de templates
    - Suporte para múltiplas imagens (até 10 para Meta, 1 para Messenger)
    - Quick replies para ambas plataformas
    - Button Template (específico Messenger)
    - _Requirements: UI Meta/Messenger_

  - [x] 19.2 Adicionar validações client-side

    - Validar formato IGSID (Meta)
    - Validar formato PSID (Messenger)
    - Validar número de imagens por plataforma
    - Validar tamanhos de arquivo por plataforma
    - _Requirements: Validação frontend_

  - [x] 19.3 Mostrar limitações da API
    - Exibir aviso sobre janela de 24h (Meta e Messenger)
    - Mostrar limites de tamanho de mídia por plataforma
    - Indicar templates não suportados
    - Mostrar diferenças entre Meta e Messenger
    - _Requirements: UX informativa_

- [x] 20. Atualizar visualização de mensagens
  - Diferenciar mensagens por provider (ícones/badges para WhatsApp/Meta/Messenger)
  - Mostrar IGSID para Meta, PSID para Messenger
  - Adicionar filtro por provider (incluir Messenger)
  - Atualizar formatação de mensagens Meta e Messenger
  - _Requirements: Visualização multi-provider_

### FASE 7: Testes Unitários

- [x] 21. Testes do MetaProvider

  - [x] 21.1 Criar MetaProviderTest.php

    - Setup com mocks de HttpClient e Logger
    - _Requirements: Setup de testes_

  - [x] 21.2 Testar envio de texto

    - Testar sendText() com sucesso
    - Testar sendText() com erro
    - Verificar payload correto
    - _Requirements: Testes de texto_

  - [x] 21.3 Testar envio de mídia

    - Testar cada tipo de mídia (image, video, audio, file)
    - Testar múltiplas imagens
    - Testar validações de tamanho
    - _Requirements: Testes de mídia_

  - [x] 21.4 Testar mensagens interativas

    - Testar quick replies
    - Testar generic template
    - Testar validações de limites
    - _Requirements: Testes interativos_

  - [x] 21.5 Testar tratamento de erros
    - Testar erro 36103
    - Testar erro 2534068
    - Testar erro de janela 24h
    - _Requirements: Testes de erro_

- [x] 22. Testes de webhook

  - [x] 22.1 Criar MetaWebhookHandlerTest.php

    - Setup com payloads de exemplo
    - _Requirements: Setup webhook tests_

  - [x] 22.2 Testar validação de webhook

    - Testar validação com signature válida
    - Testar validação com signature inválida
    - Testar verificação GET inicial
    - _Requirements: Testes de validação_

  - [x] 22.3 Testar processamento de mensagens

    - Testar mensagem de texto (Meta e Messenger)
    - Testar mensagem com mídia (Meta e Messenger)
    - Testar quick_reply response (ambas plataformas)
    - Testar detecção automática de plataforma
    - _Requirements: Testes de processamento_

  - [x] 22.4 Testar delivery reports

    - Testar status sent (Meta e Messenger)
    - Testar status delivered (Meta e Messenger)
    - Testar status read (Meta e Messenger)
    - _Requirements: Testes de status_

  - [x] 22.5 Testar MetaPlatformDetector
    - Testar detecção por estrutura de webhook
    - Testar detecção por formato de ID
    - Testar retorno de limites por plataforma
    - _Requirements: Testes de detecção_

- [x] 23. Testes de formatação
  - Criar MetaMessageFormatterTest.php
  - Testar conversão de templates para texto
  - Testar substituição de placeholders
  - Testar formatação de quick replies (Meta e Messenger)
  - Testar formatação de Button Template (Messenger)
  - Testar formatação de attachments (ambas plataformas)
  - Testar múltiplas imagens (Meta vs Messenger)
  - _Requirements: Testes de formatação_

### FASE 8: Testes de Integração

- [x] 24. Testes end-to-end

  - [x] 24.1 Criar MetaMessageFlowTest.php

    - Testar fluxo: envio → webhook → status
    - Usar banco de dados de teste
    - _Requirements: Testes E2E_

  - [x] 24.2 Testar múltiplos tipos de mensagem

    - Testar texto, mídia, interativos (Meta e Messenger)
    - Testar Button Template (Messenger)
    - Testar múltiplas imagens (Meta)
    - Verificar persistência no banco
    - Verificar metadata da plataforma
    - _Requirements: Cobertura completa_

  - [x] 24.3 Testar erro de janela de 24h

    - Simular mensagem fora da janela (Meta e Messenger)
    - Verificar tratamento de erro
    - _Requirements: Testes de edge cases_

  - [x] 24.4 Testar switch entre plataformas
    - Testar envio para Meta e Messenger na mesma sessão
    - Verificar detecção correta de plataforma
    - Verificar aplicação de limites corretos
    - _Requirements: Testes multi-plataforma_
    - Simular mensagem fora da janela
    - Verificar tratamento de erro
    - _Requirements: Testes de edge cases_

- [x] 25. Testes com MessageService
  - Criar MetaMessageServiceTest.php
  - Testar integração via MessageService
  - Testar switch entre providers (WhatsApp ↔ Meta)
  - Testar fallback em caso de erro
  - _Requirements: Testes de integração_

### FASE 9: Documentação

- [x] 26. Documentação técnica

  - [x] 26.1 Criar INSTAGRAM_SETUP.md

    - Como obter credenciais Meta
    - Como configurar Facebook Page
    - Como conectar Meta Professional Account
    - Como configurar webhooks
    - Permissões necessárias
    - _Requirements: Setup guide_

  - [x] 26.2 Atualizar README.md

    - Adicionar seção Meta
    - Documentar diferenças WhatsApp vs Meta
    - Adicionar exemplos de uso
    - _Requirements: Documentação principal_

  - [x] 26.3 Criar guia de troubleshooting
    - Problemas comuns e soluções
    - Erros da API Meta
    - Debugging de webhooks
    - _Requirements: Troubleshooting_

- [x] 27. Documentação da API

  - Atualizar documentação de endpoints
  - Adicionar exemplos de payloads Meta
  - Documentar limitações e restrições
  - Criar tabela comparativa de features
  - _Requirements: API docs_

- [x] 28. Atualizar admin panel docs
  - Atualizar admin-panel/README.md
  - Adicionar screenshots com Meta
  - Documentar fluxo de uso para Meta
  - Criar FAQ específico Meta
  - _Requirements: Admin panel docs_

### FASE 10: Deploy e Monitoramento

- [x] 29. Preparar para produção

  - [x] 29.1 Configurar rate limits

    - Implementar rate limiting específico Meta
    - Configurar limites por endpoint
    - _Requirements: Rate limiting_

  - [x] 29.2 Configurar retry policies

    - Implementar exponential backoff
    - Configurar max retries
    - _Requirements: Resiliência_

  - [x] 29.3 Adicionar circuit breaker

    - Implementar circuit breaker para Meta API (Meta + Messenger)
    - Configurar thresholds
    - _Requirements: Fault tolerance_

  - [x] 29.4 Configurar alertas
    - Alertas de erro de API
    - Alertas de webhook failures
    - Alertas de rate limit
    - _Requirements: Monitoring_

- [x] 30. Monitoramento

  - [x] 30.1 Adicionar métricas

    - Taxa de sucesso de envio
    - Tempo de resposta da API
    - Erros de janela de 24h
    - Webhooks recebidos
    - _Requirements: Métricas_

  - [x] 30.2 Configurar dashboards
    - Dashboard de mensagens Meta
    - Dashboard de erros
    - Dashboard de performance
    - _Requirements: Visualização_

- [x] 31. Documentação de deploy
  - Criar checklist de deploy
  - Documentar rollback procedure
  - Criar runbook para problemas comuns
  - Documentar processo de atualização
  - _Requirements: Ops docs_

### CHECKPOINT FINAL

- [x] 32. Checkpoint - Validação completa
  - Executar todos os testes (unit + integration)
  - Validar documentação completa
  - Testar em ambiente de staging
  - Obter aprovação para produção
  - _Requirements: Quality assurance_

## Notes

- Todas as tarefas devem seguir os padrões de código PSR-12
- Testes devem ter cobertura mínima de 80%
- Documentação deve ser atualizada junto com o código
- Cada fase deve ser revisada antes de prosseguir
- Usar feature branches para desenvolvimento
- Code review obrigatório antes de merge

## Estimativa de Esforço

Com a adição do Facebook Messenger, a estimativa aumenta ligeiramente:

- **Fase 1-2**: 7-9 dias (Setup + Provider Core + Detecção de Plataforma)
- **Fase 3-4**: 5-6 dias (Webhooks + Status)
- **Fase 5-6**: 6 dias (Integração + Admin Panel Multi-Plataforma)
- **Fase 7-10**: 9-13 dias (Testes + Docs + Deploy)
- **Total**: 27-34 dias

**Nota**: O aumento é mínimo (3-4 dias) porque Meta e Messenger compartilham a mesma API. A maior parte do código é reutilizada.

## Dependências

- Meta for Developers account
- Facebook Page configurada
- Meta Professional Account (para Meta)
- Facebook Messenger configurado (para Messenger)
- Permissões aprovadas (pages_messaging, instagram_manage_messages)
- Webhook URL pública
