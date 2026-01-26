# Meta Messaging Integration - Deployment Checklist

## Pre-Deployment Checklist

### 1. Environment Configuration

- [ ] **Meta Credentials Configured**

  - [ ] `META_PAGE_ACCESS_TOKEN` set in production `.env`
  - [ ] `META_APP_ID` set in production `.env`
  - [ ] `META_APP_SECRET` set in production `.env`
  - [ ] `META_PAGE_ID` set in production `.env`
  - [ ] `META_VERIFY_TOKEN` set in production `.env`
  - [ ] Tokens validated and not expired
  - [ ] Tokens have correct permissions (pages_messaging, instagram_manage_messages)

- [ ] **Database Configuration**

  - [ ] Database migrations executed successfully
  - [ ] Tables created: `messages`, `incoming_messages`, `templates`, `webhook_logs`
  - [ ] Database indexes optimized
  - [ ] Database backup completed

- [ ] **Webhook Configuration**
  - [ ] Webhook URL configured in Meta Developer Console
  - [ ] Webhook verification token matches `.env` configuration
  - [ ] Webhook subscriptions enabled for:
    - [ ] `messages`
    - [ ] `messaging_postbacks`
    - [ ] `message_deliveries`
    - [ ] `message_reads`
  - [ ] Webhook SSL certificate valid
  - [ ] Webhook endpoint accessible from Meta servers

### 2. Code Quality & Testing

- [ ] **Unit Tests**

  - [ ] All unit tests passing (`vendor/bin/pest tests/Unit`)
  - [ ] Code coverage ≥ 80%
  - [ ] MetaProvider tests passing
  - [ ] MetaWebhookHandler tests passing
  - [ ] MetaMessageFormatter tests passing
  - [ ] MetaPlatformDetector tests passing

- [ ] **Integration Tests**

  - [ ] All integration tests passing (`vendor/bin/pest tests/Integration`)
  - [ ] MetaMessageFlowTest passing
  - [ ] MetaMessageServiceTest passing
  - [ ] MetaPlatformSwitchingTest passing
  - [ ] MetaMessagingWindowTest passing

- [ ] **Property-Based Tests**

  - [ ] All property tests passing
  - [ ] No failing counterexamples

- [ ] **Code Quality**
  - [ ] PSR-12 coding standards followed
  - [ ] No critical security vulnerabilities
  - [ ] No high-severity bugs
  - [ ] Code reviewed and approved

### 3. Infrastructure & Monitoring

- [ ] **Rate Limiting**

  - [ ] MetaRateLimiter configured with appropriate limits
  - [ ] Rate limit thresholds tested
  - [ ] Rate limit alerts configured

- [ ] **Circuit Breaker**

  - [ ] MetaCircuitBreaker configured
  - [ ] Failure threshold set (default: 5 failures)
  - [ ] Timeout period set (default: 60 seconds)
  - [ ] Circuit breaker alerts configured

- [ ] **Retry Policy**

  - [ ] MetaRetryPolicy configured
  - [ ] Max retries set (default: 3)
  - [ ] Exponential backoff configured
  - [ ] Transient error detection working

- [ ] **Monitoring & Alerting**

  - [ ] MetaMetricsCollector enabled
  - [ ] Metrics endpoint accessible (`/api/metrics`)
  - [ ] Dashboards configured:
    - [ ] Messages dashboard
    - [ ] Errors dashboard
    - [ ] Performance dashboard
  - [ ] Alerts configured for:
    - [ ] API errors (threshold: 10 errors/minute)
    - [ ] Webhook failures (threshold: 5 failures/minute)
    - [ ] Rate limit hits
    - [ ] Circuit breaker open state
    - [ ] 24-hour messaging window violations

- [ ] **Logging**
  - [ ] Log level configured appropriately (INFO for production)
  - [ ] Log rotation configured
  - [ ] Sensitive data (tokens, IGSIDs, PSIDs) masked in logs
  - [ ] Log aggregation configured (if applicable)

### 4. Security

- [ ] **Token Security**

  - [ ] Page Access Token stored securely (encrypted at rest)
  - [ ] App Secret not exposed in logs or error messages
  - [ ] Environment variables not committed to version control
  - [ ] `.env.example` updated with placeholder values

