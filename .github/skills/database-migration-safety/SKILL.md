---
name: database-migration-safety
description: Use before creating, reviewing, running, rolling back, or modifying Laravel migrations, schema changes, or bulk data operations.
---

# Database Migration Safety

## Preflight (mandatory)
- Determine target environment and database; inspect migration status and current schema.
- Inspect Git status and migration history; preserve user work.
- Identify affected tables, rows, constraints, indexes, relationships, and application versions.
- Classify operation: additive, backfill, constraint change, rename, destructive, or irreversible.
- Estimate lock/downtime and data-loss risk; prepare backup and rollback plan.

## Design
- Prefer backward-compatible expand → migrate/backfill → contract changes.
- For large tables, consider batched/chunked backfills and online-safe index strategies supported by the DB engine.
- Do not assume `down()` can restore deleted or transformed data.
- Make migrations deterministic and safe across environments.
- Avoid editing an already-applied migration to change deployed schema; create a new migration unless the user confirms it is unreleased/local-only.
- Check type precision, defaults, nullability, foreign keys, and index names.

## Execution gate
- Never run migrations against production, `migrate:fresh`, `db:wipe`, `DROP`, `TRUNCATE`, mass delete/update, or rollback that can lose data without explicit authorization for that target and operation.
- Before execution, state target, exact command, impact, backup, and rollback limitations; obtain approval.
- Do not infer approval from a general request to fix code.

## Verify
- Test migration up/down on disposable data where possible.
- Check schema and representative data before/after.
- Confirm application compatibility and tests.
- Document deployment ordering and recovery plan.
