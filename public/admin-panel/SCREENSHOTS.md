# 📸 Screenshots - WhatsApp HSM Admin Panel

## Interface Principal

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  📱 WhatsApp HSM Admin Panel                                                │
│  Gerir templates e mensagens HSM via Infobip                                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────┬────────────────────────────────────────┐
│                                    │                                        │
│  📋 Templates Disponíveis          │  📤 Enviar Mensagem HSM                │
│                                    │                                        │
│  🔄 Atualizar Templates            │  Template Selecionado:                 │
│                                    │  ┌──────────────────────────────────┐ │
│  ┌──────────────────────────────┐ │  │ teste2_mds                       │ │
│  │ teste2_mds         APPROVED  │ │  └──────────────────────────────────┘ │
│  │ pt_PT • MARKETING            │ │                                        │
│  │ Isto é um teste 😜           │ │  Número de Destino:                    │
│  └──────────────────────────────┘ │  ┌──────────────────────────────────┐ │
│                                    │  │ 351961725398                     │ │
│  ┌──────────────────────────────┐ │  └──────────────────────────────────┘ │
│  │ suporte            APPROVED  │ │                                        │
│  │ pt_BR • MARKETING            │ │  Idioma:                               │
│  │ Olá! A nossa conversação...  │ │  ┌──────────────────────────────────┐ │
│  └──────────────────────────────┘ │  │ Português (PT)            ▼      │ │
│                                    │  └──────────────────────────────────┘ │
│  ┌──────────────────────────────┐ │                                        │
│  │ entrega_saiu_codigo          │ │  ┌──────────────────────────────────┐ │
│  │ APPROVED                     │ │  │   📨 Enviar Mensagem             │ │
│  │ pt_PT • AUTHENTICATION       │ │  └──────────────────────────────────┘ │
│  │ *{{1}}* é o seu código...    │ │                                        │
│  └──────────────────────────────┘ │                                        │
│                                    │                                        │
└────────────────────────────────────┴────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  💬 Mensagens Recebidas (Webhooks)                                          │
│                                                                             │
│  🔄 Atualizar Mensagens                                                     │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📱 351961725398                          16/01/2026 19:05:23        │   │
│  │ Sim                                                                 │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ 📱 351966141650                          16/01/2026 19:03:15        │   │
│  │ Button: Sim                                                         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Estado: Carregando Templates

```
┌────────────────────────────────────┐
│                                    │
│  📋 Templates Disponíveis          │
│                                    │
│  🔄 Atualizar Templates            │
│                                    │
│  ┌──────────────────────────────┐ │
│  │                              │ │
│  │    Carregando templates...   │ │
│  │                              │ │
│  └──────────────────────────────┘ │
│                                    │
└────────────────────────────────────┘
```

## Estado: Mensagem Enviada com Sucesso

```
┌────────────────────────────────────────┐
│                                        │
│  📤 Enviar Mensagem HSM                │
│                                        │
│  ┌──────────────────────────────────┐ │
│  │ ✅ Mensagem enviada com sucesso! │ │
│  │ Message ID: 41d54ffb-1241-...    │ │
│  │ Status: PENDING_ENROUTE          │ │
│  └──────────────────────────────────┘ │
│                                        │
│  Template Selecionado:                 │
│  ┌──────────────────────────────────┐ │
│  │                                  │ │
│  └──────────────────────────────────┘ │
│                                        │
└────────────────────────────────────────┘
```

## Estado: Erro ao Enviar

```
┌────────────────────────────────────────┐
│                                        │
│  📤 Enviar Mensagem HSM                │
│                                        │
│  ┌──────────────────────────────────┐ │
│  │ ❌ Selecione um template primeiro│ │
│  └──────────────────────────────────┘ │
│                                        │
│  Template Selecionado:                 │
│  ┌──────────────────────────────────┐ │
│  │                                  │ │
│  └──────────────────────────────────┘ │
│                                        │
└────────────────────────────────────────┘
```

## Estado: Nenhuma Mensagem Recebida

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  💬 Mensagens Recebidas (Webhooks)                                          │
│                                                                             │
│  🔄 Atualizar Mensagens                                                     │
│                                                                             │
│                                                                             │
│                      Nenhuma mensagem recebida ainda                        │
│                                                                             │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Template Selecionado (Destacado)