- [ ] **Webhook Security**

  - [ ] Webhook signature validation enabled
  - [ ] HTTPS enforced for webhook endpoint
  - [ ] Webhook verify token is strong and unique
  - [ ] Rate limiting enabled for webhook endpoint

- [ ] **Data Privacy**
  - [ ] IGSID/PSID not logged in plain text
  - [ ] Message content logging complies with privacy policy
  - [ ] Data retention policy implemented
  - [ ] GDPR compliance verified (if applicable)

### 5. Documentation

- [ ] **Technical Documentation**

  - [ ] `docs/INSTAGRAM_SETUP.md` complete and accurate
  - [ ] `docs/META_CREDENTIALS_SETUP.md` complete and accurate
  - [ ] `docs/META_PRODUCTION_DEPLOYMENT.md` complete and accurate
  - [ ] `docs/META_REQUEST_ADAPTER.md` complete and accurate
  - [ ] `docs/TROUBLESHOOTING.md` updated with Meta-specific issues
  - [ ] `docs/API.md` updated with Meta endpoints

- [ ] **Admin Panel Documentation**

  - [ ] `admin-panel/README.md` updated with Meta instructions
  - [ ] Screenshots updated to show Meta/Messenger options
  - [ ] FAQ updated with Meta-specific questions

- [ ] **Operational Documentation**
  - [ ] Deployment checklist reviewed (this document)
  - [ ] Rollback procedure documented
  - [ ] Runbook created for common issues
  - [ ] Update procedure documented

### 6. Admin Panel

- [ ] **Frontend**

  - [ ] Provider selector shows WhatsApp/Instagram/Messenger options
  - [ ] IGSID field visible when Instagram selected
  - [ ] PSID field visible when Messenger selected
  - [ ] Template field hidden for Instagram/Messenger
  - [ ] Multiple images support working (10 for Instagram, 1 for Messenger)
  - [ ] Quick replies interface working
  - [ ] Button template interface working (Messenger only)
  - [ ] 24-hour window warning displayed

- [ ] **Backend**
  - [ ] `admin-panel/api.php` handles provider parameter
  - [ ] Provider routing working correctly
  - [ ] Message filtering by provider working
  - [ ] Provider-specific validation working

### 7. Performance

- [ ] **Load Testing**

  - [ ] System tested under expected load
  - [ ] Response times acceptable (< 2s for API calls)
  - [ ] Database queries optimized
  - [ ] No memory leaks detected

- [ ] **Scalability**
  - [ ] Horizontal scaling tested (if applicable)
  - [ ] Database connection pooling configured
  - [ ] HTTP connection pooling configured
  - [ ] Cache configured (Redis/Memcached if applicable)

## Deployment Steps

### Step 1: Backup

