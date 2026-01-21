# Atualizações: Meta Messaging Integration (Instagram + Facebook Messenger)

## Mudanças Realizadas

### 1. Renomeação da Spec

- ✅ Diretório renomeado: `instagram-messaging-integration` → `meta-messaging-integration`
- ✅ Escopo expandido: Instagram → Instagram + Facebook Messenger

### 2. Requirements.md - Atualizações Principais

#### Glossário Atualizado

- ✅ Adicionado **PSID** (Page-Scoped ID) para Messenger
- ✅ Adicionado **Button_Template** (específico Messenger)
- ✅ Renomeado provider: Instagram_Provider → Meta_Provider
- ✅ Atualizado para **Messenger_Platform** (plataforma unificada)

#### Requisitos Atualizados

**Requirement 1: Configuração e Autenticação**

- ✅ Suporte para múltiplas Pages (Instagram e Messenger)
- ✅ Detecção automática de plataforma (IGSID vs PSID)

**Requirement 2: Envio de Mensagens de Texto**

- ✅ Suporte para Instagram e Messenger
- ✅ Identificação automática de plataforma

**Requirement 3: Envio de Mídia**

- ✅ Limites diferenciados por plataforma:
  - Instagram: até 10 imagens, 8MB por imagem
  - Messenger: 1 imagem padrão ou carousel, 25MB
- ✅ Detecção automática de limites

**Requirement 4: Mensagens Interativas**

- ✅ Quick Replies para ambas plataformas
- ✅ Generic Template para ambas
- ✅ **Button Template** (específico Messenger)
- ✅ Botões de URL, postback e call (Messenger)

**Requirement 6: Webhooks**

- ✅ Suporte para webhooks de Instagram e Messenger
- ✅ Extração de IGSID ou PSID
- ✅ Identificação automática de plataforma
- ✅ Metadata da plataforma incluído

**Requirement 13: Admin Panel**

- ✅ Dropdown com 3 opções: WhatsApp/Instagram/Messenger
- ✅ Campos específicos por plataforma (IGSID vs PSID)
- ✅ Limites de imagens por plataforma
- ✅ Suporte para Button Template (Messenger)

**Requirement 14: Logging**

- ✅ Métricas diferenciadas por plataforma

**Requirement 15: Documentação**

- ✅ Guia de configuração Messenger
- ✅ Tabela comparativa: WhatsApp vs Instagram vs Messenger

### 3. Design.md - Atualizações Principais

#### Arquitetura

- ✅ Provider unificado: **MetaProvider** (suporta Instagram + Messenger)
- ✅ Diagrama atualizado com 3 interfaces no Admin Panel
- ✅ Estrutura de diretórios: `src/Providers/Meta/`

#### Componentes Novos

- **MetaPlatformDetector**: Detecta Instagram vs Messenger
  - Por estrutura do webhook
  - Por formato do ID
  - Retorna limites específicos por plataforma

#### Nota Importante

Instagram e Messenger usam a **mesma Messenger Platform API**, compartilhando:

- Endpoints
- Autenticação
- Estrutura de webhooks
- Formato de mensagens

Portanto, são implementados como **um único provider** que suporta ambas as plataformas.

---

## Mudanças Pendentes

### Tasks.md - Precisa Atualizar

O arquivo `tasks.md` ainda precisa ser atualizado para refletir:

1. **Renomear todas as referências**:

   - Instagram → Meta
   - InstagramProvider → MetaProvider
   - IGSID → IGSID/PSID

2. **Adicionar tarefas específicas Messenger**:

   - Implementar Button Template
   - Implementar detecção de plataforma
   - Suporte para PSID
   - Testes específicos Messenger

3. **Atualizar estimativas**:

   - Adicionar tempo para features Messenger
   - Atualizar complexidade

4. **Atualizar Admin Panel tasks**:
   - 3 interfaces (WhatsApp/Instagram/Messenger)
   - Campos específicos por plataforma

---

## Principais Diferenças: WhatsApp vs Instagram vs Messenger

