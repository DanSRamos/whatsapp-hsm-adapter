# Requirements Document

## Introduction

Este documento descreve os requisitos para um adapter PHP que permite o envio e gestão de HSM (Highly Structured Messages) do WhatsApp através da API da Infobip. O sistema deve fornecer endpoints para recuperar templates, enviar mensagens, monitorizar estados e gerir comunicação bidirecional entre operadores e clientes.

## Glossary

- **HSM**: Highly Structured Message - mensagens estruturadas do WhatsApp baseadas em templates pré-aprovados
- **Infobip**: Plataforma de comunicação que fornece API para envio de mensagens WhatsApp
- **Template**: Modelo pré-aprovado de mensagem HSM
- **Adapter**: Sistema que integra a API da Infobip com a aplicação
- **Operador**: Utilizador do sistema que envia mensagens aos clientes
- **Cliente**: Destinatário das mensagens WhatsApp
- **Endpoint**: Rota HTTP que expõe funcionalidade da API

## Requirements

### Requirement 1: Gestão de Templates HSM

**User Story:** Como um operador, eu quero recuperar os templates HSM disponíveis da Infobip, para que eu possa saber quais mensagens posso enviar aos clientes.

#### Acceptance Criteria

1. WHEN um pedido é feito ao endpoint de templates, THE Adapter SHALL recuperar todos os templates HSM disponíveis da API da Infobip
2. WHEN a API da Infobip retorna templates, THE Adapter SHALL formatar os dados num formato consistente e retorná-los ao cliente
3. WHEN a API da Infobip retorna um erro, THE Adapter SHALL retornar uma mensagem de erro descritiva com código de status apropriado
4. THE Adapter SHALL incluir informações completas do template (ID, nome, idioma, parâmetros, status de aprovação)

### Requirement 2: Sincronização e Notificações de Alterações em Templates

**User Story:** Como um administrador do sistema, eu quero sincronizar templates manualmente e ser notificado automaticamente quando um template HSM é alterado ou apagado, para que eu possa manter a sincronização com os provedores (Infobip, Twilio, etc.).

#### Acceptance Criteria

1. WHEN um pedido de sincronização manual é feito ao endpoint de sync, THE Adapter SHALL buscar todos os templates do provedor e atualizar a base de dados local
2. WHEN o provedor envia uma notificação de alteração de template via webhook, THE Adapter SHALL receber e processar a notificação automaticamente
3. WHEN um template é modificado (manual ou via webhook), THE Adapter SHALL registar a alteração na base de dados e notificar o sistema
4. WHEN um template é apagado (manual ou via webhook), THE Adapter SHALL registar a remoção na base de dados e notificar o sistema
5. THE Adapter SHALL validar a autenticidade das notificações recebidas via webhook
6. IF uma notificação inválida é recebida, THEN THE Adapter SHALL rejeitar o pedido e registar a tentativa
7. THE Adapter SHALL suportar sincronização manual para todos os provedores configurados (Infobip, Twilio, etc.)

### Requirement 3: Envio de Mensagens HSM

**User Story:** Como um operador, eu quero enviar mensagens HSM aos clientes, para que eu possa comunicar informações importantes através do WhatsApp.

#### Acceptance Criteria

1. WHEN um pedido de envio de HSM é recebido, THE Adapter SHALL validar todos os parâmetros obrigatórios (número do destinatário, template ID, parâmetros do template)
2. WHEN os parâmetros são válidos, THE Adapter SHALL enviar a mensagem através da API da Infobip
3. WHEN a Infobip confirma o envio, THE Adapter SHALL retornar o ID da mensagem e status de envio
4. IF parâmetros obrigatórios estão em falta, THEN THE Adapter SHALL retornar um erro de validação com detalhes específicos
5. IF a API da Infobip retorna um erro, THEN THE Adapter SHALL retornar uma mensagem de erro descritiva
6. THE Adapter SHALL suportar substituição de parâmetros dinâmicos nos templates

### Requirement 4: Consulta de Estado de Mensagens HSM

**User Story:** Como um operador, eu quero consultar o estado de uma mensagem HSM enviada, para que eu possa saber se foi entregue ao cliente.

