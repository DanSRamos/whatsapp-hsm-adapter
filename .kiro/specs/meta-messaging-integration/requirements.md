# Requirements Document: Meta Messaging Integration (Instagram + Facebook Messenger)

## Introduction

Este documento define os requisitos para integração do Instagram Messaging API e Facebook Messenger API ao WhatsApp HSM Adapter existente. A integração permitirá enviar e receber mensagens do Instagram e Facebook Messenger usando a mesma arquitetura modular de providers.

## Glossary

- **Meta_Provider**: Implementação do provider para Instagram e Facebook Messenger APIs
- **IGSID**: Instagram-Scoped ID - identificador único do usuário no Instagram
- **PSID**: Page-Scoped ID - identificador único do usuário no Facebook Messenger
- **Meta_Graph_API**: API da Meta (Facebook) para acesso ao Instagram e Messenger
- **Page_Access_Token**: Token de autenticação para acesso à Facebook Page
- **Quick_Reply**: Botões de resposta rápida no Instagram e Messenger
- **Generic_Template**: Template de mensagem com cards e botões
- **Button_Template**: Template de mensagem com botões (Messenger)
- **Messaging_Window**: Janela de 24 horas para responder mensagens
- **Webhook**: Endpoint HTTP para receber notificações do Instagram e Messenger
- **HSM_Template**: High Structure Message template (não suportado no Instagram/Messenger)
- **Messenger_Platform**: Plataforma unificada da Meta para Instagram e Facebook Messenger

## Requirements

### Requirement 1: Configuração e Autenticação

**User Story:** Como desenvolvedor, quero configurar as credenciais do Meta Messaging API (Instagram + Messenger), para que o sistema possa autenticar e enviar mensagens.

#### Acceptance Criteria

1. THE System SHALL aceitar configuração de Page Access Token via variável de ambiente
2. THE System SHALL aceitar configuração de App ID via variável de ambiente
3. THE System SHALL aceitar configuração de App Secret via variável de ambiente
4. THE System SHALL aceitar configuração de Page ID via variável de ambiente
5. THE System SHALL suportar múltiplas Pages (Instagram e Messenger na mesma Page)
6. WHEN credenciais estão ausentes, THEN THE System SHALL lançar exceção descritiva
7. THE System SHALL validar formato do Page Access Token antes de usar
8. THE System SHALL incluir Page Access Token em todos os requests à API
9. THE System SHALL detectar automaticamente se mensagem é para Instagram ou Messenger baseado no identificador (IGSID vs PSID)

### Requirement 2: Envio de Mensagens de Texto

**User Story:** Como usuário, quero enviar mensagens de texto para usuários do Instagram e Facebook Messenger, para que eu possa me comunicar com clientes em ambas as plataformas.

#### Acceptance Criteria

1. WHEN uma mensagem de texto é enviada, THE Meta_Provider SHALL fazer POST para `/{page-id}/messages`
2. THE Meta_Provider SHALL incluir IGSID ou PSID do destinatário no payload
3. THE Meta_Provider SHALL incluir texto da mensagem no payload
4. WHEN envio é bem-sucedido, THEN THE System SHALL retornar message_id
5. WHEN envio falha, THEN THE System SHALL retornar erro descritivo
6. THE System SHALL suportar preview de URLs em mensagens de texto
7. THE System SHALL validar que texto não está vazio antes de enviar
8. THE System SHALL suportar mensagens de texto para Instagram e Messenger com o mesmo código
9. THE System SHALL identificar automaticamente a plataforma (Instagram/Messenger) pelo formato do ID

### Requirement 3: Envio de Mídia

**User Story:** Como usuário, quero enviar imagens, vídeos, áudio e documentos para usuários do Instagram e Facebook Messenger, para que eu possa compartilhar conteúdo rico em ambas as plataformas.

#### Acceptance Criteria

1. THE Meta_Provider SHALL suportar envio de imagens (PNG, JPEG) para Instagram e Messenger
2. THE Meta_Provider SHALL suportar envio de vídeos (MP4, OGG, AVI, MOV, WEBM) para Instagram e Messenger
3. THE Meta_Provider SHALL suportar envio de áudio (AAC, M4A, WAV, MP4) para Instagram e Messenger
4. THE Meta_Provider SHALL suportar envio de documentos (PDF) para Instagram e Messenger
5. WHEN imagem é enviada, THE System SHALL validar tamanho máximo de 8MB (Instagram) ou 25MB (Messenger)
6. WHEN vídeo/áudio é enviado, THE System SHALL validar tamanho máximo de 25MB
7. WHEN documento é enviado, THE System SHALL validar tamanho máximo de 25MB
8. THE Meta_Provider SHALL suportar envio de até 10 imagens em uma mensagem (Instagram)
9. THE Meta_Provider SHALL suportar envio de 1 imagem por mensagem (Messenger padrão)
10. WHEN formato de mídia não é suportado, THEN THE System SHALL retornar erro
11. THE System SHALL incluir URL da mídia no payload
12. THE System SHALL detectar automaticamente limites baseado na plataforma (Instagram vs Messenger)

