# 📊 DIAGRAMA: Como Funciona o App Mode

## 🎯 FLUXO DE MENSAGENS

### Cenário 1: App em Development Mode (ATUAL)

```
┌─────────────────────────────────────────────────────────────┐
│                    APP EM DEVELOPMENT MODE                   │
└─────────────────────────────────────────────────────────────┘

Utilizador Normal                    Meta Platform
     │                                     │
     │  1. Envia mensagem                  │
     │  "Olá CoreMedia"                    │
     ├────────────────────────────────────>│
     │                                     │
     │                                     │  2. Meta verifica:
     │                                     │     "Este utilizador
     │                                     │      tem privilégios?"
     │                                     │
     │                                     │  3. Resposta: NÃO
     │                                     │     (não é admin/dev/tester)
     │                                     │
     │                                     │  4. Meta NÃO envia webhook
     │                                     │     ❌ Bloqueado!
     │                                     │
     │                                     ▼
     │                              Teu Webhook
     │                              (não recebe nada)
     │
     ▼
Mensagem aparece no Messenger
(mas webhook não é chamado)
```

---

### Cenário 2: Utilizador com Privilégios (SOLUÇÃO)

```
┌─────────────────────────────────────────────────────────────┐
│                    APP EM DEVELOPMENT MODE                   │
└─────────────────────────────────────────────────────────────┘

Utilizador Autorizado                Meta Platform
(Admin/Dev/Tester)                         │
     │                                     │
     │  1. Envia mensagem                  │
     │  "Olá CoreMedia"                    │
     ├────────────────────────────────────>│
     │                                     │
     │                                     │  2. Meta verifica:
     │                                     │     "Este utilizador
     │                                     │      tem privilégios?"
     │                                     │
     │                                     │  3. Resposta: SIM ✅
     │                                     │     (é admin/dev/tester)
     │                                     │
     │                                     │  4. Meta envia webhook
     │                                     │     POST /webhooks/meta
     │                                     │
     │                                     ▼
     │                              Teu Webhook
     │                              ✅ Recebe payload!
     │                              {
     │                                "object": "page",
     │                                "entry": [...]
     │                              }
     │
     ▼
Mensagem aparece no Messenger
E webhook é chamado!
```

---

### Cenário 3: App em Live Mode (PRODUÇÃO)

```
┌─────────────────────────────────────────────────────────────┐
│                      APP EM LIVE MODE                        │
└─────────────────────────────────────────────────────────────┘

QUALQUER Utilizador                  Meta Platform
     │                                     │
     │  1. Envia mensagem                  │
     │  "Olá CoreMedia"                    │
     ├────────────────────────────────────>│
     │                                     │
     │                                     │  2. Meta NÃO verifica
     │                                     │     privilégios
     │                                     │     (app é público)
     │                                     │
     │                                     │  3. Meta envia webhook
     │                                     │     POST /webhooks/meta
     │                                     │
     │                                     ▼
     │                              Teu Webhook
     │                              ✅ Recebe payload!
     │                              {
     │                                "object": "page",
     │                                "entry": [...]
     │                              }
     │
     ▼
Mensagem aparece no Messenger
E webhook é chamado!
```

---

## 🔍 COMPARAÇÃO VISUAL

### Development Mode

```
┌──────────────────────────────────────────┐
│         APP EM DEVELOPMENT               │
├──────────────────────────────────────────┤
│                                          │
│  ✅ Administrator                        │
│     └─> Pode enviar mensagens            │
│                                          │
│  ✅ Developer                            │
│     └─> Pode enviar mensagens            │
│                                          │
│  ✅ Tester                               │
│     └─> Pode enviar mensagens            │
│                                          │
│  ❌ Utilizador Normal                    │
│     └─> NÃO pode enviar mensagens        │
│         (webhook não é chamado)          │
│                                          │
└──────────────────────────────────────────┘
```

### Live Mode

