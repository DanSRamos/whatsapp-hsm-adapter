// Multi-language support for Admin Panel
const translations = {
  en: {
    // Header
    backToDashboard: "← Back to Dashboard",

    // Navigation
    dashboard: "Dashboard",
    messages: "Messages",
    templates: "Templates",
    rcs: "RCS Messages",
    documentation: "Documentation",
    apiDocs: "API Documentation",
    monitoring: "Monitoring",

    // Dashboard
    dashboardTitle: "Multi-Platform Messaging Adapter",
    dashboardSubtitle:
      "Unified messaging API for WhatsApp, Instagram, and Facebook Messenger",

    // Stats
    totalMessages: "Total Messages",
    successRate: "Success Rate",
    activeTemplates: "Active Templates",
    platforms: "Platforms",

    // Buttons
    sendMessage: "Send Message",
    viewTemplates: "View Templates",
    checkStatus: "Check Status",
    viewDocs: "View Documentation",
    testAPI: "Test API",

    // Messages
    selectPlatform: "Select Platform",
    whatsapp: "WhatsApp",
    instagram: "Instagram",
    messenger: "Messenger",
    recipientNumber: "Recipient Number",
    messageText: "Message Text",
    send: "Send",
    cancel: "Cancel",
    sendMessageDesc:
      "Complete interface to send messages via WhatsApp, Instagram, Messenger and RCS",
    receivedMessages: "Received Messages",
    receivedMessagesDesc:
      "View webhooks and received messages from all platforms",
    templatesDesc: "Manage and use approved templates for WhatsApp",

    // Documentation sections
    rcsMessaging: "RCS Messaging",
    rcsInterface: "RCS Interface",
    rcsInterfaceDesc:
      "Complete interface to send RCS messages with rich cards, carousels and suggestions",
    rcsImplementation: "RCS Implementation",
    rcsImplementationDesc:
      "Technical documentation about RCS implementation via Infobip",
    rcsSetupGuide: "RCS Setup Guide",
    rcsSetupGuideDesc:
      "How to configure RCS credentials and test the integration",
    setupGuides: "Setup Guides",
    instagramSetup: "Instagram Setup",
    instagramSetupDesc:
      "How to configure Meta credentials and connect Instagram Professional Account",
    metaCredentials: "Meta Credentials",
    metaCredentialsDesc:
      "Complete guide to obtain and configure Meta API credentials",
    productionDeployment: "Production Deployment",
    productionDeploymentDesc:
      "Preparation and configuration for production environment",
    technicalDocumentation: "Technical Documentation",
    apiDocInteractive: "API Documentation (Interactive)",
    apiDocInteractiveDesc:
      "Interactive OpenAPI documentation with Swagger UI - test endpoints directly",
    apiDocSimpleTest: "API Documentation (Simple Test)",
    apiDocSimpleTestDesc:
      "Minimalist Swagger UI version for quick testing and debugging",
    apiDocMarkdown: "API Documentation (Markdown)",
    apiDocMarkdownDesc: "Complete REST API reference in text format",
    metaRequestAdapter: "Meta Request Adapter",
    metaRequestAdapterDesc:
      "How the system adapts requests for Instagram and Messenger",
    troubleshooting: "Troubleshooting",
    troubleshootingDesc: "Solutions for common problems and debugging",
    usefulLinks: "Useful Links",
    metaMessengerPlatform: "Meta Messenger Platform",
    metaMessengerPlatformDesc: "Official Meta documentation for Messenger",
    metaInstagramMessaging: "Meta Instagram Messaging",
    metaInstagramMessagingDesc: "Official Meta documentation for Instagram",
    infobipAPI: "Infobip API",
    infobipAPIDesc: "Infobip documentation for WhatsApp",

    // Monitoring
    alertsAndMonitoring: "Alerts and Monitoring",
    completeDashboard: "Complete Dashboard",
    completeDashboardDesc:
      "Access the complete monitoring dashboard with real-time metrics",
    rateLimits: "Rate Limits",
    rateLimitsDesc: "Monitor rate limit usage (hourly and daily)",
    circuitBreaker: "Circuit Breaker",
    circuitBreakerDesc: "Check circuit breaker status and statistics",
    alerts: "Alerts",
    alertsDesc: "View recent alerts and statistics by severity",
    systemHealth: "System Health",
    systemHealthDesc: "Check overall system health",
    performance: "Performance",
    performanceDesc: "Performance metrics and response times",
    monitoringNote: "Note about Monitoring",
    monitoringNoteDesc:
      "The monitoring dashboard requires that the API endpoints are implemented in the backend. See <strong>docs/META_PRODUCTION_DEPLOYMENT.md</strong> for implementation instructions.",

    // Index.html - Main content
    availableTemplates: "Available Templates",
    refreshTemplates: "Refresh Templates",
    sendMessageTitle: "Send Message",
    sendMessageHSM: "Send HSM Message (WhatsApp)",
    sendMessageInstagram: "Send Message (Instagram)",
    sendMessageMessenger: "Send Message (Messenger)",
    receivedMessagesWebhooks: "Received Messages (Webhooks)",
    refreshMessages: "Refresh Messages",
    allProviders: "All Providers",
    loadingTemplates: "Loading templates...",
    loadingMessages: "Loading messages...",
    sendMessageButton: "Send Message",

    // Templates
    templateName: "Template Name",
    templateStatus: "Status",
    templateLanguage: "Language",
    approved: "Approved",
    pending: "Pending",
    rejected: "Rejected",

    // RCS
    rcsTitle: "RCS Messages",
    rcsSubtitle: "Rich Communication Services via Infobip",
    sendRcsMessage: "Send RCS Message",

    // Documentation
    docsTitle: "Documentation",
    docsSubtitle: "Complete API documentation and guides",
    quickStart: "Quick Start",
    apiReference: "API Reference",
    examples: "Examples",

    // API Docs
    apiDocsTitle: "API Documentation",
    apiDocsSubtitle:
      "Interactive API documentation for Multi-Platform Messaging Adapter",
    interactiveAPI: "Interactive API",
    information: "Information",
    aboutAPI: "About the API",
    supportedPlatforms: "Supported Platforms",
    authentication: "Authentication",
    rateLimiting: "Rate Limiting",
    additionalResources: "Additional Resources",
    downloadSpec: "Download OpenAPI Spec",
    completeDocumentation: "Complete Documentation",
    quickStartGuide: "Quick Start Guide",
    simpleVersion: "Simple Version",
    simpleVersionDesc:
      "Minimalist Swagger UI interface for quick testing and debugging. Ideal when you need to test endpoints quickly without the full interface.",
    generateClientLibs: "Generate Client Libraries",
    usageExamples: "Usage Examples",

    // Monitoring
    monitoringTitle: "Monitoring Dashboard",
    monitoringSubtitle: "Real-time monitoring and metrics",
    metaMetrics: "Meta Metrics",
    errorDashboard: "Error Dashboard",
    performanceDashboard: "Performance Dashboard",

    // Dashboard Pages
    metricsDashboardTitle: "Meta Metrics Dashboard",
    metricsDashboardSubtitle:
      "Real-time monitoring of Instagram and Facebook Messenger",
    errorsDashboardTitle: "Errors Dashboard",
    errorsDashboardSubtitle: "Meta API error monitoring and analysis",
    performanceDashboardTitle: "Performance Dashboard",
    performanceDashboardSubtitle: "System performance and health monitoring",
    monitoringDashboardTitle: "Alerts and Monitoring",

    // Time periods
    lastHour: "Last Hour",
    lastDay: "Last Day",
    lastWeek: "Last Week",
    update: "Update",

    // Platforms
    allPlatforms: "All Platforms",

    // Metrics
    successRate: "Success Rate",
    responseTime: "Response Time",
    messagesSent: "Messages Sent",
    webhooksReceived: "Webhooks Received",
    window24hErrors: "24h Window Errors",
    systemStatus: "System Status",
    healthy: "Healthy",
    degraded: "Degraded",
    unhealthy: "Unhealthy",
    loadingMetrics: "Loading metrics...",
    lastUpdated: "Last updated",

    // Charts
    successRateByPlatform: "Success Rate by Platform",
    responseTimePercentiles: "Response Time (Percentiles)",
    top10Errors: "Top 10 Errors",
    webhooksByType: "Webhooks by Type",
    chartWillDisplay: "Chart will be displayed here",

    // Errors
    totalErrors: "Total Errors",
    errorTypes: "Error Types",
    windowErrors: "24h Window Errors",
    errorRate: "Error Rate",
    topErrorsByCode: "Top Errors by Code",
    messagingWindowErrors: "Messaging Window Errors (24h)",
    code: "Code",
    description: "Description",
    count: "Count",
    platform: "Platform",
    period: "Period",
    noErrorsRecorded: "No errors recorded in the selected period!",
    errorLoadingData: "Error loading data",

    // Performance
    responseTimeInstagram: "Response Time - Instagram",
    responseTimeMessenger: "Response Time - Messenger",
    average: "Average",
    minimum: "Minimum",
    maximum: "Maximum",
    median: "Median (P50)",
    circuitBreakerStatus: "Circuit Breaker Status",
    rateLimiting: "Rate Limiting",
    hourlyUsage: "Hourly Usage",
    dailyUsage: "Daily Usage",
    used: "Used",
    limit: "Limit",
    remaining: "Remaining",
    recentAlerts: "Recent Alerts (Last Hour)",
    failures: "Failures",
    successes: "Successes",
    state: "State",
    closed: "CLOSED",
    open: "OPEN",
    halfOpen: "HALF-OPEN",

    // Errors
    errorOccurred: "An error occurred",
    tryAgain: "Try Again",
    errorDetails: "Error Details",

    // Success
    messageSent: "Message sent successfully",
    operationSuccess: "Operation completed successfully",

    // Common
    loading: "Loading...",
    search: "Search",
    filter: "Filter",
    export: "Export",
    refresh: "Refresh",
    settings: "Settings",
    help: "Help",
    logout: "Logout",
    language: "Language",

    // Footer
    version: "Version",
    documentation: "Documentation",
    support: "Support",

    // Endpoints Categories
    healthCheck: "Health Check",
    whatsappTemplates: "WhatsApp Templates",
    validation: "Validation",
    webhooks: "Webhooks",
    metrics: "Metrics",

    // Descriptions
    healthCheckDesc: "Service health verification and dependencies",
    templatesDesc: "WhatsApp message template management",
    messagesDesc: "Send messages across all platforms",
    validationDesc: "WhatsApp number validation",
    webhooksDesc: "Receive notifications from providers",
    metricsDesc: "Monitoring and performance metrics",
  },

  pt: {
    // Header
    backToDashboard: "← Voltar ao Dashboard",

    // Navigation
    dashboard: "Dashboard",
    messages: "Mensagens",
    templates: "Templates",
    rcs: "Mensagens RCS",
    documentation: "Documentação",
    apiDocs: "Documentação API",
    monitoring: "Monitorização",

    // Dashboard
    dashboardTitle: "Adaptador de Mensagens Multi-Plataforma",
    dashboardSubtitle:
      "API unificada de mensagens para WhatsApp, Instagram e Facebook Messenger",

    // Stats
    totalMessages: "Total de Mensagens",
    successRate: "Taxa de Sucesso",
    activeTemplates: "Templates Ativos",
    platforms: "Plataformas",

    // Buttons
    sendMessage: "Enviar Mensagem",
    viewTemplates: "Ver Templates",
    checkStatus: "Verificar Estado",
    viewDocs: "Ver Documentação",
    testAPI: "Testar API",

    // Messages
    selectPlatform: "Selecionar Plataforma",
    whatsapp: "WhatsApp",
    instagram: "Instagram",
    messenger: "Messenger",
    recipientNumber: "Número do Destinatário",
    messageText: "Texto da Mensagem",
    send: "Enviar",
    cancel: "Cancelar",
    sendMessageDesc:
      "Interface completa para enviar mensagens via WhatsApp, Instagram, Messenger e RCS",
    receivedMessages: "Mensagens Recebidas",
    receivedMessagesDesc:
      "Visualizar webhooks e mensagens recebidas de todas as plataformas",
    templatesDesc: "Gerir e usar templates aprovados para WhatsApp",

    // Documentation sections
    rcsMessaging: "Mensagens RCS",
    rcsInterface: "Interface RCS",
    rcsInterfaceDesc:
      "Interface completa para enviar mensagens RCS com rich cards, carrosséis e sugestões",
    rcsImplementation: "Implementação RCS",
    rcsImplementationDesc:
      "Documentação técnica sobre implementação RCS via Infobip",
    rcsSetupGuide: "Guia de Configuração RCS",
    rcsSetupGuideDesc: "Como configurar credenciais RCS e testar a integração",
    setupGuides: "Guias de Configuração",
    instagramSetup: "Configuração Instagram",
    instagramSetupDesc:
      "Como configurar credenciais Meta e conectar Instagram Professional Account",
    metaCredentials: "Credenciais Meta",
    metaCredentialsDesc:
      "Guia completo para obter e configurar credenciais da Meta API",
    productionDeployment: "Deployment em Produção",
    productionDeploymentDesc:
      "Preparação e configuração para ambiente de produção",
    technicalDocumentation: "Documentação Técnica",
    apiDocInteractive: "Documentação API (Interativa)",
    apiDocInteractiveDesc:
      "Documentação interativa OpenAPI com Swagger UI - teste os endpoints diretamente",
    apiDocSimpleTest: "Documentação API (Teste Simples)",
    apiDocSimpleTestDesc:
      "Versão minimalista do Swagger UI para testes rápidos e debugging",
    apiDocMarkdown: "Documentação API (Markdown)",
    apiDocMarkdownDesc: "Referência completa da API REST em formato texto",
    metaRequestAdapter: "Meta Request Adapter",
    metaRequestAdapterDesc:
      "Como o sistema adapta requests para Instagram e Messenger",
    troubleshooting: "Resolução de Problemas",
    troubleshootingDesc: "Soluções para problemas comuns e debugging",
    usefulLinks: "Links Úteis",
    metaMessengerPlatform: "Meta Messenger Platform",
    metaMessengerPlatformDesc: "Documentação oficial da Meta para Messenger",
    metaInstagramMessaging: "Meta Instagram Messaging",
    metaInstagramMessagingDesc: "Documentação oficial da Meta para Instagram",
    infobipAPI: "Infobip API",
    infobipAPIDesc: "Documentação da Infobip para WhatsApp",

    // Monitoring
    alertsAndMonitoring: "Alertas e Monitoramento",
    completeDashboard: "Dashboard Completo",
    completeDashboardDesc:
      "Aceder ao dashboard completo de monitoramento com métricas em tempo real",
    rateLimits: "Rate Limits",
    rateLimitsDesc: "Monitorar uso de rate limits (horário e diário)",
    circuitBreaker: "Circuit Breaker",
    circuitBreakerDesc: "Verificar estado do circuit breaker e estatísticas",
    alerts: "Alertas",
    alertsDesc: "Ver alertas recentes e estatísticas por severidade",
    systemHealth: "Saúde do Sistema",
    systemHealthDesc: "Verificar saúde geral do sistema",
    performance: "Performance",
    performanceDesc: "Métricas de performance e tempos de resposta",
    monitoringNote: "Nota sobre Monitoramento",
    monitoringNoteDesc:
      "O dashboard de monitoramento requer que os endpoints da API estejam implementados no backend. Consulte <strong>docs/META_PRODUCTION_DEPLOYMENT.md</strong> para instruções de implementação.",

    // Index.html - Main content
    availableTemplates: "Templates Disponíveis",
    refreshTemplates: "Atualizar Templates",
    sendMessageTitle: "Enviar Mensagem",
    sendMessageHSM: "Enviar Mensagem HSM (WhatsApp)",
    sendMessageInstagram: "Enviar Mensagem (Instagram)",
    sendMessageMessenger: "Enviar Mensagem (Messenger)",
    receivedMessagesWebhooks: "Mensagens Recebidas (Webhooks)",
    refreshMessages: "Atualizar Mensagens",
    allProviders: "Todos os Providers",
    loadingTemplates: "Carregando templates...",
    loadingMessages: "Carregando mensagens...",
    sendMessageButton: "Enviar Mensagem",

    // Templates
    templateName: "Nome do Template",
    templateStatus: "Estado",
    templateLanguage: "Idioma",
    approved: "Aprovado",
    pending: "Pendente",
    rejected: "Rejeitado",

    // RCS
    rcsTitle: "Mensagens RCS",
    rcsSubtitle: "Rich Communication Services via Infobip",
    sendRcsMessage: "Enviar Mensagem RCS",

    // Documentation
    docsTitle: "Documentação",
    docsSubtitle: "Documentação completa da API e guias",
    quickStart: "Início Rápido",
    apiReference: "Referência API",
    examples: "Exemplos",

    // API Docs
    apiDocsTitle: "Documentação API",
    apiDocsSubtitle:
      "Documentação interativa da API Multi-Platform Messaging Adapter",
    interactiveAPI: "API Interativa",
    information: "Informações",
    aboutAPI: "Sobre a API",
    supportedPlatforms: "Plataformas Suportadas",
    authentication: "Autenticação",
    rateLimiting: "Rate Limiting",
    additionalResources: "Recursos Adicionais",
    downloadSpec: "Download OpenAPI Spec",
    completeDocumentation: "Documentação Completa",
    quickStartGuide: "Guia de Início Rápido",
    simpleVersion: "Versão Simples",
    simpleVersionDesc:
      "Interface minimalista do Swagger UI para testes rápidos e debugging. Ideal quando precisas de testar endpoints rapidamente sem a interface completa.",
    generateClientLibs: "Gerar Bibliotecas Cliente",
    usageExamples: "Exemplos de Uso",

    // Monitoring
    monitoringTitle: "Dashboard de Monitorização",
    monitoringSubtitle: "Monitorização e métricas em tempo real",
    metaMetrics: "Métricas Meta",
    errorDashboard: "Dashboard de Erros",
    performanceDashboard: "Dashboard de Performance",

    // Dashboard Pages
    metricsDashboardTitle: "Dashboard de Métricas Meta",
    metricsDashboardSubtitle:
      "Monitoramento em tempo real do Instagram e Facebook Messenger",
    errorsDashboardTitle: "Dashboard de Erros",
    errorsDashboardSubtitle: "Monitoramento e análise de erros da Meta API",
    performanceDashboardTitle: "Dashboard de Performance",
    performanceDashboardSubtitle:
      "Monitoramento de performance e saúde do sistema",
    monitoringDashboardTitle: "Alertas e Monitoramento",

    // Time periods
    lastHour: "Última Hora",
    lastDay: "Último Dia",
    lastWeek: "Última Semana",
    update: "Atualizar",

    // Platforms
    allPlatforms: "Todas as Plataformas",

    // Metrics
    successRate: "Taxa de Sucesso",
    responseTime: "Tempo de Resposta",
    messagesSent: "Mensagens Enviadas",
    webhooksReceived: "Webhooks Recebidos",
    window24hErrors: "Erros de Janela 24h",
    systemStatus: "Status do Sistema",
    healthy: "Saudável",
    degraded: "Degradado",
    unhealthy: "Não Saudável",
    loadingMetrics: "Carregando métricas...",
    lastUpdated: "Última atualização",

    // Charts
    successRateByPlatform: "Taxa de Sucesso por Plataforma",
    responseTimePercentiles: "Tempo de Resposta (Percentis)",
    top10Errors: "Top 10 Erros",
    webhooksByType: "Webhooks por Tipo",
    chartWillDisplay: "Gráfico será exibido aqui",

    // Errors
    totalErrors: "Total de Erros",
    errorTypes: "Tipos de Erro",
    windowErrors: "Erros de Janela 24h",
    errorRate: "Taxa de Erro",
    topErrorsByCode: "Top Erros por Código",
    messagingWindowErrors: "Erros de Janela de Mensagens (24h)",
    code: "Código",
    description: "Descrição",
    count: "Contagem",
    platform: "Plataforma",
    period: "Período",
    noErrorsRecorded: "Nenhum erro registrado no período selecionado!",
    errorLoadingData: "Erro ao carregar dados",

    // Performance
    responseTimeInstagram: "Tempo de Resposta - Instagram",
    responseTimeMessenger: "Tempo de Resposta - Messenger",
    average: "Média",
    minimum: "Mínimo",
    maximum: "Máximo",
    median: "Mediana (P50)",
    circuitBreakerStatus: "Status do Circuit Breaker",
    rateLimiting: "Rate Limiting",
    hourlyUsage: "Uso por Hora",
    dailyUsage: "Uso Diário",
    used: "Usado",
    limit: "Limite",
    remaining: "Restante",
    recentAlerts: "Alertas Recentes (Última Hora)",
    failures: "Falhas",
    successes: "Sucessos",
    state: "Estado",
    closed: "FECHADO",
    open: "ABERTO",
    halfOpen: "MEIO-ABERTO",

    // Errors
    errorOccurred: "Ocorreu um erro",
    tryAgain: "Tentar Novamente",
    errorDetails: "Detalhes do Erro",

    // Success
    messageSent: "Mensagem enviada com sucesso",
    operationSuccess: "Operação concluída com sucesso",

    // Common
    loading: "A carregar...",
    search: "Pesquisar",
    filter: "Filtrar",
    export: "Exportar",
    refresh: "Atualizar",
    settings: "Definições",
    help: "Ajuda",
    logout: "Sair",
    language: "Idioma",

    // Footer
    version: "Versão",
    documentation: "Documentação",
    support: "Suporte",

    // Endpoints Categories
    healthCheck: "Health Check",
    whatsappTemplates: "Templates WhatsApp",
    validation: "Validação",
    webhooks: "Webhooks",
    metrics: "Métricas",

    // Descriptions
    healthCheckDesc: "Verificação de saúde do serviço e dependências",
    templatesDesc: "Gestão de templates de mensagens WhatsApp",
    messagesDesc: "Envio de mensagens em todas as plataformas",
    validationDesc: "Validação de números WhatsApp",
    webhooksDesc: "Recebimento de notificações dos provedores",
    metricsDesc: "Monitoramento e métricas de performance",
  },
};

