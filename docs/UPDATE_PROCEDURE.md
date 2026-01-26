# Meta Messaging Integration - Update Procedure

## Overview

This document describes the procedure for updating the Meta Messaging Integration with new features, bug fixes, or configuration changes. The update process is designed to minimize downtime and ensure system stability.

## Update Types

### Type 1: Hotfix (Critical Bug Fix)

**Characteristics**:

- Critical bug affecting production
- Security vulnerability
- Data loss risk
- Requires immediate deployment

**Timeline**: Deploy within 1-4 hours
**Testing**: Minimal (critical path only)
**Approval**: Technical Lead + On-Call Engineer

### Type 2: Minor Update (Bug Fixes)

**Characteristics**:

- Non-critical bug fixes
- Performance improvements
- Minor feature enhancements
- Configuration changes

**Timeline**: Deploy within 1-3 days
**Testing**: Standard test suite
**Approval**: Technical Lead

### Type 3: Major Update (New Features)

**Characteristics**:

- New features
- API changes
- Database schema changes
- Architecture changes

**Timeline**: Deploy within 1-2 weeks
**Testing**: Full test suite + integration tests
**Approval**: Technical Lead + Product Owner

### Type 4: Configuration Update

**Characteristics**:

- Environment variable changes
- Rate limit adjustments
- Feature flags
- No code changes

**Timeline**: Deploy within hours
**Testing**: Smoke tests only
**Approval**: DevOps Lead

---

## Pre-Update Checklist

### For All Updates

- [ ] **Change Documentation**

  - [ ] Change description documented
  - [ ] Impact assessment completed
  - [ ] Rollback plan prepared
  - [ ] Stakeholders notified

- [ ] **Code Quality**

  - [ ] Code reviewed and approved
  - [ ] Tests passing in development
  - [ ] No merge conflicts
  - [ ] Version tagged in git

- [ ] **Testing**

  - [ ] Unit tests passing
  - [ ] Integration tests passing (if applicable)
  - [ ] Manual testing completed
  - [ ] Performance impact assessed

- [ ] **Backup**
  - [ ] Database backup completed
  - [ ] Configuration backup completed
  - [ ] Current version tagged

### For Database Changes

- [ ] **Migration Preparation**

  - [ ] Migration scripts tested
  - [ ] Rollback scripts prepared
  - [ ] Data migration plan documented
  - [ ] Downtime estimated

- [ ] **Data Integrity**
  - [ ] Backup verified
  - [ ] Migration tested on staging
  - [ ] Data validation queries prepared
  - [ ] Rollback tested

### For API Changes

- [ ] **Compatibility**
  - [ ] Backward compatibility verified
  - [ ] API documentation updated
  - [ ] Client impact assessed
  - [ ] Deprecation notices added (if applicable)

---

## Update Procedures

### Procedure 1: Hotfix Deployment

**Use Case**: Critical bug fix that must be deployed immediately

#### Step 1: Prepare Hotfix (15-30 minutes)

```bash
# Create hotfix branch
git checkout main
git pull origin main
git checkout -b hotfix/critical-bug-fix

# Make changes
# ... edit files ...

# Commit changes
git add .
git commit -m "Hotfix: Fix critical bug in Meta message sending"

# Push to remote
git push origin hotfix/critical-bug-fix
```

#### Step 2: Test Hotfix (15-30 minutes)

```bash
# Run critical path tests
vendor/bin/pest tests/Unit/Providers/MetaProviderTest.php
vendor/bin/pest tests/Integration/MetaMessageFlowTest.php

# Manual testing
# Test the specific bug fix
```

#### Step 3: Get Approval (5-15 minutes)

- Create pull request
- Get approval from Technical Lead
- Get approval from On-Call Engineer

#### Step 4: Deploy to Production (15-30 minutes)

```bash
# Merge to main
git checkout main
git merge hotfix/critical-bug-fix
git push origin main

# Tag version
git tag -a v1.0.1 -m "Hotfix: Critical bug fix"
git push origin v1.0.1

# Deploy to production
ssh production-server
cd /var/www/html
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl restart php-fpm
```

#### Step 5: Verify (10-15 minutes)

```bash
# Smoke tests
curl https://yourdomain.com/api/health
curl https://yourdomain.com/api/metrics

# Test the fix
# ... specific test for the bug ...

# Monitor logs
tail -f storage/logs/app.log
```

