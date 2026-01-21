# Resumo Executivo: Integração Instagram Messaging

## Visão Geral

Plano para adicionar suporte ao Instagram Messaging API ao adapter existente, aproveitando a arquitetura modular de providers.

## Principais Diferenças: WhatsApp vs Instagram

| Aspecto           | WhatsApp          | Instagram         |
| ----------------- | ----------------- | ----------------- |
| **Templates**     | Obrigatório (HSM) | Não suportado     |
| **Autenticação**  | API Key           | Page Access Token |
| **Identificador** | Telefone          | IGSID             |
| **API Base**      | Infobip           | Meta Graph API    |
| **Imagens/msg**   | 1                 | Até 10            |

## Estrutura de Implementação

```
src/Providers/Instagram/
├── InstagramProvider.php              # Provider principal
├── InstagramWebhookHandler.php        # Processamento de webhooks
├── InstagramMessageFormatter.php      # Formatação de mensagens
└── Models/
    ├── InstagramRecipient.php
    ├── InstagramAttachment.php
    └── InstagramQuickReply.php
```

## Fases Principais

### 1. Setup (2-3 dias) ⚡ ALTA

- Estrutura de diretórios
- Configuração de credenciais Meta
- Modelos específicos Instagram

### 2. Provider Core (4-5 dias) ⚡ ALTA

- Implementar `InstagramProvider`
- Envio de texto, mídia, interativos
- Adaptar templates (não suportados)

### 3. Webhooks (3-4 dias) ⚡ ALTA

- Validação de webhooks Meta
- Processar mensagens recebidas
- Delivery reports

### 4. Status & Queries (2 dias) 🔶 MÉDIA

- Consulta de status (via repositório)
- Cache de status
- Templates (retornar vazio)

### 5. Integração (2 dias) ⚡ ALTA

- Atualizar `WhatsAppProviderFactory`
- Adaptar `MessageService`
- Request adapters

### 6. Admin Panel (3 dias) 🔶 MÉDIA

- Seletor de provider
- Interface específica Instagram
- Visualização diferenciada

### 7-8. Testes (5 dias) ⚡ ALTA

- Testes unitários completos
- Testes de integração
- Testes end-to-end

### 9. Documentação (2 dias) 🔶 MÉDIA

- Setup Meta/Facebook/Instagram
- Guias de uso
- Troubleshooting

### 10. Deploy (1-2 dias) 🔵 BAIXA

- Rate limits e monitoring
- Alertas e dashboards

## Esforço Total

**24-30 dias** divididos em 4 sprints de 1 semana

## Pré-requisitos Críticos

✅ Conta Meta for Developers  
✅ App Meta criado  
✅ Facebook Page configurada  
✅ Instagram Professional Account  
✅ Permissão `instagram_manage_messages`  
✅ Page Access Token  
✅ Webhook URL pública

## Configuração Necessária

```env
INSTAGRAM_PAGE_ACCESS_TOKEN=...
INSTAGRAM_APP_ID=...
INSTAGRAM_APP_SECRET=...
INSTAGRAM_PAGE_ID=...
INSTAGRAM_VERIFY_TOKEN=...
```

## Principais Desafios

1. **Templates não suportados** → Converter para texto simples
2. **Janela de 24h** → Validar antes de enviar
3. **IGSID vs Telefone** → Adapter de identificadores
4. **Rate limits Meta** → Implementar backoff
5. **Webhooks Meta** → Validação HMAC diferente

## Benefícios

✨ Suporte multi-canal (WhatsApp + Instagram)  
✨ Arquitetura reutilizada (providers)  
✨ Admin panel unificado  
✨ Mesma base de código  
✨ Fácil adicionar novos providers

## Próximo Passo

Revisar plano completo em `INSTAGRAM_INTEGRATION_PLAN.md` e obter credenciais Meta.
