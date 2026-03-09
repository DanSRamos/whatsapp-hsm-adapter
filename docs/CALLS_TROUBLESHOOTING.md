# Troubleshooting - Chamadas WhatsApp

## Erro: "Unauthorized access"

### Problema

Ao tentar iniciar uma chamada, você recebe o erro:

```
Erro ao iniciar chamada: Unauthorized access
```

### Causa

Este erro ocorre porque sua conta Infobip não tem o serviço de **Voice/Calls** ativado. A API Key que você está usando é válida para mensagens WhatsApp, mas não tem permissões para fazer chamadas de voz.

### Solução

#### Opção 1: Ativar o Serviço Voice na Infobip (Recomendado)

1. Entre em contato com o suporte da Infobip:
   - Website: https://www.infobip.com/contact
   - Email: support@infobip.com
   - Portal: https://portal.infobip.com

2. Solicite a ativação do serviço **Voice/Calls** para sua conta

3. Pergunte sobre:
   - Custos do serviço de voz
   - Países suportados
   - Limites de chamadas
   - Documentação específica da API de voz

4. Após a ativação, você receberá:
   - Confirmação de ativação do serviço
   - Possivelmente uma nova API Key ou permissões adicionadas à existente
   - Documentação sobre como usar o serviço

#### Opção 2: Usar Alternativa para Chamadas

Se o serviço Voice da Infobip não estiver disponível ou for muito caro, considere:

**Twilio Voice API**

- Suporta chamadas de voz via WhatsApp
- Documentação: https://www.twilio.com/docs/voice
- Preços competitivos
- Fácil integração

**Vonage (Nexmo) Voice API**

- API de voz robusta
- Suporte global
- Documentação: https://developer.vonage.com/voice/voice-api/overview

**Plivo Voice API**

- Alternativa econômica
- Boa cobertura global
- Documentação: https://www.plivo.com/docs/voice/

### Verificar Permissões da Conta

Para verificar quais serviços estão ativos na sua conta Infobip:

1. Acesse o portal: https://portal.infobip.com
2. Vá em "Account" > "API Keys"
3. Verifique as permissões da sua API Key
4. Procure por "Voice" ou "Calls" nas permissões

### Testar a API de Voice

Você pode testar se sua conta tem acesso à API de Voice com este comando:

```bash
curl -X GET "https://api.infobip.com/calls/1/calls" \
  -H "Authorization: App YOUR_API_KEY" \
  -H "Accept: application/json"
```

**Respostas possíveis:**

- **200 OK**: Serviço ativo (retorna lista vazia se não houver chamadas)
- **401 Unauthorized**: API Key inválida
- **403 Forbidden**: Serviço não ativado para sua conta
- **404 Not Found**: Endpoint não existe (verifique a URL)

## Outros Erros Comuns

### Erro: "Invalid phone number"

**Causa**: Número de telefone em formato incorreto

**Solução**: Use formato internacional com código do país

```
✅ Correto: +5511999999999
❌ Errado: 11999999999
```

### Erro: "Insufficient balance"

**Causa**: Saldo insuficiente na conta Infobip

**Solução**:

1. Acesse o portal Infobip
2. Adicione créditos à sua conta
3. Verifique os custos por chamada

### Erro: "Country not supported"

**Causa**: O país de destino não suporta chamadas via WhatsApp

**Solução**:

1. Verifique a lista de países suportados na documentação da Infobip
2. Considere usar chamadas telefônicas tradicionais para esse país

### Erro: "Rate limit exceeded"

**Causa**: Muitas chamadas em curto período de tempo

**Solução**:

1. Aguarde alguns minutos antes de tentar novamente
2. Implemente rate limiting no seu código
3. Entre em contato com a Infobip para aumentar os limites

## Logs e Debug

### Verificar Logs da API

Os logs da API estão em:

```
admin-panel/api_errors.log
```

Para ver os últimos erros:

```bash
tail -50 admin-panel/api_errors.log
```

### Ativar Debug no Código

Edite `admin-panel/api.php` e adicione antes da chamada à API:

```php
// Ativar debug
error_log('=== DEBUG CALL API ===');
error_log('Payload: ' . json_encode($payload));
error_log('API Key: ' . substr($config['infobip_api_key'], 0, 10) . '...');
error_log('Endpoint: https://api.infobip.com/calls/1/calls');
```

### Testar com cURL

Teste diretamente com cURL para isolar o problema:

```bash
curl -X POST "https://api.infobip.com/calls/1/calls" \
  -H "Authorization: App YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "from": "+351927587119",
    "to": "+5511999999999"
  }' \
  -v
```

A flag `-v` mostra detalhes da requisição e resposta.

## Custos Estimados

Os custos de chamadas variam por país. Exemplos aproximados:

| País        | Custo por minuto |
| ----------- | ---------------- |
| Brasil      | €0.05 - €0.15    |
| Portugal    | €0.03 - €0.10    |
| EUA         | €0.02 - €0.08    |
| Reino Unido | €0.03 - €0.10    |

**Nota**: Valores aproximados. Consulte a Infobip para preços exatos.

## Alternativas à API de Voice

Se você não conseguir ativar o serviço Voice, considere estas alternativas:

### 1. Usar apenas mensagens WhatsApp

- Mais econômico
- Já está funcionando
- Boa para comunicação assíncrona

### 2. Integrar com Twilio

- Suporte completo a voz
- Fácil integração
- Documentação excelente

### 3. Usar chamadas telefônicas tradicionais

- Mais confiável
- Funciona em qualquer telefone
- Não depende de WhatsApp

## Suporte

### Infobip

- Website: https://www.infobip.com
- Suporte: support@infobip.com
- Portal: https://portal.infobip.com
- Documentação: https://www.infobip.com/docs

### Comunidade

- Stack Overflow: Tag `infobip`
- GitHub Issues: Reporte problemas no repositório

## Checklist de Verificação

Antes de entrar em contato com o suporte, verifique:

- [ ] API Key está correta no arquivo `.env`
- [ ] Conta Infobip está ativa
- [ ] Há saldo suficiente na conta
- [ ] Número de origem está registrado
- [ ] Número de destino está no formato correto
- [ ] País de destino suporta chamadas
- [ ] Serviço Voice está ativado na conta
- [ ] Não há problemas de rede/firewall
- [ ] Logs foram verificados

## Próximos Passos

1. Entre em contato com a Infobip para ativar o serviço Voice
2. Enquanto isso, use apenas mensagens WhatsApp
3. Considere alternativas como Twilio se necessário
4. Documente os custos e limites do serviço

## Conclusão

O erro "Unauthorized access" é esperado se sua conta não tem o serviço Voice ativado. Entre em contato com a Infobip para resolver isso. A funcionalidade de chamadas está implementada e funcionará assim que o serviço for ativado.
