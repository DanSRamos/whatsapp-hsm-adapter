# Scripts de Teste e Desenvolvimento

Esta pasta contém scripts auxiliares para teste e desenvolvimento do Multi-Platform Messaging Adapter.

## Scripts de Verificação

### `check_incoming_messages.php`

Verifica mensagens recebidas no sistema.

### `check_template_structure.php`

Valida a estrutura dos templates WhatsApp HSM.

### `check_templates_final.php`

Versão final do script de verificação de templates (mantém a versão mais completa).

## Scripts de Envio de Teste

### `send_suporte_message.php`

Envia mensagem de teste para o suporte.

## Scripts de Simulação

### `simulate_webhook.php`

Simula chamadas de webhook para testes locais.

## Scripts de Teste

### `test_env.php`

Testa a configuração do ambiente (.env).

### `test_infobip_direct.php`

Testa conexão direta com a API Infobip.

### `test_media_template.php`

Testa envio de templates com media.

### `test_meta_webhook_post.php`

Testa POST de webhooks Meta (Instagram/Messenger).

### `test_rcs_infobip.php`

Testa funcionalidade RCS via Infobip.

### `test_send_message.php`

Script principal de teste de envio de mensagens.

### `test_webhook_meta_completo.php`

Teste completo de webhooks Meta (versão mais abrangente).

## Uso

Estes scripts são para desenvolvimento e teste apenas. **Não devem ser usados em produção.**

Para executar qualquer script:

```bash
php scripts/nome_do_script.php
```

## Nota

Alguns scripts podem requerer configuração específica no `.env` ou parâmetros adicionais.