#### Step 6: Monitor (1-2 hours)

- Watch error rate
- Monitor response times
- Check for new issues
- Verify fix is working

**Total Time**: 1-2 hours

---

### Procedure 2: Minor Update Deployment

**Use Case**: Bug fixes, performance improvements, minor features

#### Step 1: Prepare Update (1-2 days)

```bash
# Create feature branch
git checkout main
git pull origin main
git checkout -b feature/minor-update

# Make changes
# ... edit files ...

# Commit changes
git add .
git commit -m "Feature: Add minor improvement"
git push origin feature/minor-update
```

#### Step 2: Test Update (4-8 hours)

```bash
# Run full test suite
vendor/bin/pest

# Run integration tests
vendor/bin/pest tests/Integration

# Manual testing
# Test all affected functionality

# Performance testing
# Verify no performance regression
```

#### Step 3: Code Review (2-4 hours)

- Create pull request
- Address review comments
- Get approval from Technical Lead
- Merge to main

#### Step 4: Deploy to Staging (1-2 hours)

```bash
# Deploy to staging
ssh staging-server
cd /var/www/html
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl restart php-fpm

# Run smoke tests on staging
curl https://staging.yourdomain.com/api/health

# Test functionality on staging
# ... comprehensive testing ...
```

#### Step 5: Deploy to Production (1-2 hours)

```bash
# Backup database
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql

# Tag version
git tag -a v1.1.0 -m "Minor update: Bug fixes and improvements"
git push origin v1.1.0

# Deploy to production
ssh production-server
cd /var/www/html
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl restart php-fpm
```

#### Step 6: Verify and Monitor (2-4 hours)

```bash
# Smoke tests
curl https://yourdomain.com/api/health
curl https://yourdomain.com/api/metrics

# Monitor metrics
watch -n 10 'curl -s https://yourdomain.com/api/metrics | jq .'

# Check logs
tail -f storage/logs/app.log
```

**Total Time**: 1-3 days

---

### Procedure 3: Major Update Deployment

**Use Case**: New features, API changes, database schema changes

#### Step 1: Planning (1-3 days)

- [ ] Create detailed update plan
- [ ] Identify all affected components
- [ ] Plan database migrations
- [ ] Estimate downtime (if any)
- [ ] Schedule deployment window
- [ ] Notify stakeholders

#### Step 2: Development (1-2 weeks)

```bash
# Create feature branch
git checkout main
git pull origin main
git checkout -b feature/major-update

# Develop feature
# ... make changes ...

# Commit regularly
git add .
git commit -m "Feature: Add major feature"
git push origin feature/major-update
```

#### Step 3: Testing (2-3 days)

```bash
# Run full test suite
vendor/bin/pest

# Run integration tests
vendor/bin/pest tests/Integration

# Run property-based tests
vendor/bin/pest tests/Property

# Performance testing
# Load testing
# Security testing

# User acceptance testing
# ... UAT with stakeholders ...
```

#### Step 4: Code Review (1-2 days)

- Create pull request
- Comprehensive code review
- Address all comments
- Get approvals from:
  - Technical Lead
  - Senior Engineers
  - Product Owner (if needed)

#### Step 5: Staging Deployment (1 day)

```bash
# Deploy to staging
ssh staging-server
cd /var/www/html
git pull origin main

# Run database migrations
php bin/migrate.php

# Install dependencies
composer install --no-dev --optimize-autoloader

# Restart services
sudo systemctl restart php-fpm

# Comprehensive testing on staging
# ... full regression testing ...
```

#### Step 6: Production Deployment (2-4 hours)

**Pre-Deployment**:

```bash
# Backup everything
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql
cp .env .env.backup_$(date +%Y%m%d_%H%M%S)

# Tag version
git tag -a v2.0.0 -m "Major update: New features"
git push origin v2.0.0

# Notify users (if needed)
# Send notification about upcoming deployment
```

**Deployment**:

```bash
# Enable maintenance mode (if needed)
ssh production-server
cd /var/www/html
touch maintenance.flag

# Pull latest code
git pull origin main

# Run database migrations
php bin/migrate.php

# Install dependencies
composer install --no-dev --optimize-autoloader

# Update configuration (if needed)
nano .env

# Clear caches
rm -rf storage/cache/*

# Restart services
sudo systemctl restart php-fpm
sudo systemctl restart nginx

# Disable maintenance mode
rm maintenance.flag
```