// Language Manager
class LanguageManager {
  constructor() {
    this.currentLang = this.getStoredLanguage() || "en";
    this.translations = translations;
  }

  getStoredLanguage() {
    return localStorage.getItem("adminPanelLanguage");
  }

  setLanguage(lang) {
    if (this.translations[lang]) {
      this.currentLang = lang;
      localStorage.setItem("adminPanelLanguage", lang);
      this.updatePageContent();
      // Dispatch event for other components
      window.dispatchEvent(
        new CustomEvent("languageChanged", { detail: { language: lang } }),
      );
    }
  }

  t(key) {
    return this.translations[this.currentLang][key] || key;
  }

  updatePageContent() {
    // Update all elements with data-i18n attribute
    document.querySelectorAll("[data-i18n]").forEach((element) => {
      const key = element.getAttribute("data-i18n");
      const translation = this.t(key);

      if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
        element.placeholder = translation;
      } else {
        element.textContent = translation;
      }
    });

    // Update all elements with data-i18n-html attribute (for HTML content)
    document.querySelectorAll("[data-i18n-html]").forEach((element) => {
      const key = element.getAttribute("data-i18n-html");
      element.innerHTML = this.t(key);
    });

    // Update title if exists
    const titleElement = document.querySelector("[data-i18n-title]");
    if (titleElement) {
      const key = titleElement.getAttribute("data-i18n-title");
      document.title = this.t(key);
    }
  }

  getCurrentLanguage() {
    return this.currentLang;
  }

  getAvailableLanguages() {
    return Object.keys(this.translations);
  }
}

// Initialize global language manager
const i18n = new LanguageManager();

// Auto-update content when DOM is loaded
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => i18n.updatePageContent());
} else {
  i18n.updatePageContent();
}
