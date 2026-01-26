# 🔧 Troubleshooting - WhatsApp HSM Admin Panel

## Problema: Botão "Atualizar Templates" não funciona

### Sintomas

- Clica no botão "🔄 Atualizar Templates"
- Aparece mensagem "Failed to fetch" ou "Erro ao carregar templates"
- Templates não aparecem na lista

### Soluções

#### 1. Verificar se o servidor está rodando

**Teste:**

```bash
curl http://localhost:8080/api.php?action=get_templates
```

\*\*Se retornar erro "Connection