```
┌──────────────────────────────────────────┐
│           APP EM LIVE                    │
├──────────────────────────────────────────┤
│                                          │
│  ✅ QUALQUER PESSOA                      │
│     └─> Pode enviar mensagens            │
│         (webhook é chamado)              │
│                                          │
│  ✅ Utilizador Normal                    │
│     └─> Pode enviar mensagens            │
│                                          │
│  ✅ Utilizador Anónimo                   │
│     └─> Pode enviar mensagens            │
│                                          │
└──────────────────────────────────────────┘
```

---

## 🎯 FLUXO DE DECISÃO

```
                    Mensagem Enviada
                          │
                          ▼
                  ┌───────────────┐
                  │  App Mode?    │
                  └───────┬───────┘
                          │
            ┌─────────────┴─────────────┐
            │                           │
            ▼                           ▼
    ┌──────────────┐          ┌──────────────┐
    │ Development  │          │     Live     │
    └──────┬───────┘          └──────┬───────┘
           │                         │
           ▼                         ▼
    ┌──────────────┐          ┌──────────────┐
    │ Verificar    │          │ Enviar       │
    │ Privilégios  │          │ Webhook      │
    └──────┬───────┘          │ Sempre       │
           │                  └──────────────┘
    ┌──────┴──────┐
    │             │
    ▼             ▼
┌────────┐   ┌────────┐
│ Admin/ │   │ Normal │
│ Dev/   │   │ User   │
│ Tester │   └────┬───┘
└───┬────┘        │
    │             ▼
    │      ┌──────────────┐
    │      │ Bloquear     │
    │      │ Webhook      │
    │      └──────────────┘
    │
    ▼
┌──────────────┐
│ Enviar       │
│ Webhook      │
└──────────────┘
```

---

## 📊 TABELA DE PERMISSÕES

| Tipo de Conta          | Development Mode     | Live Mode          |
| ---------------------- | -------------------- | ------------------ |
| **Administrator**      | ✅ Webhook enviado   | ✅ Webhook enviado |
| **Developer**          | ✅ Webhook enviado   | ✅ Webhook enviado |
| **Tester**             | ✅ Webhook enviado   | ✅ Webhook enviado |
| **Utilizador Normal**  | ❌ Webhook bloqueado | ✅ Webhook enviado |
| **Utilizador Anónimo** | ❌ Webhook bloqueado | ✅ Webhook enviado |

---

## 🔍 COMO IDENTIFICAR O PROBLEMA

### Sintomas:

```
✅ Webhook verificado (GET request)
✅ Eventos subscritos
✅ Page conectada
✅ Mensagem enviada no Messenger
❌ Webhook NÃO recebe POST request
```

### Diagnóstico:

```
1. Verifica App Mode
   └─> Se "Development" → Problema de privilégios!

2. Verifica Role da conta
   └─> Se não é Admin/Dev/Tester → Adiciona como Tester!
```

---

## 🚀 SOLUÇÃO RÁPIDA

```
┌─────────────────────────────────────────┐
│  1. Vai para Roles → Test Users         │
│  2. Clica em "Add Test Users"           │
│  3. Adiciona a conta                    │
│  4. Seleciona permissões                │
│  5. Clica em "Add"                      │
│  6. Testa novamente                     │
└─────────────────────────────────────────┘
                    │
                    ▼
            ✅ Webhook funciona!
```

---

## 💡 RESUMO VISUAL

### O QUE ESTÁ A ACONTECER:

```
Tua Conta (sem privilégios)
     │
     │ Envia mensagem
     ▼
Meta Platform
     │
     │ Verifica: "Esta conta pode usar o app?"
     │ Resposta: NÃO (app em Development)
     │
     │ ❌ Bloqueia webhook
     ▼
Teu Webhook (não recebe nada)
```

### O QUE DEVE ACONTECER:

```
Tua Conta (com privilégios)
     │
     │ Envia mensagem
     ▼
Meta Platform
     │
     │ Verifica: "Esta conta pode usar o app?"
     │ Resposta: SIM (é Tester)
     │
     │ ✅ Envia webhook
     ▼
Teu Webhook (recebe payload)
```

---

**Última atualização**: 20 Janeiro 2026, 13:15 GMT
