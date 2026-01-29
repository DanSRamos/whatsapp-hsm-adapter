# Multi-Language Support - Admin Panel

## Overview

The admin panel now supports multiple languages with English as the default. Users can switch languages using the language selector in the header.

## Supported Languages

- 🇬🇧 **English** (default)
- 🇵🇹 **Português**

## How It Works

### 1. Translation Files

All translations are stored in `i18n.js` in the `translations` object:

```javascript
const translations = {
  en: {
    /* English translations */
  },
  pt: {
    /* Portuguese translations */
  },
};
```

### 2. Using Translations in HTML

Add the `data-i18n` attribute to any element you want to translate:

```html
<h1 data-i18n="dashboardTitle">Multi-Platform Messaging Admin Panel</h1>
<button data-i18n="sendMessage">Send Message</button>
```

For HTML content (not just text), use `data-i18n-html`:

```html
<div data-i18n-html="welcomeMessage"></div>
```

For page titles, use `data-i18n-title`:

```html
<title data-i18n-title="apiDocsTitle">API Documentation</title>
```

### 3. Language Selector

The language selector is automatically injected into the header. It:

- Saves the user's language preference in localStorage
- Automatically updates all translated content when changed
- Persists across page reloads

## Adding a New Language

1. Add translations to `i18n.js`:

```javascript
const translations = {
  en: {
    /* ... */
  },
  pt: {
    /* ... */
  },
  es: {
    // New language
    dashboardTitle: "Panel de Administración",
    // ... more translations
  },
};
```

2. Add the language option to `language-selector.js`:

```html
<option value="es">🇪🇸 Español</option>
```

## Adding New Translation Keys

1. Add the key to all language objects in `i18n.js`:

```javascript
const translations = {
  en: {
    // ... existing keys
    newFeature: "New Feature",
  },
  pt: {
    // ... existing keys
    newFeature: "Nova Funcionalidade",
  },
};
```

2. Use it in your HTML:

```html
<h2 data-i18n="newFeature">New Feature</h2>
```

## Implementation Checklist

### Files Updated

- ✅ `i18n.js` - Translation system and manager
- ✅ `language-selector.js` - Language selector component
- ✅ `styles.css` - Language selector styles
- ✅ `index-tabs.html` - Main dashboard (partially updated)
- ✅ `api-docs.html` - API documentation page (partially updated)
- ⏳ `rcs.html` - RCS messages page (pending)
- ⏳ `monitoring.html` - Monitoring dashboard (pending)
- ⏳ `errors-dashboard.html` - Errors dashboard (pending)
- ⏳ `performance-dashboard.html` - Performance dashboard (pending)
- ⏳ `metrics-dashboard.html` - Metrics dashboard (pending)

### To Complete Implementation

For each HTML file:

1. Add scripts to `<head>`:

```html
<script src="i18n.js"></script>
<script src="language-selector.js"></script>
```

2. Change `lang` attribute:

```html
<html lang="en"></html>
```

3. Add `data-i18n` attributes to translatable elements

4. Test language switching

## JavaScript API

### Get Current Language

```javascript
const currentLang = i18n.getCurrentLanguage(); // 'en' or 'pt'
```

### Change Language Programmatically

```javascript
i18n.setLanguage("pt");
```

### Get Translation

```javascript
const translation = i18n.t("dashboardTitle");
```

### Listen to Language Changes

```javascript
window.addEventListener("languageChanged", (event) => {
  console.log("Language changed to:", event.detail.language);
  // Update dynamic content
});
```

## Best Practices

1. **Always provide English translations** - It's the default language
2. **Keep keys descriptive** - Use camelCase: `dashboardTitle`, `sendMessage`
3. **Group related keys** - Use prefixes: `error*`, `success*`, `button*`
4. **Test both languages** - Ensure all content is translated
5. **Keep translations concise** - Especially for buttons and labels
6. **Use placeholders for dynamic content** - Handle in JavaScript

## Example: Complete Page Setup

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title data-i18n-title="pageTitle">My Page</title>
    <script src="i18n.js"></script>
    <script src="language-selector.js"></script>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <div class="header">
      <h1 data-i18n="pageTitle">My Page</h1>
      <p data-i18n="pageSubtitle">Page description</p>
    </div>

    <button data-i18n="actionButton">Click Me</button>

    <script>
      // Language selector is auto-injected
      // Content is auto-translated on load
    </script>
  </body>
</html>
```

## Notes

- Language preference is stored in `localStorage` as `adminPanelLanguage`
- The system automatically updates content when the page loads
- No page reload is needed when switching languages
- The language selector appears in the top-right of the header
