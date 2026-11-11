---
name: laravel-debugging
description: Use when diagnosing Laravel exceptions, broken features, HTTP errors, logs, SQL errors, queue failures, or unexpected application behavior.
---

# Laravel Debugging

1. Reproduce or precisely characterize the issue; capture expected vs actual behavior and exact error/stack trace.
2. Inspect `storage/logs`, relevant request/response, route, controller, validation, model, query, job, and configuration (never reveal secret values).
3. Check Laravel/PHP/dependency versions and environment differences.
4. Trace the failure from entry point to root cause; distinguish symptom from cause.
5. Form a minimal hypothesis and inspect evidence before changing code.
6. Make the smallest fix; avoid suppressing exceptions, weakening validation, or disabling security.
7. Add a regression test when feasible.
8. Re-run the reproduction and focused tests; verify adjacent behavior.
9. Report root cause, fix, files changed, commands/results, and any unresolved uncertainty.

Do not run destructive commands or reset databases to debug. Redact credentials, tokens, personal data, and production details from logs.