**Post-Deployment**:

```bash
# Smoke tests
curl https://yourdomain.com/api/health
curl https://yourdomain.com/api/metrics

# Test new features
# ... comprehensive testing ...

# Monitor closely
tail -f storage/logs/app.log
watch -n 10 'curl -s https://yourdomain.com/api/metrics | jq .'
```

#### Step 7: Monitoring (24-48 hours)

- Monitor error rates
- Monitor response times
- Check for memory leaks
- Verify new features working
- Collect user feedback
- Address any issues immediately

**Total Time**: 1-2 weeks

---

### Procedure 4: Configuration Update

**Use Case**: Environment variable changes, rate limit adjustments

#### Step 1: Prepare Configuration (15-30 minutes)

```bash
# Backup current configuration
ssh production-server
cd /var/www/html
cp .env .env.backup_$(date +%Y%m%d_%H%M%S)

# Document changes
# Create change document with:
# - What's changing
# - Why it's changing
# - Expected impact
# - Rollback plan
```

#### Step 2: Test Configuration (30-60 minutes)

```bash
# Test on staging first
ssh staging-server
cd /var/www/html

# Update configuration
nano .env

# Restart services
sudo systemctl restart php-fpm

# Verify configuration
php -r "require 'config/meta.php'; var_dump(config('meta'));"

# Test functionality
curl https://staging.yourdomain.com/api/health
```

#### Step 3: Apply to Production (15-30 minutes)

```bash
# Update production configuration
ssh production-server
cd /var/www/html

# Edit configuration
nano .env

# Verify changes
cat .env | grep META_

# Restart services (graceful reload)
sudo systemctl reload php-fpm

# Or full restart if needed
sudo systemctl restart php-fpm
```

#### Step 4: Verify (15-30 minutes)

```bash
# Verify configuration loaded
php -r "require 'config/meta.php'; var_dump(config('meta'));"

# Test functionality
curl https://yourdomain.com/api/health
curl https://yourdomain.com/api/metrics

# Monitor for issues
tail -f storage/logs/app.log
```

**Total Time**: 1-2 hours

---

## Post-Update Procedures

### Immediate Actions (0-1 hour)

1. **Verify Deployment**

   - [ ] Health check passing
   - [ ] Metrics endpoint working
   - [ ] No critical errors in logs
   - [ ] Core functionality working

2. **Update Documentation**

   - [ ] Update CHANGELOG.md
   - [ ] Update version in documentation
   - [ ] Update API documentation (if changed)

3. **Notify Stakeholders**
   - [ ] Send deployment notification
   - [ ] Update status page
   - [ ] Notify team in Slack/Teams

### Short-term Actions (1-24 hours)

1. **Monitor System**

   - [ ] Watch error rates
   - [ ] Monitor response times
   - [ ] Check for memory leaks
   - [ ] Verify metrics trending normally

2. **Collect Feedback**

   - [ ] Check for user reports
   - [ ] Monitor support tickets
   - [ ] Review logs for issues

3. **Performance Verification**
   - [ ] Verify no performance regression
   - [ ] Check database performance
   - [ ] Verify rate limiting working

### Long-term Actions (1-7 days)

1. **Post-Deployment Review**

   - [ ] Review deployment process
   - [ ] Document lessons learned
   - [ ] Identify improvements
   - [ ] Update procedures

2. **Cleanup**

   - [ ] Remove old backups (keep last 5)
   - [ ] Clean up old logs
   - [ ] Archive deployment artifacts

3. **Documentation**
   - [ ] Update runbooks if needed
   - [ ] Update troubleshooting guide
   - [ ] Share knowledge with team

---

## Rollback Procedures

If issues are detected after deployment, follow the rollback procedure:

See [ROLLBACK_PROCEDURE.md](./ROLLBACK_PROCEDURE.md) for detailed instructions.

**Quick Rollback**:

```bash
# Rollback code
git checkout v1.0.0  # Previous version
composer install --no-dev --optimize-autoloader
sudo systemctl restart php-fpm

# Rollback database (if needed)
mysql -u [user] -p [database] < backup_YYYYMMDD_HHMMSS.sql

# Rollback configuration (if needed)
cp .env.backup_YYYYMMDD_HHMMSS .env
sudo systemctl restart php-fpm
```

