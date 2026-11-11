---
name: production-deployment
description: Use when preparing or executing deployment, server configuration, release, environment variables, queues, backups, rollback, or production operations.
---

# Production Deployment

## Prepare
- Identify target host/environment, deployment method, runtime, web server, database, queue/cache/session drivers, storage, and release process.
- Review Git status, commit/tag, dependency lockfiles, environment configuration (never print secrets), health checks, and migration plan.
- Verify backups, restore confidence, maintenance/downtime expectations, rollback artifact, and responsible approval.
- Check compatibility of PHP/extensions, Composer dependencies, frontend build, permissions, storage links, TLS, DNS, and firewall.

## Release safety
- Production deployment, restart, migration, destructive command, DNS/firewall change, or secret rotation requires explicit authorization for the specific target/action.
- Present commands and impact before execution; never assume approval.
- Prefer release directories/atomic switches and backward-compatible migrations where available.
- Do not run `migrate:fresh`, `db:wipe`, or destructive rollback in production.
- Keep secrets out of source control, logs, chat, and command history.

## Post-deploy
- Verify health endpoint, login, critical workflows, logs, queue workers, scheduled tasks, storage, and monitoring.
- Confirm version/release and database compatibility.
- If errors occur, stop risky follow-up actions; use the approved rollback/recovery plan.
- Report release, checks, issues, and rollback status precisely.
