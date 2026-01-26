# OpenAPI Quick Start Guide

## Overview

The Multi-Platform Messaging Adapter API is fully documented using OpenAPI 3.0 specification. This guide shows you how to use the specification for various purposes.

---

## Viewing the Documentation

### Option 1: Swagger UI (Recommended)

Interactive API documentation with "Try it out" functionality:

```bash
# Using Docker
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/docs/openapi.yaml \
  -v $(pwd)/docs:/docs \
  swaggerapi/swagger-ui

# Open in browser: http://localhost:8080
```

### Option 2: ReDoc

Clean, three-panel documentation:

```bash
# Using Docker
docker run -p 8080:80 \
  -e SPEC_URL=openapi.yaml \
  -v $(pwd)/docs:/usr/share/nginx/html \
  redocly/redoc

# Open in browser: http://localhost:8080
```

### Option 3: VS Code Extension

Install the "OpenAPI (Swagger) Editor" extension and open `docs/openapi.yaml`.

---

## Validating the Specification

### Using Swagger CLI

```bash
# Install
npm install -g @apidevtools/swagger-cli

# Validate
swagger-cli validate docs/openapi.yaml
```

### Using Redocly CLI

```bash
# Install
npm install -g @redocly/cli

# Validate
redocly lint docs/openapi.yaml
```

---

## Generating Client Libraries

### JavaScript/TypeScript

```bash
# Install generator
npm install -g @openapitools/openapi-generator-cli

# Generate TypeScript Axios client
openapi-generator-cli generate \
  -i docs/openapi.yaml \
  -g typescript-axios \
  -o clients/typescript

# Usage example
import { DefaultApi, Configuration } from './clients/typescript';

const api = new DefaultApi(new Configuration({
  basePath: 'https://your-domain.com',
  accessToken: 'YOUR_API_KEY'
}));

const response = await api.sendText({
  to: '+351912345678',
  text: 'Hello from TypeScript!'
});
```

### PHP

```bash
# Generate PHP client
openapi-generator-cli generate \
  -i docs/openapi.yaml \
  -g php \
  -o clients/php

# Usage example
<?php
require_once(__DIR__ . '/clients/php/vendor/autoload.php');

$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
    ->setAccessToken('YOUR_API_KEY');

$apiInstance = new OpenAPI\Client\Api\MessagesApi(
    new GuzzleHttp\Client(),
    $config
);

$request = new \OpenAPI\Client\Model\TextRequest([
    'to' => '+351912345678',
    'text' => 'Hello from PHP!'
]);

$result = $apiInstance->sendText($request);
```

### Python

```bash
# Generate Python client
openapi-generator-cli generate \
  -i docs/openapi.yaml \
  -g python \
  -o clients/python

# Usage example
import openapi_client
from openapi_client.api import messages_api
from openapi_client.model.text_request import TextRequest

configuration = openapi_client.Configuration(
    host = "https://your-domain.com",
    access_token = "YOUR_API_KEY"
)

with openapi_client.ApiClient(configuration) as api_client:
    api_instance = messages_api.MessagesApi(api_client)
    text_request = TextRequest(
        to="+351912345678",
        text="Hello from Python!"
    )

    api_response = api_instance.send_text(text_request)
```

---

## API Gateway Integration

### AWS API Gateway

1. Go to AWS API Gateway Console
2. Create new REST API
3. Click "Actions" → "Import API"
4. Select "OpenAPI 3.0"
5. Upload `docs/openapi.yaml`
6. Deploy to stage

### Kong Gateway

```bash
# Install deck (Kong's CLI tool)
brew install deck

# Convert OpenAPI to Kong format
deck file openapi2kong \
  -s docs/openapi.yaml \
  -o kong.yaml

# Apply to Kong
deck sync -s kong.yaml
```

### Azure API Management

1. Go to Azure Portal → API Management
2. Click "APIs" → "Add API"
3. Select "OpenAPI"
4. Upload `docs/openapi.yaml`
5. Configure backend and policies

---

## Testing with Postman

### Import Collection

1. Open Postman
2. Click "Import"
3. Select `docs/openapi.yaml`
4. Postman will create a collection with all endpoints

### Set Environment Variables

```json
{
  "baseUrl": "https://your-domain.com",
  "apiKey": "YOUR_API_KEY"
}
```

### Run Collection

Use Postman's Collection Runner to test all endpoints automatically.

---

## Automated Testing

### Using Dredd

```bash
# Install Dredd
npm install -g dredd

# Test API against specification
dredd docs/openapi.yaml https://your-domain.com \
  --header "Authorization: Bearer YOUR_API_KEY"
```

### Using Schemathesis

```bash
# Install Schemathesis
pip install schemathesis

# Run property-based tests
schemathesis run docs/openapi.yaml \
  --base-url https://your-domain.com \
  --header "Authorization: Bearer YOUR_API_KEY"
```

