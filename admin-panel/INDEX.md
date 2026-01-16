# 📱 WhatsApp HSM Admin Panel - Índice de Documentação

## 🚀 Começar Agora

**Quer começar rapidamente?** → [QUICK_START.md](QUICK_START.md)

## 📚 Documentação Completa

### Para Utilizadores

1. **[QUICK_START.md](QUICK_START.md)** - Início rápido em 2 minutos

   - Como iniciar o servidor
   - Como usar a interface
   - Como configurar webhook

2. **[README.md](README.md)** - Documentação completa

   - Funcionalidades detalhadas
   - Requisitos do sistema
   - Instalação passo-a-passo
   - Configuração de produção
   - Troubleshooting
   - Segurança

3. **[FEATURES.md](FEATURES.md)** - Lista de funcionalidades

   - Todas as funcionalidades implementadas
   - Casos de uso
   - Estatísticas do projeto
   - Melhorias futuras

4. **[SCREENSHOTS.md](SCREENSHOTS.md)** - Interface visual
   - Screenshots em ASCII art
   - Estados da interface
   - Paleta de cores
   - Responsividade

### Para Desenvolvedores

5. **[config.example.php](config.example.php)** - Exemplo de configuração

   - Estrutura de configuração
   - Variáveis disponíveis
   - Opções de segurança

6. **Código Fonte**
   - [index.html](index.html) - Frontend (interface)
   - [api.php](api.php) - Backend API
   - [webhook.php](webhook.php) - Endpoint webhook
   - [test.php](test.php) - Script de testes
   - [start.sh](start.sh) - Script de inicialização

## 🎯 Navegação Rápida

### Quero...

#### ...começar a usar agora

→ [QUICK_START.md](QUICK_START.md) → Secção "Início Rápido"

#### ...entender todas as funcionalidades

→ [FEATURES.md](FEATURES.md) → Secção "Funcionalidades Implementadas"

#### ...configurar para produção

→ [README.md](README.md) → Secção "Configurar Webhook (Produção)"

#### ...resolver um problema

→ [README.md](README.md) → Secção "Troubleshooting"

#### ...ver como fica a interface

→ [SCREENSHOTS.md](SCREENSHOTS.md) → Secção "Interface Principal"

#### ...testar se está tudo OK

→ Execute: `php test.php`

#### ...configurar webhook local

→ [QUICK_START.md](QUICK_START.md) → Secção "Configurar Webhook"

#### ...personalizar configurações

→ [config.example.php](config.example.php)

## 📋 Checklist de Setup

- [ ] 1. Ler [QUICK_START.md](QUICK_START.md)
- [ ] 2. Executar `./start.sh` ou `php -S localhost:8080`
- [ ] 3. Abrir http://localhost:8080
- [ ] 4. Testar listar templates
- [ ] 5. Testar enviar mensagem
- [ ] 6. (Opcional) Configurar webhook
- [ ] 7. (Opcional) Testar receber mensagens

## 🔗 Links Úteis

### Documentação Externa

- [Infobip API Docs](https://www.infobip.com/docs/api)
- [WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)
- [ngrok Documentation](https://ngrok.com/docs)

### Ferramentas

- [ngrok Download](https://ngrok.com/download)
- [PHP Download](https://www.php.net/downloads)
- [Composer](https://getcomposer.org/)

### Suporte

- [Infobip Support](https://www.infobip.com/contact)
- [GitHub Issues](https://github.com/DanSRamos/whatsapp-hsm-adapter/issues)

## 📊 Estrutura do Projeto

```
admin-panel/
├── 📄 Documentação
│   ├── INDEX.md (este ficheiro)
│   ├── QUICK_START.md
│   ├── README.md
│   ├── FEATURES.md
│   └── SCREENSHOTS.md
│
├── 💻 Código
│   ├── index.html (Frontend)
│   ├── api.php (Backend)
│   └── webhook.php (Webhook)
│
├── 🔧 Configuração
│   ├── config.example.php
│   └── .gitignore
│
├── 🛠️ Ferramentas
│   ├── start.sh (Iniciar servidor)
│   └── test.php (Testes)
│
└── 💾 Dados (criados automaticamente)
    ├── messages.json (Mensagens)
    └── webhook.log (Logs)
```

## 🎓 Ordem de Leitura Recomendada

### Para Iniciantes

1. [QUICK_START.md](QUICK_START.md) - Começar
2. [SCREENSHOTS.md](SCREENSHOTS.md) - Ver interface
3. [README.md](README.md) - Aprofundar

### Para Desenvolvedores

1. [FEATURES.md](FEATURES.md) - Entender funcionalidades
2. [README.md](README.md) - Detalhes técnicos
3. Código fonte (index.html, api.php, webhook.php)

### Para Produção

1. [README.md](README.md) - Secção "Segurança"
2. [config.example.php](config.example.php) - Configuração
3. [README.md](README.md) - Secção "Configurar Webhook"

## ❓ FAQ Rápido

**P: Como inicio o servidor?**
R: `cd admin-panel && ./start.sh`

**P: Onde acesso a interface?**
R: http://localhost:8080

**P: Como recebo mensagens?**
R: Configure webhook (ver [QUICK_START.md](QUICK_START.md))

**P: Funciona em produção?**
R: Sim, mas adicione segurança (ver [README.md](README.md))

**P: Preciso de base de dados?**
R: Não, usa ficheiros JSON

**P: Como testo se está tudo OK?**
R: Execute `php test.php`

## 📞 Contacto e Suporte

- **Projeto**: WhatsApp HSM Adapter
- **GitHub**: https://github.com/DanSRamos/whatsapp-hsm-adapter
- **Documentação Principal**: [README.md](../README.md)

## ✅ Status do Projeto

- **Versão**: 1.0.0
- **Status**: ✅ Completo e Funcional
- **Última Atualização**: 16 Janeiro 2026
- **Compatibilidade**: PHP 7.4+
- **Licença**: MIT (presumido)

---

**Pronto para começar?** → [QUICK_START.md](QUICK_START.md) 🚀
