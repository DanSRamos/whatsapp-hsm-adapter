# Guia: Configurar Credenciais Infobip

**Status**: Aguardando configuração  
**Ficheiro**: `.env`

## 📋 Passo a Passo

### 1. Obter Credenciais do Portal Infobip

#### A. Aceder ao Portal

1. Vai a: https://portal.infobip.com/
2. Faz login com a tua conta

#### B. Obter API Key

1. No portal, clica em **Settings** (canto superior direito)
2. Vai a **API Keys**
3. Copia a tua API Key
   - Se não tiveres uma, clica em **Create API Key**
   - Dá um nome (ex: "WhatsApp RCS API")
   - Guarda a key num local seguro

#### C. Obter Sender ID

1. No portal, vai a **Channels** → **WhatsApp**
2. Verifica o teu número WhatsApp Business registado
3. Copia o número (formato: `+351912345678` ou `351912345678`)

### 2. Atualizar o Ficheiro .env

Abre o ficheiro `.env` na raiz do projeto e atualiza estas linhas:

```env
# Infobip Configuration
INFOBIP_API_KEY=COLA_AQUI_A_TUA_API_KEY
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=COLA_AQUI_O_TEU_NUMERO
INFOBIP_WEBHOOK_SECRET=your_webhook_secret_here
```

#### Exemplo com valores reais:

```env
# Infobip Configuration
INFOBIP_API_KEY=abc123def456ghi789jkl012mno345pqr678stu901vwx234yz
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=+351912345678
INFOBIP_WEBHOOK_SECRET=my_secret_webhook_token_123
```

### 3. Verificar a Configuração

Depois de atualizar o `.env`, executa o script de teste:

```bash
php scripts/test_rcs_infobip.php
```

**Resultado esperado**:

```
========================================
TEST 1: Verificar Credenciais Infobip
========================================

✓ INFOBIP_API_KEY: abc123def4...x234yz
✓ INFOBIP_BASE_URL: https://ap....com
✓ INFOBIP_SENDER: +351912345678

========================================
TEST 2: Verificar Configuração do Provider
========================================

✓ Provider 'infobip-rcs' encontrado
ℹ Tipo: rcs
ℹ Enabled: Sim
✓ API Key carregada
✓ Sender carregado: +351912345678
```

### 4. Testar Envio de Mensagem

O script vai perguntar se queres enviar uma mensagem de teste:

```
Digite um número de teste (formato: +351912345678) ou ENTER para skip:
```

**Opções**:

- Digita um número válido para testar
- Pressiona ENTER para skip

### 5. Testar via Admin Panel

Depois de configurar, podes testar via interface web:

1. Abre: http://localhost:8081/admin-panel/rcs.html
2. Preenche o formulário:
   - **To**: Número de destino (ex: `+351912345678`)
   - **Text**: Mensagem de teste
3. Clica em **Send Message**
4. Verifica a resposta

## 🔍 Troubleshooting

### Erro: "API Key inválida"

**Causa**: API Key incorreta ou expirada  
**Solução**:

1. Verifica se copiaste a key completa
2. Gera uma nova key no portal Infobip
3. Atualiza o `.env`

### Erro: "Sender not found"

**Causa**: Número não registado no Infobip  
**Solução**:

1. Verifica no portal qual o número correto
2. Certifica-te que o número está ativo
3. Usa o formato correto: `+351912345678`

### Erro: "RCS not enabled"

**Causa**: RCS não ativo na tua conta  
**Solução**:

1. Contacta o suporte Infobip
2. Pede para ativar RCS Messaging
3. Aguarda confirmação

### Erro: "Connection timeout"

**Causa**: Problema de rede ou firewall  
**Solução**:

1. Verifica a tua conexão à internet
2. Testa: `curl https://api.infobip.com`
3. Verifica se há firewall a bloquear

## 📊 Verificar Logs

Se algo não funcionar, verifica os logs:

```bash
# Ver últimas linhas do log
tail -f storage/logs/whatsapp-adapter.log

# Ou
tail -f public/storage/logs/whatsapp-adapter-*.log
```

## ✅ Checklist

- [ ] Acedi ao portal Infobip
- [ ] Copiei a API Key
- [ ] Copiei o Sender ID (número WhatsApp)
- [ ] Atualizei o ficheiro `.env`
- [ ] Executei `php scripts/test_rcs_infobip.php`
- [ ] Todos os testes passaram
- [ ] Testei envio de mensagem
- [ ] Testei via Admin Panel

## 🎯 Próximos Passos

Depois de configurar com sucesso:

1. ✅ Testa todos os tipos de mensagens RCS:
   - Texto simples
   - Rich Cards
   - Carousels
   - Mensagens com sugestões
   - Envio de ficheiros

2. ✅ Configura webhooks para receber notificações

3. ✅ Monitoriza no portal Infobip

## 📞 Suporte

Se precisares de ajuda:

- **Portal Infobip**: https://portal.infobip.com/
- **Documentação**: https://www.infobip.com/docs/api/channels/rcs
- **Suporte**: support@infobip.com

---

**Nota**: As credenciais são sensíveis! Nunca commits o ficheiro `.env` para o git.
