# Meta Messaging Integration - Operations Runbook

## Overview

This runbook provides step-by-step procedures for diagnosing and resolving common operational issues with the Meta Messaging Integration (Instagram + Facebook Messenger).

## Table of Contents

1. [Common Issues](#common-issues)
2. [Diagnostic Procedures](#diagnostic-procedures)
3. [Resolution Procedures](#resolution-procedures)
4. [Monitoring & Alerts](#monitoring--alerts)
5. [Escalation Procedures](#escalation-procedures)

---

## Common Issues

### Issue 1: Messages Not Sending (Instagram/Messenger)

**Symptoms**:

- API returns error when sending messages
- Messages stuck in "pending" status
- High error rate in metrics dashboard

**Severity**: High

**Common Causes**:

- Invalid or expired Page Access Token
- 24-hour messaging window expired
- Rate limit exceeded
- Meta API outage
- Network connectivity issues

**Quick Diagnosis**:

```bash
# Check recent errors
tail -100 storage/logs/app.log | grep "ERROR.*Meta"

# Check metrics
curl -s https://yourdomain.com/api/metrics | jq '.meta'

# Test Meta API connectivity
curl -X POST "https://graph.facebook.com/v21.0/me/messages" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"recipient":{"id":"test"},"message":{"text":"test"}}'
```

**Resolution**: See [Resolution 1](#resolution-1-fix-message-sending-issues)

---

### Issue 2: Webhooks Not Being Received

**Symptoms**:

- Incoming messages not appearing in system
- Delivery reports not updating
- Webhook logs empty

**Severity**: High

**Common Causes**:

- Webhook signature validation failing
- Webhook URL not accessible from Meta servers
- SSL certificate issues
- Firewall blocking Meta IPs
- Webhook subscription not configured

**Quick Diagnosis**:

```bash
# Check webhook logs
tail -100 storage/logs/app.log | grep "webhook"

# Check webhook endpoint accessibility
curl https://yourdomain.com/webhook/meta

# Test webhook verification
curl "https://yourdomain.com/webhook/meta?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"
```

**Resolution**: See [Resolution 2](#resolution-2-fix-webhook-issues)

---

### Issue 3: High Error Rate

**Symptoms**:

- Error rate > 5% in metrics
- Multiple failed message attempts
- Circuit breaker opening frequently

**Severity**: Medium to High

**Common Causes**:

- Meta API rate limiting
- Invalid message formats
- Expired messaging window
- Network issues
- Bug in message formatting

**Quick Diagnosis**:

```bash
# Check error distribution
curl -s https://yourdomain.com/api/metrics | jq '.errors_by_type'

# Check recent errors
tail -200 storage/logs/app.log | grep "ERROR" | cut -d' ' -f5- | sort | uniq -c | sort -rn

# Check circuit breaker status
curl -s https://yourdomain.com/api/metrics | jq '.circuit_breaker'
```

**Resolution**: See [Resolution 3](#resolution-3-reduce-error-rate)

---

### Issue 4: Slow Response Times

**Symptoms**:

- Response time > 2s (p95)
- Timeouts occurring
- Users reporting slow admin panel

**Severity**: Medium

**Common Causes**:

- Database query performance
- Meta API slow responses
- High load
- Memory issues
- Network latency

**Quick Diagnosis**:

```bash
# Check response times
curl -s https://yourdomain.com/api/metrics | jq '.response_times'

# Check database performance
mysql -u [user] -p [database] -e "SHOW PROCESSLIST;"

# Check system resources
top -b -n 1 | head -20
free -h
df -h
```

**Resolution**: See [Resolution 4](#resolution-4-improve-performance)

---

### Issue 5: 24-Hour Messaging Window Violations

**Symptoms**:

- Error: "Messaging window expired"
- Messages failing after 24 hours
- Users unable to send messages

**Severity**: Medium

**Common Causes**:

- User hasn't messaged in > 24 hours
- Timestamp tracking incorrect
- Timezone issues

**Quick Diagnosis**:

```bash
# Check recent window violations
grep "messaging window" storage/logs/app.log | tail -20

# Check specific conversation
mysql -u [user] -p [database] << EOF
SELECT * FROM incoming_messages
WHERE sender_id = 'IGSID_OR_PSID'
ORDER BY created_at DESC
LIMIT 5;
EOF
```

**Resolution**: See [Resolution 5](#resolution-5-handle-messaging-window)

---

### Issue 6: Rate Limit Exceeded

**Symptoms**:

- Error: "Rate limit exceeded"
- Messages queuing up
- Delayed message delivery

**Severity**: Medium

**Common Causes**:

- Sending too many messages too quickly
- Rate limiter configuration too aggressive
- Burst traffic
- Meta API rate limits hit

**Quick Diagnosis**:

```bash
# Check rate limiter status
curl -s https://yourdomain.com/api/metrics | jq '.rate_limiter'

# Check message send rate
mysql -u [user] -p [database] << EOF
SELECT
  DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as minute,
  COUNT(*) as messages
FROM messages
WHERE created_at > NOW() - INTERVAL 1 HOUR
GROUP BY minute
ORDER BY minute DESC;
EOF
```

**Resolution**: See [Resolution 6](#resolution-6-handle-rate-limits)

---

### Issue 7: Circuit Breaker Open

**Symptoms**:

- Error: "Circuit breaker open"
- All Meta messages failing
- Alert: "Circuit breaker opened"

**Severity**: High

**Common Causes**:

- Multiple consecutive failures
- Meta API outage
- Network issues
- Configuration error

**Quick Diagnosis**:

```bash
# Check circuit breaker status
curl -s https://yourdomain.com/api/metrics | jq '.circuit_breaker'

# Check recent failures
tail -100 storage/logs/app.log | grep "circuit breaker"

# Check Meta API status
curl https://developers.facebook.com/status/
```

**Resolution**: See [Resolution 7](#resolution-7-reset-circuit-breaker)

---

### Issue 8: Database Connection Issues

**Symptoms**:

- Error: "Database connection failed"
- Intermittent failures
- Slow queries

**Severity**: High

**Common Causes**:

- Database server down
- Connection pool exhausted
- Network issues
- Database credentials incorrect

**Quick Diagnosis**:

```bash
# Test database connection
mysql -u [user] -p [database] -e "SELECT 1;"

# Check connection pool
mysql -u [user] -p [database] -e "SHOW STATUS LIKE 'Threads%';"

# Check database logs
tail -100 /var/log/mysql/error.log
```

**Resolution**: See [Resolution 8](#resolution-8-fix-database-issues)

---

## Diagnostic Procedures

### Procedure 1: Check System Health

```bash
#!/bin/bash
# health_check.sh - Comprehensive system health check

echo "=== System Health Check ==="
echo ""

# 1. Application Health
echo "1. Application Health:"
curl -s https://yourdomain.com/api/health | jq .
echo ""

# 2. Metrics
echo "2. Current Metrics:"
curl -s https://yourdomain.com/api/metrics | jq '{
  error_rate: .error_rate,
  response_time_p95: .response_time_p95,
  messages_sent_1h: .messages_sent_1h,
  circuit_breaker: .circuit_breaker.state
}'
echo ""

# 3. Recent Errors
echo "3. Recent Errors (last 10):"
tail -100 storage/logs/app.log | grep "ERROR" | tail -10
echo ""

# 4. Database Status
echo "4. Database Status:"
mysql -u [user] -p[password] [database] -e "
  SELECT
    'Messages' as table_name, COUNT(*) as count
  FROM messages
  UNION ALL
  SELECT
    'Incoming Messages', COUNT(*)
  FROM incoming_messages;
"
echo ""

# 5. System Resources
echo "5. System Resources:"
echo "Memory:"
free -h
echo ""
echo "Disk:"
df -h /var/www
echo ""
echo "Load:"
uptime
echo ""

# 6. Meta API Connectivity
echo "6. Meta API Connectivity:"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\nTime: %{time_total}s\n" \
  "https://graph.facebook.com/v21.0/me?access_token=YOUR_TOKEN"
echo ""

echo "=== Health Check Complete ==="
```

### Procedure 2: Analyze Error Patterns

```bash
#!/bin/bash
# analyze_errors.sh - Analyze error patterns

echo "=== Error Analysis ==="
echo ""

# Error frequency by type
echo "1. Error Frequency (last 1000 lines):"
tail -1000 storage/logs/app.log | \
  grep "ERROR" | \
  sed 's/.*ERROR: //' | \
  cut -d' ' -f1-5 | \
  sort | uniq -c | sort -rn | head -10
echo ""

# Errors by hour
echo "2. Errors by Hour (last 24h):"
tail -10000 storage/logs/app.log | \
  grep "ERROR" | \
  cut -d' ' -f1-2 | \
  cut -d':' -f1-2 | \
  sort | uniq -c
echo ""

# Meta-specific errors
echo "3. Meta API Errors:"
tail -1000 storage/logs/app.log | \
  grep "Meta.*error" | \
  grep -oP 'code":\K[0-9]+' | \
  sort | uniq -c | sort -rn
echo ""

# Webhook errors
echo "4. Webhook Errors:"
tail -1000 storage/logs/app.log | \
  grep "webhook.*ERROR" | wc -l
echo ""

echo "=== Analysis Complete ==="
```

### Procedure 3: Check Meta API Status

```bash
#!/bin/bash
# check_meta_api.sh - Check Meta API status and connectivity

echo "=== Meta API Status Check ==="
echo ""

# Load credentials
source .env

# 1. Test authentication
echo "1. Testing Authentication:"
response=$(curl -s -w "\n%{http_code}" \
  "https://graph.facebook.com/v21.0/me?access_token=$META_PAGE_ACCESS_TOKEN")
http_code=$(echo "$response" | tail -1)
body=$(echo "$response" | head -n -1)

if [ "$http_code" = "200" ]; then
  echo "✓ Authentication successful"
  echo "$body" | jq .
else
  echo "✗ Authentication failed (HTTP $http_code)"
  echo "$body" | jq .
fi
echo ""

# 2. Test message send endpoint
echo "2. Testing Message Send Endpoint:"
response=$(curl -s -w "\n%{http_code}" \
  -X POST "https://graph.facebook.com/v21.0/$META_PAGE_ID/messages" \
  -H "Authorization: Bearer $META_PAGE_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recipient":{"id":"test"},"message":{"text":"test"}}')
http_code=$(echo "$response" | tail -1)
body=$(echo "$response" | head -n -1)

echo "HTTP Status: $http_code"
echo "$body" | jq .
echo ""

# 3. Check rate limit headers
echo "3. Checking Rate Limits:"
curl -s -I "https://graph.facebook.com/v21.0/me?access_token=$META_PAGE_ACCESS_TOKEN" | \
  grep -i "x-.*-usage"
echo ""

# 4. Check Meta Platform Status
echo "4. Meta Platform Status:"
echo "Check: https://developers.facebook.com/status/"
echo ""

echo "=== Check Complete ==="
```

### Procedure 4: Trace Message Flow

```bash
#!/bin/bash
# trace_message.sh - Trace a specific message through the system

MESSAGE_ID=$1

if [ -z "$MESSAGE_ID" ]; then
  echo "Usage: $0 <message_id>"
  exit 1
fi

echo "=== Tracing Message: $MESSAGE_ID ==="
echo ""

# 1. Check database
echo "1. Database Record:"
mysql -u [user] -p[password] [database] << EOF
SELECT
  id,
  provider,
  recipient,
  type,
  status,
  created_at,
  updated_at,
  metadata
FROM messages
WHERE id = '$MESSAGE_ID' OR provider_message_id = '$MESSAGE_ID';
EOF
echo ""

# 2. Check logs
echo "2. Log Entries:"
grep "$MESSAGE_ID" storage/logs/app.log
echo ""

# 3. Check webhook logs
echo "3. Webhook Logs:"
mysql -u [user] -p[password] [database] << EOF
SELECT *
FROM webhook_logs
WHERE payload LIKE '%$MESSAGE_ID%'
ORDER BY created_at DESC
LIMIT 5;
EOF
echo ""

echo "=== Trace Complete ==="
```

---

## Resolution Procedures

### Resolution 1: Fix Message Sending Issues

**Step 1: Verify Credentials**

```bash
# Check if token is set
echo $META_PAGE_ACCESS_TOKEN

# Test token validity
curl "https://graph.facebook.com/v21.0/me?access_token=$META_PAGE_ACCESS_TOKEN"
```

**Step 2: Check Messaging Window**

```bash
# Check last message from user
mysql -u [user] -p [database] << EOF
SELECT
  sender_id,
  MAX(created_at) as last_message,
  TIMESTAMPDIFF(HOUR, MAX(created_at), NOW()) as hours_ago
FROM incoming_messages
WHERE sender_id = 'IGSID_OR_PSID'
GROUP BY sender_id;
EOF
```

**Step 3: Test Message Send**

```bash
# Send test message
curl -X POST https://yourdomain.com/api/messages/send \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "instagram",
    "recipient": "IGSID",
    "type": "text",
    "content": "Test message"
  }'
```

**Step 4: Check Rate Limits**

```bash
# Check current rate limit status
curl -s https://yourdomain.com/api/metrics | jq '.rate_limiter'
```

**Step 5: Review Logs**

```bash
# Check for specific errors
tail -100 storage/logs/app.log | grep "Meta.*send"
```

---

### Resolution 2: Fix Webhook Issues

**Step 1: Verify Webhook Configuration**

```bash
# Test webhook verification endpoint
curl "https://yourdomain.com/webhook/meta?hub.mode=subscribe&hub.verify_token=$META_VERIFY_TOKEN&hub.challenge=test123"

# Should return: test123
```

**Step 2: Check Webhook Accessibility**

```bash
# Test from external service
curl -X POST https://yourdomain.com/webhook/meta \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

**Step 3: Verify SSL Certificate**

```bash
# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com < /dev/null

# Check certificate expiry
echo | openssl s_client -connect yourdomain.com:443 -servername yourdomain.com 2>/dev/null | \
  openssl x509 -noout -dates
```

**Step 4: Check Webhook Subscriptions**

1. Go to Meta Developer Console
2. Navigate to your app → Messenger → Settings
3. Verify webhook subscriptions are enabled for:
   - messages
   - messaging_postbacks
   - message_deliveries
   - message_reads

**Step 5: Test Webhook Processing**

```bash
# Send test webhook
curl -X POST https://yourdomain.com/webhook/meta \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=$(echo -n '{"test":"data"}' | openssl dgst -sha256 -hmac "$META_APP_SECRET" | cut -d' ' -f2)" \
  -d '{"test": "data"}'

# Check logs
tail -f storage/logs/app.log | grep webhook
```

---

### Resolution 3: Reduce Error Rate

**Step 1: Identify Error Types**

```bash
# Get error distribution
tail -1000 storage/logs/app.log | \
  grep "ERROR.*Meta" | \
  grep -oP 'code":\K[0-9]+' | \
  sort | uniq -c | sort -rn
```

**Step 2: Address Specific Errors**

For error code 36103 (Account not eligible):

- Verify Instagram account is a Professional or Business account
- Check account status in Meta Business Suite

For error code 2534068 (Feature not available):

- Verify app has required permissions
- Check if feature is available in your region

For error code 190 (Invalid token):

- Regenerate Page Access Token
- Update `.env` file
- Restart application

**Step 3: Implement Fixes**

```bash
# Update token if expired
nano .env
# Update META_PAGE_ACCESS_TOKEN

# Restart application
sudo systemctl restart php-fpm
```

**Step 4: Monitor Improvement**

```bash
# Watch error rate
watch -n 10 'curl -s https://yourdomain.com/api/metrics | jq .error_rate'
```

---

### Resolution 4: Improve Performance

**Step 1: Identify Bottleneck**

```bash
# Check slow queries
mysql -u [user] -p [database] -e "
  SELECT * FROM information_schema.processlist
  WHERE time > 1
  ORDER BY time DESC;
"

# Check system resources
top -b -n 1 | head -20
iostat -x 1 5
```

**Step 2: Optimize Database**

```bash
# Add indexes if missing
mysql -u [user] -p [database] << EOF
-- Check existing indexes
SHOW INDEX FROM messages;

-- Add indexes if needed
CREATE INDEX idx_provider_created ON messages(provider, created_at);
CREATE INDEX idx_status ON messages(status);
EOF
```

**Step 3: Clear Caches**

```bash
# Clear application cache
rm -rf storage/cache/*
php artisan cache:clear  # If using Laravel

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

**Step 4: Optimize Configuration**

```bash
# Increase PHP memory limit
nano /etc/php/8.1/fpm/php.ini
# Set: memory_limit = 256M

# Increase PHP-FPM workers
nano /etc/php/8.1/fpm/pool.d/www.conf
# Set: pm.max_children = 50

# Restart PHP-FPM
sudo systemctl restart php-fpm
```

---

### Resolution 5: Handle Messaging Window

**Step 1: Verify Window Status**

```bash
# Check last message time
mysql -u [user] -p [database] << EOF
SELECT
  sender_id,
  MAX(created_at) as last_message,
  TIMESTAMPDIFF(HOUR, MAX(created_at), NOW()) as hours_since,
  CASE
    WHEN TIMESTAMPDIFF(HOUR, MAX(created_at), NOW()) < 24 THEN 'OPEN'
    ELSE 'CLOSED'
  END as window_status
FROM incoming_messages
WHERE sender_id = 'IGSID_OR_PSID'
GROUP BY sender_id;
EOF
```

**Step 2: Inform User**

If window is closed:

- Inform user they need to wait for customer to message first
- Or use Message Tags (if applicable and approved by Meta)

**Step 3: Implement Message Tags (Optional)**

```php
// Only if approved by Meta
$payload = [
    'recipient' => ['id' => $igsid],
    'message' => ['text' => 'Your message'],
    'messaging_type' => 'MESSAGE_TAG',
    'tag' => 'CONFIRMED_EVENT_UPDATE'  // Or appropriate tag
];
```

---

### Resolution 6: Handle Rate Limits

**Step 1: Check Current Limits**

```bash
# Check rate limiter status
curl -s https://yourdomain.com/api/metrics | jq '.rate_limiter'
```

**Step 2: Adjust Rate Limiter**

```bash
# Edit configuration
nano config/meta.php

# Adjust limits:
# 'rate_limit' => [
#     'messages_per_second' => 10,  // Reduce if hitting limits
#     'burst_size' => 20
# ]
```

**Step 3: Implement Queuing**

If rate limits are consistently hit, implement message queuing:

```php
// Queue messages instead of sending immediately
$queue->push(new SendMessageJob($message));
```

**Step 4: Monitor**

```bash
# Watch message send rate
watch -n 5 'curl -s https://yourdomain.com/api/metrics | jq .messages_sent_1m'
```

---

### Resolution 7: Reset Circuit Breaker

**Step 1: Check Circuit Breaker Status**

```bash
# Check status
curl -s https://yourdomain.com/api/metrics | jq '.circuit_breaker'
```

**Step 2: Verify Meta API is Healthy**

```bash
# Test Meta API
curl "https://graph.facebook.com/v21.0/me?access_token=$META_PAGE_ACCESS_TOKEN"
```

**Step 3: Reset Circuit Breaker**

```bash
# Option 1: Wait for automatic reset (default: 60 seconds)

# Option 2: Manual reset via API
curl -X POST https://yourdomain.com/api/circuit-breaker/reset

# Option 3: Restart application
sudo systemctl restart php-fpm
```

**Step 4: Monitor**

```bash
# Watch circuit breaker status
watch -n 5 'curl -s https://yourdomain.com/api/metrics | jq .circuit_breaker'
```

---

### Resolution 8: Fix Database Issues

**Step 1: Test Connection**

```bash
# Test database connection
mysql -u [user] -p [database] -e "SELECT 1;"
```

**Step 2: Check Database Status**

```bash
# Check MySQL status
sudo systemctl status mysql

# Check MySQL logs
tail -100 /var/log/mysql/error.log
```

**Step 3: Restart Database (If Needed)**

```bash
# Restart MySQL
sudo systemctl restart mysql

# Verify it's running
sudo systemctl status mysql
```

**Step 4: Verify Application Connection**

```bash
# Test from application
php -r "
  require 'config/database.php';
  \$pdo = new PDO(
    'mysql:host=' . config('database.host') . ';dbname=' . config('database.name'),
    config('database.user'),
    config('database.password')
  );
  echo 'Connection successful';
"
```

---

## Monitoring & Alerts

### Key Metrics to Monitor

| Metric                | Normal Range | Warning Threshold | Critical Threshold |
| --------------------- | ------------ | ----------------- | ------------------ |
| Error Rate            | < 1%         | > 5%              | > 10%              |
| Response Time (p95)   | < 2s         | > 3s              | > 5s               |
| Messages Sent/Hour    | Varies       | N/A               | N/A                |
| Webhook Success Rate  | > 95%        | < 90%             | < 80%              |
| Circuit Breaker State | Closed       | Half-Open         | Open               |
| Database Connections  | < 50         | > 80              | > 95               |
| Memory Usage          | < 70%        | > 85%             | > 95%              |
| Disk Usage            | < 70%        | > 85%             | > 95%              |

### Alert Response Times

| Severity | Response Time     | Example                           |
| -------- | ----------------- | --------------------------------- |
| Critical | 15 minutes        | System down, data loss            |
| High     | 1 hour            | High error rate, webhooks failing |
| Medium   | 4 hours           | Performance degradation           |
| Low      | Next business day | Minor bugs, UI issues             |

### Alert Escalation

1. **Level 1**: On-call engineer
2. **Level 2**: Technical lead (if not resolved in 30 minutes)
3. **Level 3**: Engineering manager (if not resolved in 1 hour)
4. **Level 4**: CTO (if critical and not resolved in 2 hours)

---

## Escalation Procedures

### When to Escalate

Escalate immediately if:

- System is completely down
- Data loss is occurring
- Security breach detected
- Issue cannot be resolved within SLA
- Multiple systems affected

### Escalation Contacts

| Level | Role                | Contact       | Availability             |
| ----- | ------------------- | ------------- | ------------------------ |
| L1    | On-Call Engineer    | [Phone/Email] | 24/7                     |
| L2    | Technical Lead      | [Phone/Email] | Business hours + on-call |
| L3    | Engineering Manager | [Phone/Email] | Business hours           |
| L4    | CTO                 | [Phone/Email] | Emergency only           |

### Escalation Template

```
Subject: [SEVERITY] Meta Integration Issue - [Brief Description]

Issue: [Detailed description]
Impact: [User impact, systems affected]
Started: [Timestamp]
Current Status: [What's been tried]
Next Steps: [Planned actions]
ETA: [Estimated resolution time]

Escalating because: [Reason for escalation]
```

---

## Appendix

### Useful Commands

```bash
# Quick health check
curl -s https://yourdomain.com/api/health | jq .

# Check error rate
curl -s https://yourdomain.com/api/metrics | jq .error_rate

# Tail logs
tail -f storage/logs/app.log

# Check database
mysql -u [user] -p [database] -e "SELECT COUNT(*) FROM messages;"

# Restart services
sudo systemctl restart php-fpm nginx

# Check disk space
df -h

# Check memory
free -h

# Check processes
ps aux | grep php
```

### Log Locations

- Application logs: `storage/logs/app.log`
- Nginx logs: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- PHP-FPM logs: `/var/log/php8.1-fpm.log`
- MySQL logs: `/var/log/mysql/error.log`

### Configuration Files

- Application: `.env`
- Meta config: `config/meta.php`
- Providers: `config/providers.php`
- Database: `config/database.php`
- Nginx: `/etc/nginx/sites-available/default`
- PHP-FPM: `/etc/php/8.1/fpm/pool.d/www.conf`

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-19  
**Next Review**: Quarterly or after major incidents
