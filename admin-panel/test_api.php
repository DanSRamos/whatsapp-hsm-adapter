<!DOCTYPE html>
<html>
<head>
    <title>API Test</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .test {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #25d366;
        }
        .test.error {
            border-left-color: #f44336;
        }
        .test.success {
            border-left-color: #4caf50;
        }
        pre {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🧪 API Test Page</h1>
    
    <div class="test">
        <h3>Test 1: Health Check</h3>
        <button onclick="testHealth()">Run Test</button>
        <pre id="health-result">Click button to test...</pre>
    </div>
    
    <div class="test">
        <h3>Test 2: Get Templates</h3>
        <button onclick="testTemplates()">Run Test</button>
        <pre id="templates-result">Click button to test...</pre>
    </div>
    
    <div class="test">
        <h3>Test 3: Get Messages</h3>
        <button onclick="testMessages()">Run Test</button>
        <pre id="messages-result">Click button to test...</pre>
    </div>

    <script>
        const API_BASE = 'api.php';
        
        async function testHealth() {
            const result = document.getElementById('health-result');
            result.textContent = 'Testing...';
            
            try {
                const response = await fetch(API_BASE + '?action=health');
                const data = await response.json();
                result.textContent = JSON.stringify(data, null, 2);
                result.parentElement.className = data.success ? 'test success' : 'test error';
            } catch (error) {
                result.textContent = 'Error: ' + error.message;
                result.parentElement.className = 'test error';
            }
        }
        
        async function testTemplates() {
            const result = document.getElementById('templates-result');
            result.textContent = 'Testing...';
            
            try {
                const response = await fetch(API_BASE + '?action=get_templates');
                const data = await response.json();
                result.textContent = JSON.stringify(data, null, 2);
                result.parentElement.className = data.success ? 'test success' : 'test error';
            } catch (error) {
                result.textContent = 'Error: ' + error.message;
                result.parentElement.className = 'test error';
            }
        }
        
        async function testMessages() {
            const result = document.getElementById('messages-result');
            result.textContent = 'Testing...';
            
            try {
                const response = await fetch(API_BASE + '?action=get_messages');
                const data = await response.json();
                result.textContent = JSON.stringify(data, null, 2);
                result.parentElement.className = data.success ? 'test success' : 'test error';
            } catch (error) {
                result.textContent = 'Error: ' + error.message;
                result.parentElement.className = 'test error';
            }
        }
    </script>
</body>
</html>
