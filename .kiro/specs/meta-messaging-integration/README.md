# Instagram Messaging Integration - Spec

## Visão Geral

Esta spec define a integração completa do Instagram Messaging API ao WhatsApp HSM Adapter existente.

## Estrutura da Spec

### 📋 [requirements.md](./requirements.md)

Define os 15 requisitos funcionais para a integração, incluindo:

- Configuração e autenticação
- Envio de mensagens (texto, mídia, interativas)
- Recebimento via webhooks
- Gestão de status
- Integração com sistema existente
- Admin panel multi-provider

### 🏗️ [design.md](./design.md)

Descreve o design técnico detalhado:

- Arquitetura de componentes
- Implementação do InstagramProvider
- Modelos de dados
- 15 propriedades de corretude
- Estratégia de testes (unit + property-based + integration)
- Tratamento de erros específicos

### ✅ [tasks.md](./tasks.md)

Lista de 32 tarefas organizadas em 10 fases:

1. Configuração e Estrutura Base (3 tarefas)
2. Provider Core (5 tarefas)
3. Webhooks (4 tarefas)
4. Status e Consultas (2 tarefas)
5. Integração (3 tarefas)
6. Admin Panel (3 tarefas)
7. Testes Unitários (3 tarefas)
8. Testes de Integração (2 tarefas)
9. Documentação (3 tarefas)
10. Deploy e Monitoramento (3 tarefas)
11. Checkpoint Final (1 tarefa)

## Estimativa de Esforço

**Total**: 24-30 dias de desenvolvimento

**Sprints Sugeridos**:

- Sprint 1 (1 semana): Fases 1-2 - Setup + Provider Core
- Sprint 2 (1 semana): Fases 3-4 - Webhooks + Status
- Sprint 3 (1 semana): Fases 5-6 - Integração + Admin Panel
- Sprint 4 (1 semana): Fases 7-10 - Testes + Docs + Deploy

## Pré-requisitos

Antes de iniciar a implementação:

- [ ] Conta Meta for Developers criada
- [ ] App Meta criado no App Dashboard
- [ ] Facebook Page criada e configurada
- [ ] Instagram Professional Account criado
- [ ] Instagram conectado à Facebook Page
- [ ] Permissão `instagram_manage_messages` aprovada
- [ ] Page Access Token gerado
- [ ] App Secret obtido
- [ ] Webhook URL pública disponível

## Principais Diferenças: WhatsApp vs Instagram

| Aspecto           | WhatsApp          | Instagram                  |
| ----------------- | ----------------- | -------------------------- |
| **Templates**     | Obrigatório (HSM) | Não suportado              |
| **Autenticação**  | API Key           | Page Access Token          |
| **Identificador** | Telefone          | IGSID                      |
| **API Base**      | Infobip           | Meta Graph API             |
| **Imagens/msg**   | 1                 | Até 10                     |
| **Webhooks**      | HMAC SHA-256      | X-Hub-Signature-256        |
| **Status Query**  | Endpoint direto   | Via webhooks + repositório |

## Arquitetura

```
src/Providers/Instagram/
├── InstagramProvider.php              # Provider principal
├── InstagramWebhookHandler.php        # Processamento de webhooks
├── InstagramMessageFormatter.php      # Formatação de mensagens
└── Models/
    ├── InstagramRecipient.php         # Modelo de destinatário (IGSID)
    ├── InstagramAttachment.php        # Modelo de anexo
    └── InstagramQuickReply.php        # Modelo de quick reply
```

## Como Usar Esta Spec

### Para Desenvolvedores

1. **Leia os requirements** para entender o que precisa ser implementado
2. **Revise o design** para entender como implementar
3. **Siga as tasks** na ordem definida
4. **Execute os testes** após cada fase
5. **Atualize a documentação** conforme implementa

### Para Product Owners

1. **Revise os requirements** para validar se atendem às necessidades
2. **Verifique as estimativas** de esforço
3. **Priorize as tarefas** se necessário
4. **Acompanhe o progresso** via tasks.md

### Para QA

1. **Use os requirements** como base para casos de teste
2. **Implemente property-based tests** conforme design
3. **Valide as 15 propriedades de corretude**
4. **Teste os cenários de erro** documentados

## Documentação Adicional

- [INSTAGRAM_INTEGRATION_PLAN.md](../../../INSTAGRAM_INTEGRATION_PLAN.md) - Plano detalhado completo
- [INSTAGRAM_INTEGRATION_SUMMARY.md](../../../INSTAGRAM_INTEGRATION_SUMMARY.md) - Resumo executivo

## Próximos Passos

1. ✅ Revisar e aprovar esta spec
2. ⏳ Obter credenciais Meta necessárias
3. ⏳ Configurar ambiente de desenvolvimento
4. ⏳ Iniciar Fase 1: Configuração e Estrutura Base
5. ⏳ Executar testes incrementais a cada fase

## Status

- **Criado em**: 2025-01-16
- **Status**: 📝 Draft - Aguardando aprovação
- **Versão**: 1.0
- **Autor**: Kiro AI Assistant

## Referências

- [Instagram Messaging API - Meta Developers](https://developers.facebook.com/docs/messenger-platform/instagram/)
- [Send Messages - Instagram](https://developers.facebook.com/docs/messenger-platform/instagram/features/send-message/)
- [Webhooks - Messenger Platform](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Graph API Reference](https://developers.facebook.com/docs/graph-api/)
