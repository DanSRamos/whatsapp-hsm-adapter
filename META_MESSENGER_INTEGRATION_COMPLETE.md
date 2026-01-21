# ✅ Spec Atualizada: Meta Messaging Integration (Instagram + Facebook Messenger)

## 🎯 Mudanças Realizadas

### 1. Renomeação e Expansão de Escopo

- ✅ Diretório renomeado: `instagram-messaging-integration` → `meta-messaging-integration`
- ✅ Escopo expandido: **Instagram** → **Instagram + Facebook Messenger**
- ✅ Provider unificado: **MetaProvider** (suporta ambas plataformas)

### 2. Arquivos Atualizados

#### ✅ requirements.md (247 linhas)

**Mudanças principais**:

- Glossário atualizado com PSID, Button_Template, Messenger_Platform
- Requirement 1: Suporte para múltiplas Pages, detecção automática IGSID/PSID
- Requirement 2: Envio de texto para Instagram e Messenger
- Requirement 3: Limites diferenciados por plataforma (10 imagens IG, 1 Messenger)
- Requirement 4: Button Template (específico Messenger), Quick Replies (ambos)
- Requirement 6: Webhooks para ambas plataformas, detecção automática
- Requirement 13: Admin Panel com 3 opções (WhatsApp/Instagram/Messenger)
- Requirement 14: Métricas diferenciadas por plataforma
- Requirement 15: Documentação para Instagram e Messenger

#### ✅ design.md (720 linhas)

**Mudanças principais**:

- Arquitetura atualizada com MetaProvider unificado
- Novo componente: **MetaPlatformDetector** (detecta Instagram vs Messenger)
- Estrutura: `src/Providers/Meta/` (em vez de Instagram)
- Suporte para Button Template (Messenger)
- Detecção automática de plataforma por webhook e ID
- Limites específicos por plataforma aplicados automaticamente

#### ✅ tasks.md (511 linhas)

**Mudanças principais**:

- Título atualizado para "Meta Messaging Integration (Instagram + Facebook Messenger)"
- Nota sobre API unificada da Meta
- Tarefa 1: Setup do MetaProvider (não InstagramProvider)
- Tarefa 3.1: Implementar MetaPlatformDetector (NOVA)
- Tarefa 7.3: Implementar Button Template Messenger (NOVA)
- Tarefa 10.1: Processar mensagens de ambas plataformas
- Tarefa 18-20: Admin Panel com 3 providers
- Tarefa 22.5: Testes de MetaPlatformDetector (NOVA)
- Tarefa 24.4: Testes multi-plataforma (NOVA)
- Estimativa atualizada: 27-34 dias (vs 24-30 original)

---

## 📊 Comparativo: WhatsApp vs Instagram vs Messenger

| Aspecto              | WhatsApp        | Instagram           | Messenger           |
| -------------------- | --------------- | ------------------- | ------------------- |
| **Provider**         | Infobip         | Meta                | Meta                |
| **Templates**        | ✅ HSM          | ❌                  | ❌                  |
| **Autenticação**     | API Key         | Page Access Token   | Page Access Token   |
| **Identificador**    | Telefone        | IGSID               | PSID                |
| **API Base**         | api.infobip.com | graph.facebook.com  | graph.facebook.com  |
| **Imagens/msg**      | 1               | Até 10              | 1 (ou carousel)     |
| **Webhooks**         | HMAC SHA-256    | X-Hub-Signature-256 | X-Hub-Signature-256 |
| **Status Query**     | Endpoint direto | Via webhooks        | Via webhooks        |
| **Janela**           | 24h             | 24h                 | 24h                 |
| **Button Template**  | ❌              | ❌                  | ✅                  |
| **Quick Replies**    | ❌              | ✅ (máx 13)         | ✅ (máx 13)         |
| **Generic Template** | ❌              | ✅                  | ✅                  |

---

## 🏗️ Arquitetura Unificada

### Por que um único provider?

Instagram e Messenger usam a **mesma Messenger Platform API**:

- ✅ Mesmos endpoints
- ✅ Mesma autenticação (Page Access Token)
- ✅ Mesma estrutura de webhooks
- ✅ Mesmo formato de mensagens

### Componentes Principais

```
src/Providers/Meta/
├── MetaProvider.php                # Provider unificado
├── MetaWebhookHandler.php          # Webhooks (IG + Messenger)
├── MetaMessageFormatter.php        # Formatação de mensagens
├── MetaPlatformDetector.php        # 🆕 Detecta IG vs Messenger
└── Models/
    ├── MetaRecipient.php           # IGSID ou PSID
    ├── MetaAttachment.php          # Anexos
    └── MetaQuickReply.php          # Quick replies
```

### Detecção Automática de Plataforma

O **MetaPlatformDetector** identifica automaticamente:

1. **Por estrutura de webhook**: `entry[].messaging` (Messenger) vs `entry[].instagram` (Instagram)
2. **Por formato de ID**: IGSID vs PSID
3. **Aplica limites corretos**: 10 imagens (IG) vs 1 imagem (Messenger)

---

## 📝 Novas Tarefas Adicionadas

### Fase 1

- **3.1**: Implementar MetaPlatformDetector

### Fase 2

- **7.3**: Implementar Button Template (Messenger)

### Fase 6

