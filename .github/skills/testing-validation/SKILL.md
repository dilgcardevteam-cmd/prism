---
name: testing-validation
description: Use when writing or running tests, building QA plans, validating acceptance criteria, regression checks, or troubleshooting test failures.
---

# Testing and Validation

- Inspect test framework, existing fixtures/factories, conventions, and CI commands before writing tests.
- Translate requirements into observable acceptance criteria and edge cases.
- Test behavior rather than implementation details; use realistic factories and isolate external services.
- Cover happy path, invalid input, authorization denial, boundary values, failure/retry, and regression scenarios as applicable.
- Keep tests deterministic; avoid real production services/data and flaky timing.
- Do not alter tests merely to hide a defect; explain changes to assertions and expected behavior.
- Run focused tests first, then broader relevant suite if practical.
- Record exact command, pass/fail, skipped tests, and environmental blockers.
- If tests cannot run, provide manual verification steps and state that automated validation remains incomplete.
