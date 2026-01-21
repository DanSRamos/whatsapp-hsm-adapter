# Admin Panel com Sistema de Tabs

## Visão Geral

O Admin Panel foi atualizado com um sistema de tabs que organiza a interface em 3 páginas principais:

1. **💬 Mensagens** - Interface original para envio e visualização de mensagens
2. **📚 Documentação** - Acesso centralizado a toda documentação do sistema
3. **📊 Alertas & Monitoramento** - Dashboard de monitoramento e alertas em tempo real

## Estrutura de Arquivos

```
admin-panel/
├── index-tabs.html          # Nova página principal com tabs
├── index.html               # Página de mensagens (original)
├── documentation.html       # Página de documentação
├── monitoring.html          # Página de alertas e monitoramento
├── styles.css               # Estilos compartilhados
├── api.php                  # Backend API
└── README_TABS.md          # Este arquivo
```

## Como Usar

### Opção 1: Usar a Nova Interface com Tabs

Acesse: `http://localhost:8000/admin-panel/index-tabs.html`

Esta é a interface recomendada que integra todas as funcionalidades em uma única página com navegação por tabs.

### Opção 2: Usar Páginas Individuais

Você também pode acessar cada página diretamente:

- **Mensagens**: `http://localhost:8000/admin-panel/index.html`
- **Documentação**: `http://localhost:8000/admin-panel/documentation.html`
- **Monitoramento**: `http://localhost:8000/admin-panel/monitoring.html`

## Funcionalidades por Página

### 1. Mensagens (index.html)

Interface original mantida intacta:

- Envio de mensagens via WhatsApp, Instagram e Messenger
- Seleção de templates HSM (WhatsApp)
- Visualização de mensagens recebidas
- Filtros por provider

### 2. Documentação (documentation.html)

Nova página com acesso centralizado à documentação:

**Guias de Setup:**

- Instagram Setup Guide
- Meta Credentials Setup
- Production Deployment Guide

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

### 3. Alertas & Monitoramento (monitoring.html)

Nova página de monitoramento em tempo real:

**Rate Limit Status:**

- Uso horário e diário
- Barras de progresso visuais
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
