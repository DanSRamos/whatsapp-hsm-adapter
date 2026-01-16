# 📋 Plano de Migração: WhatsApp HSM Adapter → Symfony Boilerplate

## 🔍 Análise Comparativa

### Projeto Atual (WhatsApp HSM Adapter)

- **Framework**: PHP Vanilla com PSR-7/PSR-15
- **Arquitetura**: Custom MVC simplificado
- **Testing**: Pest PHP com Property-Based Testing
- **Database**: PDO direto
- **Cache**: Redis direto
- **Routing**: Router custom
- **DI**: Manual
- **Deployment**: Manual/Docker básico

### Boilerplate Symfony

- **Framework**: Symfony 7.2
- **Arquitetura**: Modular (Module-based)
- **Testing**: PHPUnit
- **Database**: Doctrine ORM
- **Cache**: Symfony Cache Component
- **Routing**: Symfony Router
- **DI**: Symfony Container (autowiring)
- **Deployment**: Docker + Docker Compose + CI/CD

---

## 📊 Gaps Identificados

### 1. **Framework & Arquitetura**

- ❌ Projeto atual não usa Symfony
- ❌ Sem Dependency Injection Container
- ❌ Sem Event Dispatcher
- ❌ Sem Service Container

### 2. **Estrutura de Código**

- ❌ Controllers não seguem padrão Symfony
- ❌ Sem EventListeners (AuthListener, ExceptionListener, JsonListener)
- ❌ Sem Commands (Console)
- ❌ Sem Consumers (RabbitMQ)

### 3. **Configuração**

- ❌ Sem arquivos de configuração Symfony (services.yaml, packages/)
- ❌ Sem bundles configurados
- ❌ Sem parameters.yaml

### 4. **Database**

- ❌ Usa PDO direto em vez de Doctrine ORM
- ❌ Sem Entities
- ❌ Sem Repositories Doctrine
- ❌ Migrations manuais em vez de Doctrine Migrations

### 5. **Testing**

- ✅ Tem Property-Based Testing (melhor que boilerplate)
- ❌ Usa Pest em vez de PHPUnit
- ❌ Sem testes de integração Symfony

### 6. **DevOps**

- ❌ Sem Docker Compose completo
- ❌ Sem CI/CD (GitLab CI)
- ❌ Sem scripts de deployment
- ❌ Sem HAProxy config
- ❌ Sem systemd services

### 7. **Code Quality**

- ❌ Sem PHP CS Fixer configurado
- ❌ Sem PHPStan configurado
- ❌ Sem Rector configurado
- ❌ Sem PHPCS configurado

### 8. **Monitoring**

- ❌ Sem integração com Byside Monitor
- ❌ Sem health checks estruturados
- ❌ Sem métricas

---

## 🎯 Plano de Tarefas

### **Fase 1: Setup Symfony Base** (Prioridade: ALTA)

#### 1.1 Instalar Symfony Framework

- [ ] Criar novo projeto Symfony 7.2
- [ ] Copiar estrutura de pastas do boilerplate
- [ ] Configurar `composer.json` com dependências Symfony
- [ ] Instalar bundles essenciais:
  - `symfony/framework-bundle`
  - `symfony/console`
  - `symfony/monolog-bundle`
  - `doctrine/doctrine-bundle`
  - `doctrine/orm`

#### 1.2 Configurar Kernel e Bootstrap

- [ ] Criar `src/Kernel.php` baseado no boilerplate
- [ ] Configurar `config/bootstrap.php`
- [ ] Configurar `config/bundles.php`
- [ ] Criar `public/index.php` como entry point

#### 1.3 Configurar Services

- [ ] Criar `config/services.yaml`
- [ ] Configurar autowiring
- [ ] Configurar autoconfigure
- [ ] Migrar serviços atuais para DI Container

---

### **Fase 2: Migração de Database** (Prioridade: ALTA)

#### 2.1 Configurar Doctrine ORM

- [ ] Instalar `doctrine/doctrine-bundle`
- [ ] Instalar `doctrine/doctrine-migrations-bundle`
- [ ] Configurar `config/packages/doctrine.yaml`
- [ ] Configurar connection strings

#### 2.2 Criar Entities

- [ ] Migrar `messages` table → `Message` Entity
- [ ] Migrar `incoming_messages` table → `IncomingMessage` Entity
- [ ] Migrar `templates` table → `Template` Entity
- [ ] Migrar `webhook_logs` table → `WebhookLog` Entity
- [ ] Adicionar annotations/attributes
- [ ] Configurar relationships

#### 2.3 Criar Repositories

- [ ] Criar `MessageRepository` (Doctrine)
- [ ] Criar `IncomingMessageRepository` (Doctrine)
- [ ] Criar `TemplateRepository` (Doctrine)
- [ ] Criar `WebhookLogRepository` (Doctrine)
- [ ] Migrar queries SQL para DQL/QueryBuilder

#### 2.4 Migrar Migrations

- [ ] Converter migrations SQL para Doctrine Migrations
- [ ] Testar migrations up/down
- [ ] Criar fixtures para testes

---

### **Fase 3: Migração de Controllers** (Prioridade: ALTA)

