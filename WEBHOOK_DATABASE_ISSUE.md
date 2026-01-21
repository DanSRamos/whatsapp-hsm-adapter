# ⚠️ Problema Identificado: MySQL não está rodando

## 🔍 Situação Atual

O webhook está configurado corretamente, mas o servidor PHP está falhando porque está tentando conectar ao MySQL, que não está rodando.

**Erro:**

```
SQLSTATE[HY000] [2002] Connection refused
```

---

## ✅ Soluções

### Opção 1: Iniciar o MySQL (Recomendado)

Se você tem o MySQL instalado, inicie o serviço:

```bash
# macOS com Homebrew
brew services start mysql

# Ou manualmente
mysql.server start
```

Depois teste novamente:

```bash
curl -X GET "http://localhost:8081/webhooks/meta?hub.mode=subscribe&hub.verify_token=d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5&hub.challenge=TESTE123"
```

---

### Opção 2: Instalar MySQL

Se você não tem o MySQL instalado:

```bash
# Instalar via Homebrew
brew install mysql

# Iniciar o serviço
brew services start mysql

# Configurar senha root (opcional)
mysql_secure_installation
```

---

### Opção 3: Usar SQLite (Temporário para testes)

Edite o arquivo `.env` e mude para SQLite:

```bash
# Mudar de:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=whatsapp_adapter

# Para:
DB_CONNECTION=sqlite
DB_DATABASE=storage/database.sqlite
```

Depois crie o arquivo do banco:

```bash
touch storage/database.sqlite
```

---

### Opção 4: Modificar temporariamente o código (Não recomendado)

Você pode comentar temporariamente a conexão ao banco no `public/index.php`, mas isso vai quebrar outras funcionalidades.

---

## 🎯 Recomendação

**Use a Opção 1 ou 2** - Iniciar/Instalar o MySQL é a melhor solução porque:

- O sistema precisa do banco de dados para funcionar completamente
- Armazenar mensagens, templates, logs, etc.
- É a configuração de produção

---

## 📋 Após Resolver o Problema do Banco

1. **Reinicie o servidor PHP** (se necessário)
2. **Teste o webhook localmente:**

   ```bash
   curl -X GET "http://localhost:8081/webhooks/meta?hub.mode=subscribe&hub.verify_token=d0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5&hub.challenge=TESTE123"
   ```

   **Resposta esperada:** `TESTE123`

3. **Configure no Meta Dashboard** usando as informações do `WEBHOOK_SETUP_SUMMARY.md`

---

## 🔧 Verificar Status do MySQL

```bash
# Ver se o MySQL está rodando
brew services list | grep mysql

# Ou
ps aux | grep mysql
```

---

**Qual opção você prefere?**
