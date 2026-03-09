# Admin Panel com Sistema de Tabs

## Visão Geral

O Admin Panel foi atualizado com um sistema de tabs que organiza a interface em páginas principais:

1. **💬 Mensagens** - Interface para envio e visualização de mensagens
2. **📞 Chamadas** - Interface para fazer chamadas via WhatsApp (requer serviço Voice ativado)
3. **📚 Documentação** - Acesso centralizado a toda documentação do sistema

## ⚠️ Importante: Chamadas WhatsApp

A funcionalidade de chamadas requer:

- Conta Infobip com serviço **Voice/Calls** ativado
- Se você receber erro "Unauthorized access", consulte [CALLS_TROUBLESHOOTING.md](../docs/CALLS_TROUBLESHOOTING.md)
- Entre em contato com a Infobip: https://www.infobip.com/contact

## Estrutura de Arquivos

```
admin-panel/
├── index-tabs.html          # Página principal com tabs
├── index.html               # Página de mensagens
├── calls.html               # Página de chamadas WhatsApp (NOVO)
├── rcs.html                 # Página de mensagens RCS
├── monitoring.html          # Página de monitoramento
├── styles.css               # Estilos compartilhados
├── api.php                  # Backend API (inclui endpoints de chamadas)
└── README_TABS.md          # Este arquivo
```

## Como Usar

### Opção 1: Usar a Interface com Tabs (Recomendado)

Acesse: `http://localhost:8080/admin-panel/index-tabs.html`

Esta é a interface recomendada que integra todas as funcionalidades em uma única página com navegação por tabs.

### Opção 2: Usar Páginas Individuais

Você também pode acessar cada página diretamente:

- **Mensagens**: `http://localhost:8080/admin-panel/index.html`
- **Chamadas**: `http://localhost:8080/admin-panel/calls.html`
- **RCS**: `http://localhost:8080/admin-panel/rcs.html`
- **Monitoramento**: `http://localhost:8080/admin-panel/monitoring.html`

## Funcionalidades por Página

### 1. Mensagens (index.html)

Interface para envio de mensagens:

- Envio de mensagens via WhatsApp, Instagram e Messenger
- Seleção de templates HSM (WhatsApp)
- Visualização de mensagens recebidas
- Filtros por provider

### 2. Chamadas (calls.html) 🆕

Interface para chamadas via WhatsApp:

- Formulário para iniciar chamadas
- Monitoramento de status em tempo real
- Timer de duração da chamada
- Controle para encerrar chamadas
- Histórico de chamadas realizadas

**⚠️ Requisitos:**

- Serviço Voice/Calls ativado na Infobip
- Consulte [CALLS_TROUBLESHOOTING.md](../docs/CALLS_TROUBLESHOOTING.md) se tiver problemas

### 3. Documentação

Acesso centralizado à documentação:

**Guias de Setup:**

- Instagram Setup Guide
- Meta Credentials Setup
- Production Deployment Guide
- **Calls Setup Guide** 🆕
- **Calls Quick Start** 🆕
- **Calls Troubleshooting** 🆕

**Documentação Técnica:**

- API Documentation
- Meta Request Adapter
- Troubleshooting Guide

**Configuração e Produção:**

- Exemplo de Configuração (.env)
- Rate Limits e Thresholds
- Circuit Breaker e Resiliência

**Comparação de Plataformas:**

- Tabela comparativa WhatsApp vs Instagram vs Messenger
- Limites e recursos de cada plataforma

**Links Úteis:**

- Links diretos para documentação oficial da Meta
- Documentação da Infobip
- Alertas quando limites são atingidos

**Circuit Breaker:**

- Estado atual (CLOSED/OPEN/HALF_OPEN)
- Número de falhas e sucessos
- Tempo desde última abertura

**Estatísticas de Alertas:**

- Total de alertas nas últimas 24h
- Breakdown por severidade (Critical, Error, Warning, Info)
- Breakdown por tipo (API Error, Webhook Failure, etc.)

**Alertas Recentes:**

- Lista de alertas com filtro por severidade
- Detalhes de cada alerta
- Contexto e timestamps

**System Health:**

- Status geral do sistema
- Health checks individuais
- Indicadores visuais de saúde

**Performance Metrics:**

- Tempo médio de resposta
- Taxa de sucesso
- Taxa de erro

**Ações de Administração:**

- Resetar Rate Limits
- Resetar Circuit Breaker
- Limpar Alertas
- Exportar Logs

## Atualização do Backend (api.php)

Para suportar a página de monitoramento, adicione os seguintes endpoints ao `api.php`:

```php
// Rate Limits
case 'get_rate_limits':
    // Retornar uso atual de rate limits
    break;

case 'reset_rate_limits':
    // Resetar contadores de rate limit
    break;

// Circuit Breaker
case 'get_circuit_breaker_status':
    // Retornar estado do circuit breaker
    break;

case 'reset_circuit_breaker':
    // Resetar circuit breaker para CLOSED
    break;

// Alertas
case 'get_alert_stats':
    // Retornar estatísticas de alertas
    break;

case 'get_recent_alerts':
    // Retornar lista de alertas recentes
    break;

case 'clear_alerts':
    // Limpar histórico de alertas
    break;

// System Health
case 'health':
    // Retornar status de saúde do sistema
    break;

case 'get_performance_metrics':
    // Retornar métricas de performance
    break;

// Logs
case 'export_logs':
    // Exportar logs em formato JSON
    break;
```

## Integração com Serviços de Produção

A página de monitoramento se integra com os serviços criados na Task 29:

- **MetaRateLimiter** - Para exibir uso de rate limits
- **MetaCircuitBreaker** - Para exibir estado do circuit breaker
- **MetaAlertManager** - Para exibir alertas e estatísticas

## Auto-Refresh

A página de monitoramento atualiza automaticamente a cada 30 segundos para manter os dados sempre atualizados.

## Responsividade

Todas as páginas são responsivas e funcionam bem em:

- Desktop (1400px+)
- Tablet (768px - 1400px)
- Mobile (< 768px)

## Migração

Para migrar da interface antiga para a nova:

1. **Backup**: O arquivo original foi salvo como `index.html.backup`
2. **Teste**: Acesse `index-tabs.html` para testar a nova interface
3. **Deploy**: Quando estiver satisfeito, você pode:
   - Renomear `index.html` para `index-messages.html`
   - Renomear `index-tabs.html` para `index.html`
   - Ou manter ambos e usar `index-tabs.html` como padrão

## Próximos Passos

1. **Implementar endpoints no api.php** para suportar monitoramento
2. **Integrar com serviços de produção** (MetaRateLimiter, MetaCircuitBreaker, MetaAlertManager)
3. **Adicionar autenticação** se necessário
4. **Configurar alertas** para notificações em tempo real
5. **Adicionar mais métricas** conforme necessário

## Suporte

Para problemas ou dúvidas:

- Consultar documentação em `docs/`
- Verificar logs em `storage/logs/`
- Consultar `docs/TROUBLESHOOTING.md`