---

## Mock Server

### Using Prism

```bash
# Install Prism
npm install -g @stoplight/prism-cli

# Start mock server
prism mock docs/openapi.yaml

# Server runs on http://localhost:4010
# Test with: curl http://localhost:4010/health
```

### Using Mockoon

1. Download Mockoon: https://mockoon.com
2. Import `docs/openapi.yaml`
3. Start mock server
4. Use for frontend development without backend

---

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/openapi-validation.yml
name: Validate OpenAPI Spec

on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Validate OpenAPI Specification
        uses: char0n/swagger-editor-validate@v1
        with:
          definition-file: docs/openapi.yaml

      - name: Run API Tests
        run: |
          npm install -g dredd
          dredd docs/openapi.yaml ${{ secrets.API_BASE_URL }} \
            --header "Authorization: Bearer ${{ secrets.API_KEY }}"
```

### GitLab CI

```yaml
# .gitlab-ci.yml
validate-openapi:
  stage: test
  image: node:18
  script:
    - npm install -g @apidevtools/swagger-cli
    - swagger-cli validate docs/openapi.yaml
```

---

## Documentation Hosting

### GitHub Pages

```bash
# Install Redocly CLI
npm install -g @redocly/cli

# Build static HTML
redocly build-docs docs/openapi.yaml \
  -o public/index.html

# Commit to gh-pages branch
git checkout -b gh-pages
git add public/index.html
git commit -m "Update API docs"
git push origin gh-pages

# Access at: https://your-username.github.io/your-repo
```

### Netlify

```bash
# netlify.toml
[build]
  command = "npx @redocly/cli build-docs docs/openapi.yaml -o public/index.html"
  publish = "public"

# Deploy
netlify deploy --prod
```

---

## IDE Integration

### VS Code

Install extensions:

- **OpenAPI (Swagger) Editor** - Syntax highlighting and validation
- **REST Client** - Test endpoints directly in VS Code

### IntelliJ IDEA / PhpStorm

1. Install "OpenAPI Specifications" plugin
2. Right-click `docs/openapi.yaml`
3. Select "OpenAPI" → "Generate Code"

### Vim

```bash
# Install ALE (Asynchronous Lint Engine)
# Add to .vimrc:
let g:ale_linters = {
\   'yaml': ['yamllint'],
\}
```

---

## Keeping Specification Updated

### Manual Updates

When adding new endpoints:

1. Update `docs/openapi.yaml`
2. Validate: `swagger-cli validate docs/openapi.yaml`
3. Test: `dredd docs/openapi.yaml http://localhost:8000`
4. Commit changes

### Automated Generation

Consider using annotations in code:

```php
/**
 * @OA\Post(
 *     path="/api/messages/text",
 *     summary="Send text message",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/TextRequest")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Message sent successfully"
 *     )
 * )
 */
public function sendText(ServerRequestInterface $request): ResponseInterface
{
    // Implementation
}
```

Then generate with:

```bash
vendor/bin/openapi src/ -o docs/openapi.yaml
```

---

## Common Use Cases

### 1. Frontend Development

Use mock server for development:

```bash
prism mock docs/openapi.yaml
```

### 2. API Testing

Automated contract testing:

```bash
dredd docs/openapi.yaml https://staging.your-domain.com
```

### 3. Client SDK Distribution

Generate and publish SDKs:

```bash
# Generate all clients
./scripts/generate-clients.sh

# Publish to npm, PyPI, Packagist
npm publish clients/typescript
python setup.py sdist upload
```

### 4. API Documentation Portal

Host interactive docs:

```bash
redocly build-docs docs/openapi.yaml -o docs-site/
```

---

## Troubleshooting

### Validation Errors

```bash
# Get detailed validation errors
swagger-cli validate docs/openapi.yaml --debug
```

### Schema Mismatches

```bash
# Test actual API responses against schema
schemathesis run docs/openapi.yaml \
  --base-url https://your-domain.com \
  --checks all
```

### CORS Issues

Add to OpenAPI spec:

```yaml
servers:
  - url: https://your-domain.com
    description: Production
    x-cors:
      allowOrigins: ["*"]
      allowMethods: ["GET", "POST"]
```

---

## Resources

- **OpenAPI Specification**: https://spec.openapis.org/oas/v3.0.3
- **Swagger UI**: https://swagger.io/tools/swagger-ui/
- **ReDoc**: https://redocly.com/redoc/
- **OpenAPI Generator**: https://openapi-generator.tech/
- **Prism Mock Server**: https://stoplight.io/open-source/prism
- **Dredd Testing**: https://dredd.org/

---

## Support

For questions about the OpenAPI specification:

1. Check the specification: `docs/openapi.yaml`
2. Review examples in `docs/API.md`
3. Open an issue on GitHub
4. Contact: support@your-domain.com
