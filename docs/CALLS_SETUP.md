# Configuração de Chamadas WhatsApp via Infobip

Este documento descreve como configurar e usar o sistema de chamadas via WhatsApp usando a API da Infobip.

## ⚠️ IMPORTANTE: Requisitos do Serviço Voice

Para usar chamadas de voz, você precisa:

1. **Conta Infobip com serviço Voice/Calls ativado**
   - O serviço de mensagens WhatsApp NÃO inclui chamadas de voz
   - É necessário solicitar ativação separada do serviço Voice
   - Entre em contato: https://www.infobip.com/contact

2. **Se você receber erro "Unauthorized access":**
   - Sua conta não tem o serviço Voice ativado
   - Consulte o [Guia de Troubleshooting](CALLS_TROUBLESHOOTING.md)
   - Entre em contato com o suporte da Infobip

## Pré-requisitos

1. Conta ativa na Infobip **com serviço Voice/Calls ativado**
2. API Key da Infobip configurada
3. Número de WhatsApp Business registrado na Infobip
4. Permissões para fazer chamadas via API
5. Saldo suficiente na conta para chamadas

## Configuração

### 1. Variáveis de Ambiente

Certifique-se de que as seguintes variáveis estão configuradas no arquivo `.env`:

```env
INFOBIP_API_KEY=sua_api_key_aqui
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=seu_numero_whatsapp
```

### 2. Verificar Configuração

Acesse o painel administrativo e verifique se as credenciais estão corretas:

```
http://localhost:8080/admin-panel/index-tabs.html
```

## Funcionalidades

### Iniciar Chamada

1. Acesse a interface de chamadas em `admin-panel/calls.html`
2. Digite o número do WhatsApp no formato internacional (ex: +5511999999999)
3. Clique em "Iniciar Chamada"
4. O sistema exibirá o status da chamada em tempo real

### Monitorar Chamada

Durante uma chamada ativa, você pode:

- Ver o status atual (iniciada, tocando, atendida, encerrada)
- Acompanhar a duração da chamada
- Ver o ID da chamada para referência

### Encerrar Chamada

Para encerrar uma chamada ativa:

1. Clique no botão "Encerrar Chamada"
2. A chamada será terminada imediatamente
3. O histórico será atualizado automaticamente

### Histórico de Chamadas

O histórico mostra:

- Número de destino
- Data e hora da chamada
- Duração total
- Status final

## API Endpoints

### POST /api.php?action=initiate_call

Inicia uma nova chamada.

**Request:**

```json
{
  "to": "+5511999999999",
  "from": "+351927587119"
}
```

**Response:**

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "initiated",
  "to": "+5511999999999",
  "from": "+351927587119"
}
```

### GET /api.php?action=get_call_status&call_id={callId}

Obtém o status de uma chamada.

**Response:**

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "answered",
  "duration": 45,
  "startTime": "2024-03-06T10:30:00Z",
  "endTime": null
}
```

### POST /api.php?action=hangup_call&call_id={callId}

Encerra uma chamada ativa.

**Response:**

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "terminated",
  "message": "Chamada encerrada com sucesso"
}
```

### GET /api.php?action=get_call_history

Lista o histórico de chamadas.

**Query Parameters:**

- `from`: Filtrar por número de origem (opcional)
- `to`: Filtrar por número de destino (opcional)
- `limit`: Número máximo de resultados (padrão: 50)

**Response:**

```json
{
  "success": true,
  "calls": [
    {
      "callId": "abc123",
      "from": "+351927587119",
      "to": "+5511999999999",
      "status": "terminated",
      "duration": 120,
      "startTime": "2024-03-06T10:30:00Z",
      "endTime": "2024-03-06T10:32:00Z"
    }
  ],
  "total": 1
}
```

## Status de Chamadas

- `initiated`: Chamada iniciada
- `ringing`: Telefone tocando
- `answered`: Chamada atendida
- `terminated`: Chamada encerrada
- `failed`: Chamada falhou
- `busy`: Linha ocupada
- `no_answer`: Sem resposta

## Formato de Números

Os números devem estar no formato internacional:

- Incluir código do país com `+`
- Exemplo Brasil: `+5511999999999`
- Exemplo Portugal: `+351927587119`

O sistema automaticamente formata números que não incluem o `+`.

## Limitações

1. **Rate Limits**: A Infobip pode ter limites de taxa para chamadas
2. **Custos**: Cada chamada tem um custo associado
3. **Disponibilidade**: Nem todos os países suportam chamadas via WhatsApp
4. **Permissões**: Verifique se sua conta tem permissão para fazer chamadas

## Troubleshooting

### ⚠️ Erro: "Unauthorized access" (MAIS COMUM)

**Causa**: Sua conta Infobip não tem o serviço Voice/Calls ativado.

**Solução**:

1. Entre em contato com a Infobip: https://www.infobip.com/contact
2. Solicite ativação do serviço "Voice/Calls"
3. Pergunte sobre custos e países suportados
4. Aguarde confirmação da ativação

**Documentação completa**: [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)

### Erro: "Infobip credentials not configured"

Verifique se as variáveis de ambiente estão configuradas corretamente no arquivo `.env`.

### Erro: "Failed to initiate call"

Possíveis causas:

- API Key inválida
- Número de origem não registrado
- Número de destino inválido
- Conta sem permissão para chamadas
- **Serviço Voice não ativado** (causa mais comum)

### Chamada não conecta

Verifique:

- Número de destino está correto
- Número tem WhatsApp ativo
- País suporta chamadas via WhatsApp
- Saldo da conta Infobip
- **Serviço Voice está ativado na conta**

## Suporte

### Ativar Serviço Voice

- **Website**: https://www.infobip.com/contact
- **Email**: support@infobip.com
- **Portal**: https://portal.infobip.com

### Documentação

- [Documentação Oficial Infobip](https://www.infobip.com/docs/api)
- [Troubleshooting Completo](CALLS_TROUBLESHOOTING.md)
- [Guia Rápido](CALLS_QUICK_START.md)

### Alternativas

Se o serviço Voice não estiver disponível:

- **Twilio Voice API**: https://www.twilio.com/docs/voice
- **Vonage Voice API**: https://developer.vonage.com/voice
- **Plivo Voice API**: https://www.plivo.com/docs/voice/

## Segurança

- Nunca exponha sua API Key publicamente
- Use HTTPS em produção
- Implemente autenticação no painel administrativo
- Monitore o uso para detectar abusos
- Configure rate limiting no servidor
