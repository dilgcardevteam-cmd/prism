---
name: excel-import-export
description: Use for Excel/CSV imports, exports, bulk uploads, row validation, column mapping, large files, or import error reporting.
---

# Excel Import and Export

## Import design
- Inspect file format, headers, sample rows, expected schema, existing import package, and size/timeout constraints.
- Validate extension/MIME and actual file content; enforce size limits; treat spreadsheet content as untrusted.
- Define an explicit column mapping and normalization rules (dates, IDs, decimal/currency, enums, text encoding).
- Validate rows before writing; return actionable row/column errors and a downloadable error report when useful.
- Decide transaction strategy: all-or-nothing for small batches, or documented partial/chunked processing for large imports.
- Use chunking/queues for large files; ensure retries are idempotent and duplicate handling is explicit.
- Protect against formula injection in exported CSV/Excel values; do not evaluate uploaded formulas/macros.
- Avoid silent truncation, lossy numeric conversions, and ambiguous date parsing.
- Check DB constraints and relationships; distinguish missing, invalid, and unknown references.

## Export
- Authorize export scope and avoid leaking fields/records.
- Escape/formula-neutralize untrusted values where spreadsheet clients may execute formulas.
- Preserve numeric precision, dates, and meaningful headings.

## Verify
- Test valid, invalid, duplicate, empty, oversized, malformed, and boundary-value files.
- Confirm row counts, rejected rows, error messages, memory/time behavior, and no unintended writes.
- Report mapping assumptions and import outcome accurately.
