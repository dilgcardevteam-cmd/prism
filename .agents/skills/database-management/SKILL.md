---
name: database-management
description: Use when inspecting, designing, querying, or changing database schemas, Eloquent models, relationships, indexes, or data integrity.
---

# Database Management

## Inspect
- Identify database engine/version, connection configuration (without printing secrets), table definitions, indexes, foreign keys, constraints, migration history, and model relationships.
- Confirm actual schema before assuming column names/types or data shape.

## Query and model practices
- Parameterize queries; never concatenate untrusted values into SQL.
- Use explicit selected columns when useful, indexes for demonstrated query patterns, and eager loading to avoid N+1.
- Define relationship cardinality and foreign keys consistently.
- Preserve decimal precision for currency/contract amounts; avoid lossy float storage for money.
- Normalize/validate dates, enums/status values, and nullable fields deliberately.
- Use transactions and appropriate locking for concurrent critical updates.
- Consider tenant/LGU scoping and authorization on every read/write.

## Data integrity
- Check duplicate, null, orphan, and invalid records before bulk changes.
- Use chunking for large datasets; make jobs retry-safe and idempotent.
- Provide a preview/count and backup/rollback strategy for bulk updates.
- Never expose production records in logs or examples.

## Verification
- Explain query assumptions and expected impact.
- Run relevant tests and inspect query plans when performance is at issue.
- Confirm constraints and relationships remain valid after changes.
