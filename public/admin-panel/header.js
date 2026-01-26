// Shared header component for all admin panel pages
function renderHeader(currentPage) {
  const header = document.createElement("div");
  header.className = "app-header";

  header.innerHTML = `
    <div class="header-container">
      <div class="header-top">
        <button onclick="goBack()" class="back-btn" title="Voltar">
          ← Voltar
        </button>
        <div class="header-title">
          <h1>📱 Multi-Platform Messaging Admin Panel</h1>
          <div class="header-subtitle">Gerir mensagens via WhatsApp, Instagram e Facebook Messenger</div>
        </div>
      </div>
      
      <nav class="header-tabs">
        <a href="index.html" class="tab ${
          currentPage === "messages" ? "active" : ""
        }">
          💬 Mensagens
        </a>
        <a href="metrics-dashboard.html" class="tab ${
          currentPage === "metrics" ? "active" : ""
        }">
          📊 Métricas
        </a>
        <a href="performance-dashboard.html" class="tab ${
          currentPage === "performance" ? "active" : ""
        }">
          ⚡ Performance
        </a>
        <a href="errors-dashboard.html" class="tab ${
          currentPage === "errors" ? "active" : ""
        }">
          ❌ Erros
        </a>
        <a href="documentation.html" class="tab ${
          currentPage === "documentation" ? "active" : ""
        }">
          📚 Documentação
        </a>
        <a href="monitoring.html" class="tab ${
          currentPage === "monitoring" ? "active" : ""
        }">
          🚨 Alertas
        </a>
      </nav>
    </div>
  `;

  return header;
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = "index.html";
  }
}

// Initialize header on page load
document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const currentPage = body.getAttribute("data-page") || "messages";
  const header = renderHeader(currentPage);

  // Insert header as first child of body
  body.insertBefore(header, body.firstChild);
});
