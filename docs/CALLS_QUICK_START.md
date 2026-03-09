# Guia Rápido - Chamadas WhatsApp

## ⚠️ AVISO IMPORTANTE

**Antes de começar**: Para usar chamadas de voz, sua conta Infobip precisa ter o serviço **Voice/Calls** ativado.

- Se você receber erro "Unauthorized access", sua conta não tem este serviço
- Entre em contato: https://www.infobip.com/contact
- Consulte: [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)

## Início Rápido em 3 Passos

### 1. Configurar Credenciais

Edite o arquivo `.env`:

```env
INFOBIP_API_KEY=sua_api_key_aqui
INFOBIP_SENDER=seu_numero_whatsapp
INFOBIP_BASE_URL=https://api.infobip.com
```

### 2. Acessar Interface

Abra no navegador:

```
http://localhost:8080/admin-panel/calls.html
```

### 3. Fazer uma Chamada

1. Digite o número no formato internacional: `+5511999999999`
2. Clique em "Iniciar Chamada"
3. Acompanhe o status em tempo real
4. Clique em "Encerrar Chamada" quando terminar

## Exemplo via API

### Iniciar Chamada

```bash
curl -X POST http://localhost:8080/admin-panel/api.php?action=initiate_call \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+5511999999999"
  }'
```

Resposta:

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "initiated",
  "to": "+5511999999999"
}
```

### Verificar Status

```bash
curl http://localhost:8080/admin-panel/api.php?action=get_call_status&call_id=abc123
```

Resposta:

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "answered",
  "duration": 45
}
```

### Encerrar Chamada

```bash
curl -X POST http://localhost:8080/admin-panel/api.php?action=hangup_call&call_id=abc123
```

Resposta:

```json
{
  "success": true,
  "call_id": "abc123",
  "status": "terminated"
}
```

### Ver Histórico

```bash
curl http://localhost:8080/admin-panel/api.php?action=get_call_history
```

Resposta:

```json
{
  "success": true,
  "calls": [
    {
      "callId": "abc123",
      "to": "+5511999999999",
      "status": "terminated",
      "duration": 120,
      "startTime": "2024-03-06T10:30:00Z"
    }
  ],
  "total": 1
}
```

## Exemplo em PHP

```php
<?php

// Iniciar chamada
$ch = curl_init('http://localhost:8080/admin-panel/api.php?action=initiate_call');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'to' => '+5511999999999'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$result = json_decode($response, true);

if ($result['success']) {
    echo "Chamada iniciada! ID: " . $result['call_id'] . "\n";

    // Aguardar alguns segundos
    sleep(5);

    // Verificar status
    $statusUrl = 'http://localhost:8080/admin-panel/api.php?action=get_call_status&call_id=' . $result['call_id'];
    $statusResponse = file_get_contents($statusUrl);
    $status = json_decode($statusResponse, true);

    echo "Status: " . $status['status'] . "\n";
    echo "Duração: " . $status['duration'] . " segundos\n";
} else {
    echo "Erro: " . $result['error'] . "\n";
}

curl_close($ch);
```

## Exemplo em JavaScript

```javascript
// Iniciar chamada
async function makeCall(phoneNumber) {
  const response = await fetch("/admin-panel/api.php?action=initiate_call", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      to: phoneNumber,
    }),
  });

  const result = await response.json();

  if (result.success) {
    console.log("Chamada iniciada!", result.call_id);
    return result.call_id;
  } else {
    console.error("Erro:", result.error);
    return null;
  }
}

// Verificar status
async function checkCallStatus(callId) {
  const response = await fetch(
    `/admin-panel/api.php?action=get_call_status&call_id=${callId}`,
  );

  const result = await response.json();

  if (result.success) {
    console.log("Status:", result.status);
    console.log("Duração:", result.duration, "segundos");
    return result;
  }

  return null;
}

// Encerrar chamada
async function hangupCall(callId) {
  const response = await fetch(
    `/admin-panel/api.php?action=hangup_call&call_id=${callId}`,
    { method: "POST" },
  );

  const result = await response.json();

  if (result.success) {
    console.log("Chamada encerrada");
  }
}

// Uso
const callId = await makeCall("+5511999999999");
if (callId) {
  // Verificar status a cada 3 segundos
  const interval = setInterval(async () => {
    const status = await checkCallStatus(callId);
    if (status.status === "terminated") {
      clearInterval(interval);
    }
  }, 3000);
}
```

## Formato de Números

✅ Correto:

- `+5511999999999` (Brasil)
- `+351927587119` (Portugal)
- `+14155551234` (EUA)

❌ Incorreto:

- `11999999999` (sem código do país)
- `5511999999999` (sem +)
- `+55 11 99999-9999` (com formatação)

## Erros Comuns

### "Unauthorized access"

- **Causa**: Serviço Voice não ativado na conta Infobip
- **Solução**: Entre em contato com a Infobip para ativar
- **Mais detalhes**: [CALLS_TROUBLESHOOTING.md](CALLS_TROUBLESHOOTING.md)

### "Invalid phone number"

- **Causa**: Formato incorreto
- **Solução**: Use formato internacional com + (ex: +5511999999999)

### "Insufficient balance"

- **Causa**: Saldo insuficiente
- **Solução**: Adicione créditos no portal Infobip

## Próximos Passos

- [Documentação Completa](CALLS_SETUP.md)
- [Troubleshooting Detalhado](CALLS_TROUBLESHOOTING.md)
- [API Reference](CALLS_SETUP.md#api-endpoints)
- [Contato Infobip](https://www.infobip.com/contact)
