# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

## [2.0.0] - 2024-03-09

### 🆕 Adicionado - Chamadas WhatsApp

#### Funcionalidades

- **Interface Web para Chamadas** (`admin-panel/calls.html`)
  - Formulário para iniciar chamadas
  - Monitoramento de status em tempo real
  - Timer de duração da chamada
  - Controle para encerrar chamadas
  - Histórico de chamadas realizadas
  - Avisos sobre requisitos do serviço Voice

#### Backend

- **Serviço de Chamadas** (`src/Services/InfobipCallService.php`)
  - Iniciar chamadas via API Infobip
  - Verificar status de chamadas
  - Encerrar chamadas ativas
  - Listar histórico de chamadas
  - Formatação automática de números

- **Controller HTTP** (`src/Http/Controllers/CallController.php`)
  - Endpoints RESTful para chamadas
  - Validação de parâmetros
  - Tratamento de erros

- **API Endpoints** (em `admin-panel/api.php`)
  - `POST /api.php?action=initiate_call` - Iniciar chamada
  - `GET /api.php?action=get_call_status` - Status da chamada
  - `POST /api.php?action=hangup_call` - Encerrar chamada
  - `GET /api.php?action=get_call_history` - Histórico

#### Documentação

- **[CALLS_SETUP.md](docs/CALLS_SETUP.md)** - Guia completo de configuração
- **[CALLS_QUICK_START.md](docs/CALLS_QUICK_START.md)** - Guia rápido em 3 passos
- **[CALLS_TROUBLESHOOTING.md](docs/CALLS_TROUBLESHOOTING.md)** - Solução de problemas
- **[CALLS_FEATURE_SUMMARY.md](docs/CALLS_FEATURE_SUMMARY.md)** - Resumo da funcionalidade
- **[INDEX.md](docs/INDEX.md)** - Índice completo da documentação

#### Interface

- Adicionada aba "📞 Chamadas" no menu principal
- Links para documentação de chamadas na seção de documentação
- Avisos sobre requisitos do serviço Voice
- Mensagens de erro melhoradas

#### Atualizações

- **README.md** - Adicionada funcionalidade de chamadas
- **admin-panel/README_TABS.md** - Documentação atualizada
- **admin-panel/index-tabs.html** - Nova aba de chamadas

### ⚠️ Importante

- Chamadas requerem serviço **Voice/Calls** ativado na Infobip
- Erro "Unauthorized access" indica que o serviço não está ativado
- Consulte [CALLS_TROUBLESHOOTING.md](docs/CALLS_TROUBLESHOOTING.md) para mais informações

### 🔧 Melhorias

- Mensagens de erro mais descritivas
- Logs detalhados para debug
- Validação de formato de números
- Tratamento de erros robusto

---

## [1.5.0] - 2024-03-06

### Adicionado

- Suporte a RCS (Rich Communication Services)
- Interface web para mensagens RCS
- Documentação de RCS

### Melhorado

- Performance do painel administrativo
- Tratamento de erros da API Meta

---

## [1.4.0] - 2024-03-01

### Adicionado

- Suporte a Instagram Messaging
- Suporte a Facebook Messenger
- Detecção automática de plataforma (Instagram vs Messenger)
- Documentação completa da integração Meta

### Melhorado

- Interface do painel administrativo
- Sistema de tabs para organização

---

## [1.3.0] - 2024-02-15

### Adicionado

- Templates HSM para WhatsApp
- Sincronização automática de templates
- Mensagens interativas (botões e listas)

### Melhorado

- Validação de mensagens
- Tratamento de erros

---

## [1.2.0] - 2024-02-01

### Adicionado

- Suporte a múltiplos providers (Infobip, Twilio)
- Webhooks para delivery reports
- Logs estruturados

---

## [1.1.0] - 2024-01-15

### Adicionado

- Painel administrativo web
- Interface para envio de mensagens
- Visualização de histórico

---

## [1.0.0] - 2024-01-01

### Adicionado

- Versão inicial
- Suporte básico a WhatsApp via Infobip
- API REST para envio de mensagens
- Documentação básica

---

## Tipos de Mudanças

- **Adicionado** - Para novas funcionalidades
- **Alterado** - Para mudanças em funcionalidades existentes
- **Descontinuado** - Para funcionalidades que serão removidas
- **Removido** - Para funcionalidades removidas
- **Corrigido** - Para correções de bugs
- **Segurança** - Para correções de vulnerabilidades

## Links

- [Documentação Completa](docs/INDEX.md)
- [Guia de Contribuição](CONTRIBUTING.md)
- [Código de Conduta](CODE_OF_CONDUCT.md)
