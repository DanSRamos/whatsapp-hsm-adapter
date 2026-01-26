# Meta Messaging Integration - Rollback Procedure

## Overview

This document describes the procedure for rolling back the Meta Messaging Integration deployment in case of critical issues. The rollback process is designed to restore the system to its previous stable state with minimal downtime.

## When to Rollback

### Critical Issues (Immediate Rollback Required)

- **Data Loss**: Any indication of message data loss or corruption
- **Security Breach**: Security vulnerability actively being exploited
- **System Outage**: Complete system failure or unavailability
- **Database Corruption**: Database integrity compromised
- **High Error Rate**: Error rate > 10% for more than 5 minutes
- **Webhook Failure**: Webhook processing failing > 50% for more than 10 minutes

### Major Issues (Rollback Recommended)

- **Performance Degradation**: Response times > 5s (p95) for more than 15 minutes
- **Memory Leaks**: Memory usage growing unbounded
- **Integration Failures**: Meta API integration completely broken
- **Data Inconsistency**: Significant data inconsistencies detected
- **Critical Bug**: Bug affecting core functionality

### Minor Issues (Rollback Optional)

- **UI Issues**: Admin panel display issues (can be fixed forward)
- **Non-critical Bugs**: Bugs affecting edge cases only
- **Performance Issues**: Minor performance degradation (< 2x baseline)
- **Logging Issues**: Problems with logging or monitoring

## Rollback Decision Matrix

| Issue Severity | Error Rate | Response Time | Action                   |
| -------------- | ---------- | ------------- | ------------------------ |
| Critical       | > 10%      | Any           | **ROLLBACK IMMEDIATELY** |
| Major          | 5-10%      | > 5s          | **ROLLBACK RECOMMENDED** |
| Major          | < 5%       | > 5s          | **MONITOR & DECIDE**     |
| Minor          | < 5%       | < 5s          | **FIX FORWARD**          |

## Pre-Rollback Checklist

Before initiating rollback:

- [ ] **Confirm Issue Severity**

  - [ ] Issue documented with evidence (logs, metrics, screenshots)
  - [ ] Issue severity assessed using decision matrix
  - [ ] Rollback decision approved by Technical Lead

- [ ] **Notify Stakeholders**

  - [ ] Team notified via Slack/Teams
  - [ ] On-call engineer alerted
  - [ ] Product owner informed
  - [ ] Users notified (if customer-facing impact)

- [ ] **Capture Diagnostic Data**
  - [ ] Recent logs exported
  - [ ] Database state captured
  - [ ] Metrics screenshots taken
  - [ ] Error traces collected

## Rollback Procedure

### Phase 1: Immediate Mitigation (0-5 minutes)

#### Step 1: Enable Maintenance Mode (Optional)

If system is unstable, enable maintenance mode:

```bash
# Create maintenance flag
touch /var/www/html/maintenance.flag

# Or use your framework's maintenance mode
php artisan down  # Laravel
```

#### Step 2: Stop Webhook Processing

Prevent new webhooks from being processed:

```bash
# Option 1: Disable webhook endpoint temporarily
# Edit nginx/apache config to return 503 for /webhook/meta

# Option 2: Set environment variable
echo "WEBHOOKS_ENABLED=false" >> .env

# Reload web server
sudo systemctl reload nginx
# OR
sudo systemctl reload apache2
```

### Phase 2: Code Rollback (5-15 minutes)

#### Step 3: Revert to Previous Version

```bash
# Navigate to application directory
cd /var/www/html

# Check current version
git describe --tags

# List recent tags
git tag -l --sort=-version:refname | head -5

# Checkout previous stable version
git checkout v1.0.0-pre-meta  # Or appropriate tag

# Alternative: Revert specific commits
# git revert <commit-hash> --no-commit
# git commit -m "Rollback Meta integration"
```

#### Step 4: Restore Dependencies

```bash
# Restore previous dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
rm -rf storage/cache/*
php artisan cache:clear  # If using Laravel
```

### Phase 3: Database Rollback (15-25 minutes)

#### Step 5: Assess Database Changes

```bash
# Check if new tables were created
mysql -u [user] -p [database] -e "SHOW TABLES LIKE 'webhook_logs';"

# Check if columns were added
mysql -u [user] -p [database] -e "DESCRIBE messages;"
```

