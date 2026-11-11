---
name: code-review
description: Use when reviewing a pull request, diff, implementation, security, maintainability, correctness, or regression risk.
---

# Code Review

Review changed code against the request and surrounding architecture. Prioritize findings by severity and impact.

## Inspect
- Read the diff and relevant surrounding code; understand callers, contracts, permissions, schema, and tests.
- Look for correctness bugs, security flaws, data loss, race conditions, authorization gaps, validation gaps, error handling, performance issues, accessibility, and regressions.
- Check secrets, unsafe SQL, mass assignment, XSS, CSRF, IDOR, insecure file uploads, and logging of sensitive values.
- Confirm migrations are safe and API/UI behavior remains compatible.
- Do not nitpick style when formatter/project conventions already handle it.

## Output
- Findings first, highest severity first; include file/line references and concrete impact.
- Separate confirmed defects from questions or suggestions.
- State tests/checks reviewed and gaps.
- If no findings, say so but note review scope and untested areas.