#### Acceptance Criteria

1. WHEN um pedido de consulta de estado é feito com um ID de mensagem válido, THE Adapter SHALL recuperar o estado atual da mensagem da API da Infobip
2. THE Adapter SHALL retornar informações de estado (enviada, entregue, lida, falhada) com timestamps
3. IF o ID da mensagem não existe, THEN THE Adapter SHALL retornar um erro 404 com mensagem descritiva
4. WHEN a API da Infobip está indisponível, THE Adapter SHALL retornar um erro de serviço temporariamente indisponível

### Requirement 5: Notificações de Respostas de Clientes

**User Story:** Como um operador, eu quero ser notificado quando um cliente responde a uma mensagem HSM, para que eu possa dar seguimento à conversa.

#### Acceptance Criteria

1. WHEN a Infobip envia uma notificação de resposta de cliente, THE Adapter SHALL receber e processar a mensagem através de um webhook
2. THE Adapter SHALL extrair o conteúdo da mensagem, número do remetente, timestamp e ID da conversa
3. THE Adapter SHALL validar a autenticidade das notificações recebidas da Infobip
4. WHEN uma resposta válida é recebida, THE Adapter SHALL armazenar ou encaminhar a mensagem para o sistema de gestão de conversas
5. IF uma notificação inválida é recebida, THEN THE Adapter SHALL rejeitar o pedido e registar a tentativa

### Requirement 6: Envio de Mensagens de Texto Livre (Não-HSM)

**User Story:** Como um operador, eu quero enviar mensagens de texto livre aos clientes, para que eu possa manter conversas naturais e bidirecionais.

#### Acceptance Criteria

1. WHEN um operador envia uma mensagem de texto livre, THE Adapter SHALL validar todos os parâmetros obrigatórios (número do destinatário, conteúdo da mensagem)
2. WHEN os parâmetros são válidos, THE Adapter SHALL enviar a mensagem através da API da Infobip
3. THE Adapter SHALL suportar diferentes tipos de conteúdo (texto simples, texto formatado, emojis)
4. WHEN a mensagem é enviada com sucesso, THE Adapter SHALL retornar o ID da mensagem e status de envio
5. IF a API da Infobip retorna um erro indicando que a sessão expirou, THEN THE Adapter SHALL retornar um erro específico sugerindo o uso de HSM
6. THE Adapter SHALL permitir envio de mensagens de texto livre independentemente de existir uma sessão ativa (a Infobip gerirá as restrições)

### Requirement 7: Envio de Media (Imagens, Documentos, Áudio, Vídeo)

**User Story:** Como um operador, eu quero enviar diferentes tipos de media aos clientes, para que eu possa partilhar informações visuais e documentos.

#### Acceptance Criteria

1. WHEN um operador envia uma imagem, THE Adapter SHALL validar o formato (JPEG, PNG) e tamanho máximo
2. WHEN um operador envia um documento, THE Adapter SHALL validar o formato (PDF, DOC, DOCX, XLS, XLSX) e tamanho máximo
3. WHEN um operador envia áudio, THE Adapter SHALL validar o formato (MP3, OGG, AMR) e duração máxima
4. WHEN um operador envia vídeo, THE Adapter SHALL validar o formato (MP4, 3GP) e tamanho máximo
5. THE Adapter SHALL suportar envio de media através de URL ou upload direto
6. WHEN media é enviada com sucesso, THE Adapter SHALL retornar o ID da mensagem
7. IF o formato ou tamanho é inválido, THEN THE Adapter SHALL retornar um erro de validação específico

### Requirement 8: Recepção de Mensagens do Cliente para o Operador

**User Story:** Como um operador, eu quero receber todas as mensagens enviadas pelos clientes, para que eu possa responder adequadamente.

#### Acceptance Criteria