---

## Update Checklist Template

Use this template for each update:

```markdown
# Update: [Update Name]

**Type**: Hotfix / Minor / Major / Configuration
**Version**: v1.x.x
**Date**: YYYY-MM-DD
**Owner**: [Name]

## Description

[Brief description of the update]

## Changes

- [ ] Change 1
- [ ] Change 2
- [ ] Change 3

## Testing

- [ ] Unit tests passing
- [ ] Integration tests passing
- [ ] Manual testing completed
- [ ] Performance verified

## Deployment

- [ ] Staging deployed
- [ ] Staging verified
- [ ] Production deployed
- [ ] Production verified

## Rollback Plan

[Describe rollback procedure if needed]

## Monitoring

- [ ] Error rate normal
- [ ] Response time normal
- [ ] No critical issues

## Sign-off

- [ ] Technical Lead: **\_\_\_** Date: **\_\_\_**
- [ ] DevOps: **\_\_\_** Date: **\_\_\_**
- [ ] Product Owner: **\_\_\_** Date: **\_\_\_** (if needed)
```

---

## Best Practices

### General

1. **Always backup before updates**

   - Database backup
   - Configuration backup
   - Tag current version

2. **Test thoroughly**

   - Run full test suite
   - Test on staging first
   - Manual testing of critical paths

3. **Deploy during low-traffic periods**

   - Schedule deployments during off-peak hours
   - Notify users in advance if downtime expected

4. **Monitor closely after deployment**

   - Watch logs in real-time
   - Monitor metrics continuously
   - Be ready to rollback

5. **Document everything**
   - Document changes
   - Document issues encountered
   - Update procedures based on learnings

### Database Updates

1. **Always have rollback scripts**

   - Test rollback on staging
   - Keep rollback scripts with migrations

2. **Minimize downtime**

   - Use online schema changes if possible
   - Plan migrations carefully
   - Consider blue-green deployments

3. **Verify data integrity**
   - Run validation queries before and after
   - Check row counts
   - Verify critical data

### Configuration Updates

1. **Test on staging first**

   - Never change production config without testing

2. **Use graceful reloads**

   - Use `reload` instead of `restart` when possible
   - Minimize service interruption

3. **Document configuration changes**
   - Update .env.example
   - Document in CHANGELOG
   - Update configuration documentation

---

## Troubleshooting Updates

### Issue: Update Fails to Deploy

**Symptoms**: Git pull fails, composer install fails, or services won't start

**Resolution**:

1. Check error messages
2. Verify file permissions
3. Check disk space
4. Verify dependencies
5. Rollback if necessary

### Issue: Tests Fail After Update

**Symptoms**: Tests passing locally but failing on server

**Resolution**:

1. Check environment differences
2. Verify database state
3. Check configuration
4. Review test logs
5. Fix issues or rollback

### Issue: Performance Degradation After Update

**Symptoms**: Slow response times, high CPU/memory usage

**Resolution**:

1. Check for N+1 queries
2. Verify caching working
3. Check for memory leaks
4. Review new code for inefficiencies
5. Rollback if severe

---

## Appendix

### Version Numbering

Follow Semantic Versioning (semver):

- **Major** (v2.0.0): Breaking changes, major features
- **Minor** (v1.1.0): New features, backward compatible
- **Patch** (v1.0.1): Bug fixes, backward compatible

### Deployment Schedule

Recommended deployment windows:

- **Hotfixes**: Anytime (emergency)
- **Minor Updates**: Tuesday-Thursday, 10 AM - 2 PM
- **Major Updates**: Tuesday-Wednesday, 10 AM - 12 PM
- **Configuration**: Anytime during business hours

Avoid:

- Mondays (start of week, high traffic)
- Fridays (no time to fix issues before weekend)
- Weekends (limited support available)
- Holidays (limited support available)

### Useful Commands

```bash
# Check current version
git describe --tags

# List recent versions
git tag -l --sort=-version:refname | head -10

# View changes between versions
git log v1.0.0..v1.1.0 --oneline

# Check what's deployed
ssh production-server 'cd /var/www/html && git describe --tags'

# Quick health check
curl -s https://yourdomain.com/api/health | jq .

# Check metrics
curl -s https://yourdomain.com/api/metrics | jq .
```

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-19  
**Next Review**: Quarterly or after major updates