#### Step 6: Rollback Database (If Necessary)

**Option A: Rollback Migrations (Preferred)**

```bash
# If using migration system
php bin/migrate.php rollback

# Or manually drop new tables
mysql -u [user] -p [database] << EOF
DROP TABLE IF EXISTS webhook_logs;
-- Add other rollback SQL as needed
EOF
```

**Option B: Restore from Backup (If Corrupted)**

```bash
# Stop application
sudo systemctl stop php-fpm  # Or your PHP service

# Restore database backup
mysql -u [user] -p [database] < backup_YYYYMMDD_HHMMSS.sql

# Verify restoration
mysql -u [user] -p [database] -e "SELECT COUNT(*) FROM messages;"

# Start application
sudo systemctl start php-fpm
```

### Phase 4: Configuration Rollback (25-30 minutes)

#### Step 7: Restore Configuration

```bash
# Restore previous .env file
cp .env.backup_YYYYMMDD_HHMMSS .env

# Or remove Meta-specific configuration
sed -i '/META_/d' .env

# Verify configuration
cat .env | grep -E "(META_|PROVIDER)"
```

#### Step 8: Update Provider Configuration

```bash
# Edit config/providers.php to remove Meta
nano config/providers.php

# Ensure only previous providers are enabled
# Remove 'instagram' and 'messenger' from supported providers list
```

### Phase 5: Verification (30-40 minutes)

#### Step 9: Smoke Tests

```bash
# Test health endpoint
curl https://yourdomain.com/api/health
# Expected: {"status":"ok"}

# Test WhatsApp message send (if applicable)
curl -X POST https://yourdomain.com/api/messages/send \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "whatsapp",
    "recipient": "+1234567890",
    "type": "text",
    "content": "Test after rollback"
  }'

# Check logs for errors
tail -f storage/logs/app.log
```

#### Step 10: Verify Core Functionality

- [ ] Admin panel loads correctly
- [ ] WhatsApp messages can be sent
- [ ] Incoming messages are processed
- [ ] Database queries working
- [ ] No critical errors in logs

### Phase 6: Re-enable System (40-45 minutes)

#### Step 11: Re-enable Webhooks

```bash
# Remove maintenance flag
rm /var/www/html/maintenance.flag

# Re-enable webhooks
sed -i 's/WEBHOOKS_ENABLED=false/WEBHOOKS_ENABLED=true/' .env

# Reload web server
sudo systemctl reload nginx
```

#### Step 12: Disable Maintenance Mode

```bash
# Disable maintenance mode
php artisan up  # Laravel

# Or remove maintenance flag
rm /var/www/html/maintenance.flag
```

### Phase 7: Monitoring (45+ minutes)

#### Step 13: Monitor System Health

```bash
# Watch logs in real-time
tail -f storage/logs/app.log

# Monitor error rate
watch -n 5 'curl -s https://yourdomain.com/api/metrics | jq .error_rate'

# Monitor response time
watch -n 5 'curl -s https://yourdomain.com/api/metrics | jq .response_time_p95'
```

#### Step 14: Verify Metrics

- [ ] Error rate < 1%
- [ ] Response time < 2s (p95)
- [ ] No memory leaks
- [ ] Database performance normal
- [ ] Webhook processing working (if applicable)

## Post-Rollback Actions

### Immediate Actions (0-1 hour)

1. **Update Status Page**

   - Mark incident as resolved
   - Provide brief explanation
   - Set next update time

2. **Notify Stakeholders**

   - Send rollback completion notification
   - Provide system status update
   - Confirm normal operations resumed

3. **Document Incident**
   - Create incident report
   - Document root cause (if known)
   - List actions taken
   - Note time to resolution

### Short-term Actions (1-24 hours)

1. **Root Cause Analysis**

   - Analyze logs and metrics
   - Identify root cause
   - Document findings
   - Create action items

2. **Fix Planning**

   - Create fix plan
   - Estimate fix timeline
   - Identify testing requirements
   - Schedule fix deployment

3. **Communication**
   - Send detailed incident report
   - Share lessons learned
   - Update documentation
   - Schedule post-mortem meeting

### Long-term Actions (1-7 days)