1. WHEN a Infobip envia uma notificação de mensagem recebida, THE Adapter SHALL processar a mensagem através de um webhook
2. THE Adapter SHALL extrair todo o conteúdo da mensagem (texto, media, localização, contactos)
3. THE Adapter SHALL identificar o cliente remetente e associar a mensagem à conversa correta
4. THE Adapter SHALL validar a autenticidade das notificações recebidas
5. WHEN uma mensagem válida é recebida, THE Adapter SHALL armazenar ou encaminhar para o sistema de gestão de conversas

### Requirement 9: Envio de Mensagens Interativas (Botões e Listas)

**User Story:** Como um operador, eu quero enviar mensagens com botões ou listas de opções, para que os clientes possam responder de forma estruturada.

#### Acceptance Criteria

1. WHEN um operador envia uma mensagem com botões, THE Adapter SHALL validar que existem no máximo 3 botões
2. WHEN um operador envia uma mensagem com lista, THE Adapter SHALL validar que existem no máximo 10 itens
3. THE Adapter SHALL validar que cada botão ou item tem um ID único e texto descritivo
4. WHEN a mensagem interativa é enviada com sucesso, THE Adapter SHALL retornar o ID da mensagem
5. IF o número de botões ou itens excede o limite, THEN THE Adapter SHALL retornar um erro de validação
6. THE Adapter SHALL suportar botões de resposta rápida e botões de ação (URL, chamada telefónica)

### Requirement 10: Recepção de Respostas Interativas

**User Story:** Como um operador, eu quero receber as respostas dos clientes a mensagens interativas, para que eu possa processar as suas escolhas.

#### Acceptance Criteria

1. WHEN a Infobip envia uma notificação de resposta a botão, THE Adapter SHALL extrair o ID do botão selecionado
2. WHEN a Infobip envia uma notificação de resposta a lista, THE Adapter SHALL extrair o ID do item selecionado
3. THE Adapter SHALL associar a resposta à mensagem original e à conversa
4. THE Adapter SHALL validar a autenticidade das notificações recebidas
5. WHEN uma resposta interativa válida é recebida, THE Adapter SHALL encaminhar para o sistema de gestão de conversas

### Requirement 11: Autenticação e Segurança

**User Story:** Como um administrador do sistema, eu quero garantir que todas as comunicações com a Infobip são seguras e autenticadas, para que os dados dos clientes estejam protegidos.

#### Acceptance Criteria

1. THE Adapter SHALL armazenar credenciais da API da Infobip de forma segura (variáveis de ambiente ou vault)
2. THE Adapter SHALL incluir autenticação em todos os pedidos à API da Infobip
3. THE Adapter SHALL validar assinaturas ou tokens em webhooks recebidos da Infobip
4. THE Adapter SHALL usar HTTPS para todas as comunicações
5. THE Adapter SHALL implementar rate limiting para prevenir abuso dos endpoints

### Requirement 12: Logging e Monitorização

**User Story:** Como um administrador do sistema, eu quero ter logs detalhados de todas as operações, para que eu possa diagnosticar problemas e monitorizar o uso.

#### Acceptance Criteria

1. THE Adapter SHALL registar todos os pedidos recebidos com timestamps e parâmetros
2. THE Adapter SHALL registar todas as respostas da API da Infobip
3. THE Adapter SHALL registar erros com stack traces e contexto suficiente para debugging
4. WHEN erros críticos ocorrem, THE Adapter SHALL notificar os administradores
5. THE Adapter SHALL não registar informações sensíveis (tokens, conteúdo completo de mensagens pessoais)

### Requirement 13: Gestão de Erros e Retry

**User Story:** Como um operador, eu quero que o sistema tente reenviar mensagens automaticamente em caso de falhas temporárias, para que as mensagens importantes não se percam.

#### Acceptance Criteria

1. WHEN a API da Infobip retorna um erro temporário (5xx, timeout), THE Adapter SHALL implementar retry com backoff exponencial
2. THE Adapter SHALL tentar no máximo 3 vezes antes de falhar definitivamente
3. WHEN todas as tentativas falham, THE Adapter SHALL retornar um erro ao cliente com detalhes das tentativas
4. THE Adapter SHALL não fazer retry em erros permanentes (4xx exceto 429)
5. WHEN recebe erro 429 (rate limit), THE Adapter SHALL respeitar o header Retry-After