```
┌────────────────────────────────────┐
│                                    │
│  📋 Templates Disponíveis          │
│                                    │
│  ┌──────────────────────────────┐ │
│  │ teste2_mds         APPROVED  │ │  ← Normal
│  │ pt_PT • MARKETING            │ │
│  │ Isto é um teste 😜           │ │
│  └──────────────────────────────┘ │
│                                    │
│  ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓ │
│  ┃ suporte            APPROVED  ┃ │  ← Selecionado (verde)
│  ┃ pt_BR • MARKETING            ┃ │
│  ┃ Olá! A nossa conversação...  ┃ │
│  ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛ │
│                                    │
│  ┌──────────────────────────────┐ │
│  │ entrega_saiu_codigo          │ │  ← Normal
│  │ APPROVED                     │ │
│  └──────────────────────────────┘ │
│                                    │
└────────────────────────────────────┘
```

## Cores e Estilos

### Paleta de Cores

- **Verde WhatsApp**: #25D366 (header, botões, destaques)
- **Verde Hover**: #20BA5A (hover em botões)
- **Verde Claro**: #e8f5e9 (template selecionado, alertas sucesso)
- **Branco**: #ffffff (painéis, fundo)
- **Cinza Claro**: #f5f5f5 (fundo geral, preview templates)
- **Cinza**: #666 (texto secundário)
- **Preto**: #333 (texto principal)
- **Azul**: #2196F3 (botão refresh)
- **Vermelho**: #c62828 (alertas erro)

### Tipografia

- **Font**: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto
- **Tamanhos**:
  - H1: 24px
  - H2: 18px
  - Body: 14px
  - Small: 12px

### Espaçamento

- **Padding**: 20px (painéis)
- **Gap**: 20px (grid)
- **Border Radius**: 8px (painéis), 4px (inputs)
- **Box Shadow**: 0 2px 4px rgba(0,0,0,0.1)

## Responsividade

### Desktop (> 1400px)

```
┌─────────────────────────────────────────────────────────────────┐
│                         Header                                  │
├──────────────────────────────┬──────────────────────────────────┤
│      Templates (50%)         │      Enviar (50%)                │
├──────────────────────────────┴──────────────────────────────────┤
│                    Mensagens (100%)                             │
└─────────────────────────────────────────────────────────────────┘
```

### Tablet (768px - 1400px)

```
┌─────────────────────────────────────┐
│            Header                   │
├─────────────────────────────────────┤
│      Templates (100%)               │
├─────────────────────────────────────┤
│      Enviar (100%)                  │
├─────────────────────────────────────┤
│      Mensagens (100%)               │
└─────────────────────────────────────┘
```

### Mobile (< 768px)

```
┌───────────────────┐
│     Header        │
├───────────────────┤
│   Templates       │
│    (100%)         │
├───────────────────┤
│    Enviar         │
│    (100%)         │
├───────────────────┤
│   Mensagens       │
│    (100%)         │
└───────────────────┘
```

## Interações

### Hover em Template

```
Normal:     ┌──────────────────┐
            │ Template Name    │
            └──────────────────┘

Hover:      ┏━━━━━━━━━━━━━━━━━━┓  (borda verde, fundo cinza claro)
            ┃ Template Name    ┃
            ┗━━━━━━━━━━━━━━━━━━┛
```

### Botão Estados

```
Normal:     [ 📨 Enviar Mensagem ]  (verde)
Hover:      [ 📨 Enviar Mensagem ]  (verde escuro)
Loading:    [ ⏳ Enviando...     ]  (desabilitado, cinza)
```

### Alertas

```
Sucesso:    ┌────────────────────────────────┐
            │ ✅ Mensagem enviada!           │  (verde claro)
            └────────────────────────────────┘

Erro:       ┌────────────────────────────────┐
            │ ❌ Erro ao enviar              │  (vermelho claro)
            └────────────────────────────────┘

Info:       ┌────────────────────────────────┐
            │ ℹ️  Configuração necessária    │  (azul claro)
            └────────────────────────────────┘
```

## Animações

- **Transições**: 0.2s ease (hover, seleção)
- **Loading**: Fade in/out
- **Alertas**: Slide down from top
- **Scroll**: Smooth scrolling em listas

## Acessibilidade

- ✅ Contraste adequado (WCAG AA)
- ✅ Foco visível em elementos interativos
- ✅ Labels descritivos
- ✅ Estados visuais claros
- ✅ Ícones com texto descritivo