- **18.1**: Admin Panel com 3 providers (WhatsApp/Instagram/Messenger)
- **19.1**: Campos específicos (IGSID vs PSID)

### Fase 7

- **22.5**: Testes de MetaPlatformDetector
- **23**: Testes de Button Template

### Fase 8

- **24.4**: Testes multi-plataforma

---

## 📈 Estimativa de Esforço Atualizada

| Fase                    | Original       | Com Messenger  | Diferença     |
| ----------------------- | -------------- | -------------- | ------------- |
| 1-2: Setup + Core       | 6-8 dias       | 7-9 dias       | +1 dia        |
| 3-4: Webhooks + Status  | 5-6 dias       | 5-6 dias       | -             |
| 5-6: Integração + Admin | 5 dias         | 6 dias         | +1 dia        |
| 7-10: Testes + Docs     | 8-11 dias      | 9-13 dias      | +1-2 dias     |
| **TOTAL**               | **24-30 dias** | **27-34 dias** | **+3-4 dias** |

**Aumento mínimo**: Apenas 3-4 dias extras porque a API é compartilhada!

---

## ✨ Benefícios da Unificação

### 1. Código Compartilhado

- Um único provider para duas plataformas
- Menos código duplicado
- Manutenção simplificada

### 2. Detecção Automática

- Sistema detecta Instagram vs Messenger automaticamente
- Aplica limites corretos sem intervenção manual
- Usuário não precisa especificar plataforma

### 3. Escalabilidade

- Fácil adicionar outras plataformas Meta no futuro
- Arquitetura preparada para expansão
- Testes compartilhados

### 4. Admin Panel Unificado

- Gerenciar WhatsApp, Instagram e Messenger em um lugar
- Interface consistente
- Filtros por plataforma

---

## 📚 Estrutura Final da Spec

```
.kiro/specs/meta-messaging-integration/
├── README.md              - Visão geral (⏳ precisa atualizar)
├── requirements.md        - 15 requisitos (✅ ATUALIZADO)
├── design.md              - Design técnico (✅ ATUALIZADO)
└── tasks.md               - 32+ tarefas (✅ ATUALIZADO)
```

---

## 🎯 Próximos Passos

### 1. Atualizar README.md ⏳

- Atualizar título e descrição
- Incluir Messenger em todos os lugares
- Atualizar tabela comparativa
- Atualizar pré-requisitos

### 2. Atualizar Documentos Raiz ⏳

- Renomear INSTAGRAM*\* para META*\*
- Atualizar conteúdo para incluir Messenger
- Criar novos resumos

### 3. Obter Credenciais

- [ ] Conta Meta for Developers
- [ ] Facebook Page
- [ ] Instagram Professional Account
- [ ] Facebook Messenger configurado
- [ ] Permissões: `pages_messaging`, `instagram_manage_messages`

### 4. Iniciar Implementação

- [ ] Criar branch `feature/meta-messaging-integration`
- [ ] Começar pela Fase 1: Configuração
- [ ] Implementar MetaPlatformDetector
- [ ] Seguir tasks.md sequencialmente

---

## 🔑 Principais Features Adicionadas

### Instagram

- ✅ Envio de texto
- ✅ Envio de mídia (até 10 imagens)
- ✅ Quick Replies (máx 13)
- ✅ Generic Template
- ✅ Webhooks
- ✅ Delivery reports

### Facebook Messenger (NOVO)

- ✅ Envio de texto
- ✅ Envio de mídia (1 imagem ou carousel)
- ✅ Quick Replies (máx 13)
- ✅ Generic Template
- ✅ **Button Template** (específico)
- ✅ Botões de URL, postback, call
- ✅ Webhooks
- ✅ Delivery reports

### Compartilhado

- ✅ Mesma API (Messenger Platform)
- ✅ Mesma autenticação
- ✅ Detecção automática de plataforma
- ✅ Conversão de templates HSM
- ✅ Janela de 24 horas

---

## 📖 Documentação de Referência

### Arquivos Atualizados

1. `.kiro/specs/meta-messaging-integration/requirements.md` ✅
2. `.kiro/specs/meta-messaging-integration/design.md` ✅
3. `.kiro/specs/meta-messaging-integration/tasks.md` ✅
4. `META_MESSAGING_UPDATES.md` ✅
5. `META_MESSENGER_INTEGRATION_COMPLETE.md` ✅ (este arquivo)

### Links Úteis

- [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram/)
- [Facebook Messenger API](https://developers.facebook.com/docs/messenger-platform/)
- [Send Messages](https://developers.facebook.com/docs/messenger-platform/send-messages/)
- [Webhooks](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Button Template](https://developers.facebook.com/docs/messenger-platform/send-messages/template/button)

---

## 🚀 Pronto para Começar!

A spec foi completamente atualizada para incluir suporte ao Facebook Messenger. Você agora tem:

✅ **15 requisitos** cobrindo Instagram e Messenger  
✅ **Design técnico** com MetaProvider unificado  
✅ **32+ tarefas** com estimativa de 27-34 dias  
✅ **Detecção automática** de plataforma  
✅ **Código compartilhado** entre Instagram e Messenger

**Próximo passo**: Revisar a spec e obter credenciais Meta!

---

**Criado em**: 2025-01-16  
**Versão**: 2.0  
**Status**: ✅ Completo - Pronto para implementação