1. **Post-Mortem Meeting**

   - Review incident timeline
   - Discuss root cause
   - Identify improvements
   - Assign action items

2. **Process Improvements**

   - Update deployment checklist
   - Improve testing procedures
   - Enhance monitoring
   - Update rollback procedure

3. **Fix Implementation**
   - Implement fix in development
   - Test thoroughly
   - Deploy to staging
   - Schedule production deployment

## Rollback Validation Checklist

After rollback, verify:

- [ ] **Application Status**

  - [ ] Application accessible
  - [ ] Health endpoint returns 200 OK
  - [ ] Admin panel loads correctly
  - [ ] No critical errors in logs

- [ ] **Core Functionality**

  - [ ] Messages can be sent (WhatsApp)
  - [ ] Messages can be received
  - [ ] Database queries working
  - [ ] API endpoints responding

- [ ] **Performance**

  - [ ] Response time < 2s (p95)
  - [ ] Error rate < 1%
  - [ ] Memory usage normal
  - [ ] CPU usage normal

- [ ] **Data Integrity**
  - [ ] No data loss detected
  - [ ] Message history intact
  - [ ] Database consistency verified
  - [ ] Backups available

## Emergency Contacts

| Role             | Name   | Contact       |
| ---------------- | ------ | ------------- |
| Technical Lead   | [Name] | [Phone/Email] |
| DevOps Lead      | [Name] | [Phone/Email] |
| Database Admin   | [Name] | [Phone/Email] |
| On-Call Engineer | [Name] | [Phone/Email] |
| Product Owner    | [Name] | [Phone/Email] |

## Rollback Time Estimates

| Phase                  | Estimated Time | Critical Path |
| ---------------------- | -------------- | ------------- |
| Immediate Mitigation   | 0-5 minutes    | Yes           |
| Code Rollback          | 5-15 minutes   | Yes           |
| Database Rollback      | 15-25 minutes  | Yes           |
| Configuration Rollback | 25-30 minutes  | Yes           |
| Verification           | 30-40 minutes  | Yes           |
| Re-enable System       | 40-45 minutes  | Yes           |
| **Total**              | **45 minutes** |               |

## Common Rollback Scenarios

### Scenario 1: Meta API Integration Broken

**Symptoms**: All Meta messages failing, 100% error rate for Instagram/Messenger

**Rollback Steps**:

1. Disable Meta provider in `config/providers.php`
2. Clear cache
3. Verify WhatsApp still working
4. No database rollback needed

**Time**: 10 minutes

### Scenario 2: Database Migration Failed

**Symptoms**: Database errors, application crashes, data corruption

**Rollback Steps**:

1. Stop application
2. Restore database from backup
3. Rollback code to previous version
4. Verify data integrity
5. Restart application

**Time**: 30 minutes

### Scenario 3: Performance Degradation

**Symptoms**: Slow response times, high CPU/memory usage

**Rollback Steps**:

1. Rollback code to previous version
2. Clear caches
3. Restart services
4. Monitor performance
5. No database rollback needed

**Time**: 15 minutes

### Scenario 4: Webhook Processing Failures

**Symptoms**: Webhooks failing, messages not being received

**Rollback Steps**:

1. Disable webhook endpoint
2. Rollback code
3. Re-enable webhooks
4. Test webhook processing
5. No database rollback needed

**Time**: 20 minutes

## Lessons Learned Template

After each rollback, document:

```markdown
## Incident: [Brief Description]

**Date**: YYYY-MM-DD
**Duration**: X hours
**Severity**: Critical/Major/Minor

### Timeline

- HH:MM - Issue detected
- HH:MM - Rollback initiated
- HH:MM - Rollback completed
- HH:MM - System verified

### Root Cause

[Detailed explanation of what went wrong]

### Impact

- Users affected: X
- Messages lost: X
- Downtime: X minutes

### Actions Taken

1. [Action 1]
2. [Action 2]
3. [Action 3]

### Lessons Learned

1. [Lesson 1]
2. [Lesson 2]
3. [Lesson 3]

### Action Items

- [ ] [Action item 1] - Owner: [Name] - Due: [Date]
- [ ] [Action item 2] - Owner: [Name] - Due: [Date]
```

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-19  
**Next Review**: After first rollback or quarterly
