// Shared header component for all admin panel pages
function renderHeader(currentPage) {
  const header = document.createElement("div");
  header.className = "app-header";

  header.innerHTML = `
    <div class="header-container">
      <div class="header-top">
        <button onclick="goBack()" class="back-btn" title="Voltar">
          <span data-i18n="backToDashboard">← Back to Dashboard</span>
        </button>
        <div class="header-title">
          <h1 data-i18n="dashboardTitle">📱 Multi-Platform Messaging Adapter</h1>
          <div class="header-subtitle" data-i18n="dashboardSubtitle">Unified messaging API for WhatsApp, Instagram, and Facebook Messenger</div>
        </div>
      </div>
      
      <nav class="header-tabs">
        <a href="#" onclick="switchTab(event, 'messages')" class="tab ${
          currentPage === "messages" ? "active" : ""
        }" data-tab="messages">
          💬 <span data-i18n="messages">Messages</span>
        </a>
        <a href="#" onclick="switchTab(event, 'metrics')" class="tab ${
          currentPage === "metrics" ? "active" : ""
        }" data-tab="metrics">
          📊 <span data-i18n="metrics">Metrics</span>
        </a>
        <a href="#" onclick="switchTab(event, 'performance')" class="tab ${
          currentPage === "performance" ? "active" : ""
        }" data-tab="performance">
          ⚡ <span data-i18n="performance">Performance</span>
        </a>
        <a href="#" onclick="switchTab(event, 'errors')" class="tab ${
          currentPage === "errors" ? "active" : ""
        }" data-tab="errors">
          ❌ <span data-i18n="alerts">Alerts</span>
        </a>
        <a href="#" onclick="switchTab(event, 'monitoring')" class="tab ${
          currentPage === "monitoring" ? "active" : ""
        }" data-tab="monitoring">
          🚨 <span data-i18n="monitoring">Monitoring</span>
        </a>
      </nav>
    </div>
  `;

  return header;
}

function goBack() {
  // Always redirect to index-tabs.html (main dashboard)
  window.location.href = "index-tabs.html";
}

// Function to switch tabs without page navigation
function switchTab(event, tabName) {
  event.preventDefault();

  // Update active tab in header
  document.querySelectorAll(".header-tabs .tab").forEach((tab) => {
    tab.classList.remove("active");
  });
  event.currentTarget.classList.add("active");

  // Hide all tab contents
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.style.display = "none";
  });

  // Show selected tab content
  const selectedContent = document.getElementById(`${tabName}-content`);
  if (selectedContent) {
    selectedContent.style.display = "block";
  }

  // Load content dynamically if needed
  if (tabName === "metrics" && typeof loadMetricsContent === "function") {
    loadMetricsContent();
  } else if (
    tabName === "performance" &&
    typeof loadPerformanceContent === "function"
  ) {
    loadPerformanceContent();
  } else if (tabName === "errors" && typeof loadErrorsContent === "function") {
    loadErrorsContent();
  } else if (
    tabName === "monitoring" &&
    typeof loadMonitoringContent === "function"
  ) {
    loadMonitoringContent();
  }
}

// Initialize header on page load
document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const currentPage = body.getAttribute("data-page") || "messages";
  const header = renderHeader(currentPage);

  // Insert header as first child of body
  body.insertBefore(header, body.firstChild);

  // Apply translations if i18n is available
  if (typeof i18n !== "undefined") {
    i18n.updatePageContent();
  }
});
