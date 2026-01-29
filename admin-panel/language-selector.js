// Language Selector Component
function createLanguageSelector() {
  const container = document.createElement("div");
  container.className = "language-selector";
  container.innerHTML = `
    <select id="languageSelect" class="language-select">
      <option value="en">🇬🇧 English</option>
      <option value="pt">🇵🇹 Português</option>
    </select>
  `;

  const select = container.querySelector("#languageSelect");
  select.value = i18n.getCurrentLanguage();

  select.addEventListener("change", (e) => {
    i18n.setLanguage(e.target.value);
  });

  return container;
}

// Auto-inject language selector into header if it exists
document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector(".header");
  if (header && !document.querySelector(".language-selector")) {
    const selector = createLanguageSelector();
    header.appendChild(selector);
  }
});