#### 3.1 Criar Controllers Symfony

- [ ] Migrar `MessageController` → Symfony Controller
- [ ] Migrar `TemplateController` → Symfony Controller
- [ ] Migrar `WebhookController` → Symfony Controller
- [ ] Migrar `HealthController` → Symfony Controller
- [ ] Adicionar annotations de routing
- [ ] Usar `AbstractController` como base

#### 3.2 Configurar Routing

- [ ] Criar `config/routes.yaml`
- [ ] Migrar rotas do Router custom
- [ ] Configurar route parameters
- [ ] Adicionar route constraints

#### 3.3 Criar Event Listeners

- [ ] Criar `AuthListener` (baseado no boilerplate)
- [ ] Criar `ExceptionListener` (baseado no boilerplate)
- [ ] Criar `JsonListener` (baseado no boilerplate)
- [ ] Registar listeners em `services.yaml`

---

### **Fase 4: Migração de Services** (Prioridade: MÉDIA)

#### 4.1 Migrar Provider Services

- [ ] Migrar `InfobipProvider` para usar DI
- [ ] Migrar `TwilioProvider` para usar DI
- [ ] Criar interface `ProviderInterface`
- [ ] Configurar factory pattern no container

#### 4.2 Migrar Core Services

- [ ] Migrar `MessageService`
- [ ] Migrar `TemplateService`
- [ ] Migrar `WebhookValidator`
- [ ] Migrar `RetryHandler`
- [ ] Migrar `MediaValidator`
- [ ] Migrar `InteractiveValidator`

#### 4.3 Migrar Utility Services

- [ ] Migrar `LoggerFactory` → usar Monolog
- [ ] Migrar `CriticalErrorNotifier`
- [ ] Criar `Redis` component (baseado no boilerplate)

---

### **Fase 5: Configuração & Environment** (Prioridade: MÉDIA)

#### 5.1 Configurar Packages

- [ ] Criar `config/packages/framework.yaml`
- [ ] Criar `config/packages/doctrine.yaml`
- [ ] Criar `config/packages/monolog.yaml`
- [ ] Criar `config/packages/cache.yaml`
- [ ] Criar `config/packages/validator.yaml`

#### 5.2 Configurar Environments

- [ ] Criar configs para `dev` environment
- [ ] Criar configs para `test` environment
- [ ] Criar configs para `prod` environment
- [ ] Configurar `.env` files

#### 5.3 Criar Parameters

- [ ] Criar `config/parameters.yaml`
- [ ] Migrar configurações de `config/whatsapp.php`
- [ ] Migrar configurações de `config/cache.php`
- [ ] Migrar configurações de `config/logging.php`

---

### **Fase 6: Testing** (Prioridade: MÉDIA)

#### 6.1 Configurar PHPUnit

- [ ] Instalar `phpunit/phpunit`
- [ ] Criar `phpunit.xml.dist`
- [ ] Configurar test database
- [ ] Criar `tests/bootstrap.php`

#### 6.2 Migrar Unit Tests

- [ ] Converter testes Pest → PHPUnit
- [ ] Manter Property-Based Tests (usar biblioteca compatível)
- [ ] Criar WebTestCase para controllers
- [ ] Criar KernelTestCase para services

#### 6.3 Criar Integration Tests

- [ ] Testes de API endpoints
- [ ] Testes de Doctrine repositories
- [ ] Testes de Event Listeners
- [ ] Testes de Commands

---

### **Fase 7: DevOps & Deployment** (Prioridade: BAIXA)

#### 7.1 Docker Setup

- [ ] Criar `Dockerfile` (baseado no boilerplate)
- [ ] Criar `docker-compose.yml` completo
- [ ] Criar `docker-compose-dev.yml`
- [ ] Criar `docker-compose-ci.yml`
- [ ] Configurar volumes e networks

#### 7.2 Scripts de Deployment

- [ ] Criar `bin/docker-compose-run.sh`
- [ ] Criar `bin/start-dev.sh`
- [ ] Criar `bin/stop-dev.sh`
- [ ] Criar scripts de entrypoint

#### 7.3 CI/CD

- [ ] Criar `.gitlab-ci.yml`
- [ ] Configurar stages (build, test, deploy)
- [ ] Configurar Docker registry
- [ ] Configurar deployment automático

#### 7.4 Nginx & HAProxy

- [ ] Criar configuração Nginx
- [ ] Criar configuração HAProxy
- [ ] Configurar SSL/TLS
- [ ] Configurar load balancing

---

### **Fase 8: Code Quality** (Prioridade: BAIXA)

#### 8.1 PHP CS Fixer

- [ ] Criar `.php-cs-fixer.dist.php`
- [ ] Configurar rules
- [ ] Executar fix em todo código
- [ ] Adicionar ao CI/CD

#### 8.2 PHPStan

- [ ] Criar `phpstan.neon`
- [ ] Configurar level (começar com 5)
- [ ] Corrigir erros encontrados
- [ ] Adicionar ao CI/CD

#### 8.3 PHPCS