| Aspecto              | WhatsApp             | Instagram           | Messenger           |
| -------------------- | -------------------- | ------------------- | ------------------- |
| **Templates**        | ✅ Obrigatório (HSM) | ❌ Não suportado    | ❌ Não suportado    |
| **Autenticação**     | API Key              | Page Access Token   | Page Access Token   |
| **Identificador**    | Telefone             | IGSID               | PSID                |
| **API Base**         | api.infobip.com      | graph.facebook.com  | graph.facebook.com  |
| **Imagens/msg**      | 1                    | Até 10              | 1 (ou carousel)     |
| **Webhooks**         | HMAC SHA-256         | X-Hub-Signature-256 | X-Hub-Signature-256 |
| **Status Query**     | Endpoint direto      | Via webhooks        | Via webhooks        |
| **Janela**           | 24h                  | 24h                 | 24h                 |
| **Button Template**  | ❌                   | ❌                  | ✅                  |
| **Quick Replies**    | ❌                   | ✅ (máx 13)         | ✅ (máx 13)         |
| **Generic Template** | ❌                   | ✅                  | ✅                  |

---

## Benefícios da Unificação

### 1. Código Compartilhado

- Mesma API (Messenger Platform)
- Mesmos endpoints
- Mesma autenticação
- Mesmos webhooks

### 2. Manutenção Simplificada

- Um único provider para duas plataformas
- Menos código duplicado
- Testes compartilhados

### 3. Detecção Automática

- Sistema detecta automaticamente Instagram vs Messenger
- Aplica limites corretos automaticamente
- Usuário não precisa especificar plataforma

### 4. Escalabilidade

- Fácil adicionar outras plataformas Meta no futuro
- Arquitetura preparada para expansão

---

## Próximos Passos

### 1. Atualizar tasks.md ⏳

- Renomear todas as referências
- Adicionar tarefas Messenger
- Atualizar estimativas
- Adicionar testes específicos

### 2. Atualizar README.md ⏳

- Atualizar título e descrição
- Incluir Messenger em todos os lugares
- Atualizar tabela comparativa
- Atualizar pré-requisitos

### 3. Atualizar Documentos Raiz ⏳

- INSTAGRAM_INTEGRATION_PLAN.md → META_MESSAGING_PLAN.md
- INSTAGRAM_INTEGRATION_SUMMARY.md → META_MESSAGING_SUMMARY.md
- INSTAGRAM_SPEC_CREATED.md → META_SPEC_CREATED.md
- INSTAGRAM_FILES_SUMMARY.txt → META_FILES_SUMMARY.txt

### 4. Criar Documentação Adicional ⏳

- Guia de setup Messenger
- Diferenças Instagram vs Messenger
- Exemplos de código para ambas plataformas

---

## Estrutura Final

```
.kiro/specs/meta-messaging-integration/
├── README.md              - Visão geral (Instagram + Messenger)
├── requirements.md        - 15 requisitos (✅ ATUALIZADO)
├── design.md              - Design técnico (✅ PARCIALMENTE ATUALIZADO)
└── tasks.md               - 32+ tarefas (⏳ PRECISA ATUALIZAR)
```

---

## Estimativa de Esforço Atualizada

Com a adição do Messenger, a estimativa pode aumentar ligeiramente:

| Fase             | Duração Original | Duração com Messenger | Diferença     |
| ---------------- | ---------------- | --------------------- | ------------- |
| 1. Configuração  | 2-3 dias         | 2-3 dias              | -             |
| 2. Provider Core | 4-5 dias         | 5-6 dias              | +1 dia        |
| 3. Webhooks      | 3-4 dias         | 3-4 dias              | -             |
| 4. Status        | 2 dias           | 2 dias                | -             |
| 5. Integração    | 2 dias           | 2-3 dias              | +1 dia        |
| 6. Admin Panel   | 3 dias           | 4 dias                | +1 dia        |
| 7. Testes Unit   | 3 dias           | 4 dias                | +1 dia        |
| 8. Testes Int    | 2 dias           | 2-3 dias              | +1 dia        |
| 9. Documentação  | 2 dias           | 2-3 dias              | +1 dia        |
| 10. Deploy       | 1-2 dias         | 1-2 dias              | -             |
| **TOTAL**        | **24-30 dias**   | **27-34 dias**        | **+3-4 dias** |

**Nota**: O aumento é mínimo porque Instagram e Messenger compartilham a mesma API. A maior parte do código é reutilizada.

---

**Criado em**: 2025-01-16  
**Versão**: 1.0  
**Status**: Parcialmente atualizado - tasks.md pendente