### Requirement 4: Mensagens Interativas

**User Story:** Como usuário, quero enviar mensagens com botões de resposta rápida e templates, para que usuários possam interagir facilmente no Instagram e Messenger.

#### Acceptance Criteria

1. THE Meta_Provider SHALL suportar envio de Quick Replies para Instagram e Messenger
2. THE System SHALL validar máximo de 13 Quick Replies por mensagem
3. WHEN Quick Reply é enviado, THE System SHALL incluir title e payload
4. THE Meta_Provider SHALL suportar envio de Generic Template para Instagram e Messenger
5. WHEN Generic Template é enviado, THE System SHALL suportar múltiplos cards
6. THE System SHALL suportar imagens em cards do Generic Template
7. THE System SHALL suportar botões em cards do Generic Template
8. THE Meta_Provider SHALL suportar Button Template (específico Messenger)
9. THE Meta_Provider SHALL suportar botões de URL, postback e call (Messenger)
10. THE System SHALL validar limites de botões por plataforma (3 para Messenger, 13 quick replies)

### Requirement 5: Adaptação de Templates HSM

**User Story:** Como desenvolvedor, quero que templates HSM sejam convertidos para mensagens de texto, para manter compatibilidade com a interface existente no Instagram e Messenger.

#### Acceptance Criteria

1. WHEN template HSM é enviado via Instagram ou Messenger, THE System SHALL converter para texto simples
2. THE System SHALL substituir placeholders {{1}}, {{2}}, etc. pelos parâmetros fornecidos
3. THE System SHALL adicionar warning no log sobre templates não suportados
4. WHEN conversão de template falha, THEN THE System SHALL retornar erro descritivo
5. THE System SHALL manter estrutura do texto após substituição de placeholders
6. THE System SHALL funcionar identicamente para Instagram e Messenger

### Requirement 6: Recebimento de Mensagens via Webhook

**User Story:** Como sistema, quero receber notificações de mensagens recebidas via webhook do Instagram e Messenger, para que eu possa processar mensagens de usuários de ambas as plataformas.

#### Acceptance Criteria

1. THE System SHALL validar webhooks usando X-Hub-Signature-256 header
2. THE System SHALL calcular HMAC SHA-256 usando App Secret
3. WHEN signature é inválida, THEN THE System SHALL rejeitar webhook
4. THE System SHALL responder verificação GET inicial com hub.challenge
5. WHEN hub.verify_token é inválido, THEN THE System SHALL rejeitar verificação
6. THE System SHALL processar mensagens de texto recebidas do Instagram e Messenger
7. THE System SHALL processar mensagens de mídia recebidas do Instagram e Messenger
8. THE System SHALL processar respostas de Quick Reply de ambas as plataformas
9. THE System SHALL extrair IGSID (Instagram) ou PSID (Messenger) do remetente
10. THE System SHALL mapear payload para IncomingMessage model
11. THE System SHALL identificar automaticamente a plataforma de origem (Instagram vs Messenger)
12. THE System SHALL incluir metadata da plataforma na mensagem processada

### Requirement 7: Delivery Reports

**User Story:** Como usuário, quero saber o status de entrega das minhas mensagens, para que eu possa confirmar que foram recebidas.

#### Acceptance Criteria

1. THE System SHALL processar webhooks de delivery reports
2. THE System SHALL identificar status: sent, delivered, read
3. WHEN delivery report é recebido, THE System SHALL atualizar status no repositório
4. THE System SHALL extrair timestamps de entrega e leitura
5. THE System SHALL mapear payload para DeliveryReport model
6. WHEN message_id não existe, THE System SHALL registrar warning no log

### Requirement 8: Consulta de Status de Mensagem

**User Story:** Como usuário, quero consultar o status de uma mensagem enviada, para que eu possa verificar se foi entregue.

#### Acceptance Criteria

1. WHEN status de mensagem é consultado, THE System SHALL buscar no repositório local
2. THE System SHALL retornar último status conhecido
3. WHEN mensagem não existe, THEN THE System SHALL lançar exceção
4. THE System SHALL incluir timestamps de envio, entrega e leitura
5. WHEN status é desconhecido após timeout, THE System SHALL retornar status UNKNOWN

### Requirement 9: Validação de Janela de Mensagens

**User Story:** Como sistema, quero validar a janela de 24 horas antes de enviar mensagens, para evitar erros da API.

#### Acceptance Criteria

1. THE System SHALL verificar timestamp da última mensagem do usuário
2. WHEN última mensagem foi há mais de 24 horas, THE System SHALL retornar erro
3. THE System SHALL incluir tempo restante na mensagem de erro
4. THE System SHALL permitir envio dentro da janela de 24 horas
5. WHEN janela expirou, THE System SHALL sugerir uso de tags (se aplicável)

### Requirement 10: Tratamento de Erros Específicos

**User Story:** Como desenvolvedor, quero que erros específicos do Instagram sejam tratados adequadamente, para facilitar debugging.