1. **Backup Database**

   ```bash
   mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Backup Configuration**

   ```bash
   cp .env .env.backup_$(date +%Y%m%d_%H%M%S)
   ```

3. **Tag Current Version**
   ```bash
   git tag -a v1.0.0-pre-meta -m "Pre-Meta integration deployment"
   git push origin v1.0.0-pre-meta
   ```

### Step 2: Deploy Code

1. **Pull Latest Code**

   ```bash
   git fetch origin
   git checkout main
   git pull origin main
   ```

2. **Install Dependencies**

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Clear Caches**
   ```bash
   php artisan cache:clear  # If using Laravel
   # OR
   rm -rf storage/cache/*   # If using custom cache
   ```

### Step 3: Database Migration

1. **Run Migrations**

   ```bash
   # Verify migrations first
   ls -la database/migrations/

   # Run migrations
   php bin/migrate.php  # Or your migration command
   ```

2. **Verify Tables**
   ```bash
   mysql -u [user] -p [database] -e "SHOW TABLES;"
   mysql -u [user] -p [database] -e "DESCRIBE webhook_logs;"
   ```

### Step 4: Configuration

1. **Update Environment Variables**

   ```bash
   # Edit .env file
   nano .env

   # Add Meta configuration
   META_PAGE_ACCESS_TOKEN=your_token_here
   META_APP_ID=your_app_id
   META_APP_SECRET=your_app_secret
   META_PAGE_ID=your_page_id
   META_VERIFY_TOKEN=your_verify_token
   ```

2. **Verify Configuration**
   ```bash
   php -r "require 'config/meta.php'; var_dump(config('meta'));"
   ```

### Step 5: Webhook Setup

1. **Configure Webhook in Meta Developer Console**

   - Go to https://developers.facebook.com/apps/
   - Select your app
   - Go to Messenger → Settings
   - Add Callback URL: `https://yourdomain.com/webhook/meta`
   - Add Verify Token: (same as `META_VERIFY_TOKEN`)
   - Subscribe to fields: messages, messaging_postbacks, message_deliveries, message_reads

2. **Test Webhook**

   ```bash
   # Test webhook verification
   curl "https://yourdomain.com/webhook/meta?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"

   # Should return: test123
   ```

### Step 6: Smoke Tests

1. **Test Health Endpoint**

   ```bash
   curl https://yourdomain.com/api/health
   ```

2. **Test Metrics Endpoint**

   ```bash
   curl https://yourdomain.com/api/metrics
   ```

3. **Test Message Send (Instagram)**

   ```bash
   curl -X POST https://yourdomain.com/api/messages/send \
     -H "Content-Type: application/json" \
     -d '{
       "provider": "instagram",
       "recipient": "IGSID_HERE",
       "type": "text",
       "content": "Test message"
     }'
   ```

4. **Test Message Send (Messenger)**
   ```bash
   curl -X POST https://yourdomain.com/api/messages/send \
     -H "Content-Type: application/json" \
     -d '{
       "provider": "messenger",
       "recipient": "PSID_HERE",
       "type": "text",
       "content": "Test message"
     }'
   ```

### Step 7: Monitoring Setup

1. **Verify Dashboards**

   - Open `https://yourdomain.com/admin-panel/monitoring.html`
   - Verify metrics are being collected
   - Check all three dashboards (Messages, Errors, Performance)

2. **Test Alerts**
   - Trigger a test alert
   - Verify alert delivery (email/Slack/etc.)

### Step 8: Documentation Update

1. **Update Internal Wiki/Docs**

   - Document new Meta integration
   - Update runbooks
   - Update on-call procedures

2. **Notify Team**
   - Send deployment notification
   - Share documentation links
   - Schedule knowledge transfer session

## Post-Deployment Verification

### Immediate Checks (0-15 minutes)

- [ ] Application is accessible
- [ ] No critical errors in logs
- [ ] Health endpoint returns 200 OK
- [ ] Metrics endpoint returns data
- [ ] Admin panel loads correctly
- [ ] Provider selector shows all options

### Short-term Checks (15 minutes - 1 hour)

- [ ] Test message sent successfully via Instagram
- [ ] Test message sent successfully via Messenger
- [ ] Webhook receives and processes incoming messages
- [ ] Delivery reports update message status
- [ ] Rate limiter is working
- [ ] Circuit breaker is in closed state

### Medium-term Checks (1-24 hours)

- [ ] No memory leaks detected
- [ ] Response times within acceptable range
- [ ] Error rate < 1%
- [ ] All webhooks processing successfully
- [ ] No database performance issues
- [ ] Monitoring dashboards showing correct data

### Long-term Checks (24+ hours)

- [ ] System stable under production load
- [ ] No unexpected errors
- [ ] Metrics trending as expected
- [ ] User feedback positive
- [ ] No security incidents

## Rollback Criteria

Rollback immediately if:

- [ ] Critical errors affecting > 10% of requests
- [ ] Database corruption detected
- [ ] Security vulnerability discovered
- [ ] Webhook processing failing > 50% of the time
- [ ] System unresponsive or extremely slow
- [ ] Data loss detected

## Success Criteria

Deployment is successful when:

- [ ] All smoke tests passing
- [ ] Error rate < 1%
- [ ] Response time < 2s (p95)
- [ ] Webhook processing success rate > 95%
- [ ] No critical bugs reported
- [ ] Monitoring and alerting working
- [ ] Team trained and comfortable with new system

## Sign-off

- [ ] **Technical Lead**: ********\_******** Date: **\_\_\_**
- [ ] **DevOps Lead**: ********\_******** Date: **\_\_\_**
- [ ] **Product Owner**: ********\_******** Date: **\_\_\_**
- [ ] **Security Review**: ********\_******** Date: **\_\_\_**

## Notes

_Add any deployment-specific notes here_

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-19  
**Next Review**: After first production deployment