- [ ] Criar `phpcs.xml.dist`
- [ ] Configurar PSR-12
- [ ] Corrigir violations
- [ ] Adicionar ao CI/CD

#### 8.4 Rector

- [ ] Criar `rector.php`
- [ ] Configurar rules de modernização
- [ ] Executar refactoring
- [ ] Adicionar ao CI/CD

---

### **Fase 9: Monitoring & Observability** (Prioridade: BAIXA)

#### 9.1 Health Checks

- [ ] Criar `Module/Health/` structure
- [ ] Implementar `DatabaseHealthCheck`
- [ ] Implementar `RedisHealthCheck`
- [ ] Implementar `RabbitMqHealthCheck` (se necessário)
- [ ] Criar endpoint `/health`

#### 9.2 Logging

- [ ] Configurar Monolog handlers
- [ ] Configurar log rotation
- [ ] Adicionar structured logging
- [ ] Configurar log levels por environment

#### 9.3 Metrics (Opcional)

- [ ] Integrar com Byside Monitor
- [ ] Adicionar métricas de performance
- [ ] Adicionar métricas de negócio
- [ ] Criar dashboards

---

### **Fase 10: Features Adicionais** (Prioridade: BAIXA)

#### 10.1 Console Commands

- [ ] Criar command para sync templates
- [ ] Criar command para retry failed messages
- [ ] Criar command para cleanup old logs
- [ ] Criar command para health check

#### 10.2 RabbitMQ (Opcional)

- [ ] Instalar `php-amqplib/rabbitmq-bundle`
- [ ] Configurar connections
- [ ] Criar producers
- [ ] Criar consumers
- [ ] Criar command para start consumers

#### 10.3 Admin Panel Integration

- [ ] Integrar admin-panel com Symfony
- [ ] Criar API endpoints para admin
- [ ] Adicionar autenticação
- [ ] Adicionar CORS configuration

---

## 📈 Estimativa de Esforço

| Fase                  | Tarefas         | Estimativa     | Prioridade |
| --------------------- | --------------- | -------------- | ---------- |
| Fase 1: Setup Symfony | 12              | 2-3 dias       | ALTA       |
| Fase 2: Database      | 15              | 3-4 dias       | ALTA       |
| Fase 3: Controllers   | 12              | 2-3 dias       | ALTA       |
| Fase 4: Services      | 12              | 2-3 dias       | MÉDIA      |
| Fase 5: Config        | 12              | 1-2 dias       | MÉDIA      |
| Fase 6: Testing       | 12              | 3-4 dias       | MÉDIA      |
| Fase 7: DevOps        | 16              | 3-4 dias       | BAIXA      |
| Fase 8: Code Quality  | 12              | 2-3 dias       | BAIXA      |
| Fase 9: Monitoring    | 9               | 1-2 dias       | BAIXA      |
| Fase 10: Features     | 12              | 2-3 dias       | BAIXA      |
| **TOTAL**             | **124 tarefas** | **21-31 dias** | -          |

---

## 🎯 Recomendações

### Abordagem Sugerida

1. **Migração Incremental** (Recomendado)

   - Manter projeto atual funcionando
   - Criar novo projeto Symfony em paralelo
   - Migrar módulo por módulo
   - Testar cada módulo antes de prosseguir
   - Fazer cutover quando 80% estiver migrado

2. **Big Bang** (Não Recomendado)
   - Migrar tudo de uma vez
   - Alto risco
   - Difícil de testar
   - Pode causar downtime

### Priorização

**Sprint 1 (Semana 1-2)**: Fases 1, 2, 3

- Setup básico Symfony
- Database com Doctrine
- Controllers principais funcionando

**Sprint 2 (Semana 3-4)**: Fases 4, 5

- Services migrados
- Configuração completa
- Testes básicos

**Sprint 3 (Semana 5-6)**: Fases 6, 7

- Testing completo
- DevOps setup
- Deploy em staging

**Sprint 4 (Semana 7+)**: Fases 8, 9, 10

- Code quality
- Monitoring
- Features extras
- Deploy em produção

---

## ⚠️ Riscos & Mitigações

### Riscos Identificados

1. **Perda de Property-Based Testing**

   - **Mitigação**: Manter Pest ou usar biblioteca PHPUnit compatível

2. **Breaking Changes na API**

   - **Mitigação**: Manter compatibilidade com versão atual

3. **Performance Degradation**

   - **Mitigação**: Benchmarks antes e depois

4. **Downtime durante migração**

   - **Mitigação**: Blue-green deployment

5. **Perda de conhecimento do código**
   - **Mitigação**: Documentação detalhada

---

## ✅ Checklist de Conclusão

Antes de considerar a migração completa:

- [ ] Todos os testes passam (unit + integration)
- [ ] Performance igual ou melhor que versão atual
- [ ] API mantém compatibilidade
- [ ] Documentação atualizada
- [ ] CI/CD funcionando
- [ ] Deploy em staging testado
- [ ] Rollback plan definido
- [ ] Monitoring configurado
- [ ] Team training completo

---

## 📚 Recursos

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)

---

**Criado em**: 16 Janeiro 2026  
**Versão**: 1.0  
**Status**: 📋 Planeamento