#### Acceptance Criteria

1. WHEN erro 36103 ocorre, THE System SHALL retornar mensagem "Conta não elegível para mensagens"
2. WHEN erro 2534068 ocorre, THE System SHALL retornar mensagem "Feature não disponível"
3. WHEN erro de rate limit ocorre, THE System SHALL implementar retry com backoff
4. THE System SHALL registrar todos os erros da API no log
5. THE System SHALL incluir trace_id do Facebook nos logs de erro
6. WHEN erro é transiente, THE System SHALL marcar como is_transient

### Requirement 11: Integração com Provider Factory

**User Story:** Como desenvolvedor, quero que o Instagram Provider seja integrado ao factory existente, para manter consistência arquitetural.

#### Acceptance Criteria

1. THE WhatsAppProviderFactory SHALL suportar criação de Instagram_Provider
2. WHEN provider 'instagram' é solicitado, THE Factory SHALL retornar InstagramProvider
3. THE Factory SHALL validar configurações Instagram antes de criar provider
4. WHEN configurações são inválidas, THEN THE Factory SHALL lançar exceção
5. THE Factory SHALL passar HttpClient, config e logger para Instagram_Provider

### Requirement 12: Compatibilidade com MessageService

**User Story:** Como desenvolvedor, quero que o Instagram Provider funcione com MessageService existente, para reutilizar lógica de negócio.

#### Acceptance Criteria

1. THE Instagram_Provider SHALL implementar todos os métodos de WhatsAppProviderInterface
2. THE MessageService SHALL funcionar com Instagram_Provider sem modificações
3. THE System SHALL persistir mensagens Instagram no mesmo repositório
4. THE System SHALL diferenciar mensagens por provider no metadata
5. THE System SHALL aplicar retry logic para Instagram da mesma forma que WhatsApp

### Requirement 13: Admin Panel Multi-Provider

**User Story:** Como usuário, quero selecionar entre WhatsApp, Instagram e Facebook Messenger no admin panel, para gerenciar mensagens de todas as plataformas.

#### Acceptance Criteria

1. THE Admin_Panel SHALL exibir dropdown para selecionar provider (WhatsApp/Instagram/Messenger)
2. WHEN Instagram é selecionado, THE Admin_Panel SHALL ocultar campo de templates
3. WHEN Messenger é selecionado, THE Admin_Panel SHALL ocultar campo de templates
4. WHEN Instagram é selecionado, THE Admin_Panel SHALL mostrar campo IGSID
5. WHEN Messenger é selecionado, THE Admin_Panel SHALL mostrar campo PSID
6. WHEN Instagram é selecionado, THE Admin_Panel SHALL permitir até 10 imagens
7. WHEN Messenger é selecionado, THE Admin_Panel SHALL permitir 1 imagem (ou múltiplas via carousel)
8. THE Admin_Panel SHALL exibir aviso sobre janela de 24 horas para Instagram e Messenger
9. THE Admin_Panel SHALL filtrar mensagens por provider (WhatsApp/Instagram/Messenger)
10. THE Admin_Panel SHALL exibir ícone/badge diferente por provider
11. WHEN mensagem Instagram é exibida, THE Admin_Panel SHALL mostrar IGSID
12. WHEN mensagem Messenger é exibida, THE Admin_Panel SHALL mostrar PSID
13. THE Admin_Panel SHALL suportar envio de Button Template (específico Messenger)

### Requirement 14: Logging e Monitoramento

**User Story:** Como operador, quero logs detalhados e métricas de Instagram e Messenger, para monitorar saúde do sistema.

#### Acceptance Criteria

1. THE System SHALL registrar todas as chamadas à Meta API (Instagram e Messenger)
2. THE System SHALL registrar tempo de resposta de cada request
3. THE System SHALL registrar erros com stack trace completo
4. THE System SHALL expor métricas de taxa de sucesso de envio por plataforma
5. THE System SHALL expor métricas de erros por tipo e plataforma
6. THE System SHALL expor métricas de webhooks recebidos por plataforma
7. THE System SHALL alertar quando rate limit é atingido
8. THE System SHALL diferenciar métricas entre Instagram e Messenger

### Requirement 15: Documentação

**User Story:** Como desenvolvedor, quero documentação completa sobre Instagram e Messenger integration, para facilitar setup e manutenção.

#### Acceptance Criteria

1. THE System SHALL incluir guia de setup de credenciais Meta
2. THE System SHALL incluir guia de configuração de Facebook Page
3. THE System SHALL incluir guia de conexão Instagram Professional Account
4. THE System SHALL incluir guia de configuração Facebook Messenger
5. THE System SHALL incluir guia de configuração de webhooks
6. THE System SHALL incluir lista de permissões necessárias
7. THE System SHALL incluir exemplos de uso da API para Instagram e Messenger
8. THE System SHALL incluir guia de troubleshooting
9. THE System SHALL incluir tabela comparativa WhatsApp vs Instagram vs Messenger
