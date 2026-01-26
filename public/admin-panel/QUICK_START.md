# 🚀 Quick Start - WhatsApp HSM Admin Panel

## Início Rápido (2 minutos)

### 1. Iniciar o Servidor

```bash
cd admin-panel
./start.sh
```

Ou manualmente:

```bash
cd admin-panel
php -S localhost:8080
```

### 2. Abrir no Navegador

Abra: **http://localhost:8080**

### 3. Usar a Interface

✅ **Listar Templates**: Carregam automaticamente ao abrir a página

✅ **Enviar Mensagem**:

1. Clique num template da lista
2. Insira o número (ex: 351961725398)
3. Clique em "Enviar Mensagem"

✅ **Ver Respostas**: Aparecem automaticamente no painel inferior (requer webhook configurado)

---

## 📡 Configurar Webhook (Opcional)

Para receber respostas dos utilizadores:

### Opção A: Desenvolvimento Local (ngrok)

```bash
# Terminal 1: Iniciar servidor
cd admin-panel
php -S localhost:8080

# Terminal 2: Criar túnel público
ngrok http 8080
```

Depois configure na Infobip:

- URL: `https://xxx.ngrok.io/webhook.php`
- Método: POST

### Opção B: Servidor Público

1. Faça upload para servidor com HTTPS
2. Configure webhook: `https://seu-dominio.com/admin-panel/webhook.php`

---

## 🧪 Testar Instalação

```bash
cd admin-panel
php test.php
```

---

## 📱 Exemplos de Uso

### Enviar template "suporte"

1. Selecione template "suporte"
2. Número: 351961725398
3. Idioma: pt_BR
4. Enviar

### Enviar template "teste2_mds"

1. Selecione template "teste2_mds"
2. Número: 351966141650
3. Idioma: pt_PT
4. Enviar

---

## 🔧 Troubleshooting

### Problema: Templates não carregam

**Solução**: Verifique API key em `api.php`

### Problema: Mensagem não envia

**Solução**: Verifique formato do número (sem + ou 00)

### Problema: Não recebe respostas

**Solução**: Configure webhook (ver acima)

---

## 📞 URLs Importantes

- **Interface**: http://localhost:8080
- **API Templates**: http://localhost:8080/api.php?action=get_templates
- **API Mensagens**: http://localhost:8080/api.php?action=get_messages
- **Webhook**: http://localhost:8080/webhook.php

---

## ✅ Checklist

- [ ] Servidor iniciado (localhost:8080)
- [ ] Interface abre no navegador
- [ ] Templates carregam
- [ ] Consegue enviar mensagem
- [ ] (Opcional) Webhook configurado
- [ ] (Opcional) Recebe respostas

---

**Pronto! 🎉 O painel está funcional.**

Para mais detalhes, consulte: [README.md](README.md)
