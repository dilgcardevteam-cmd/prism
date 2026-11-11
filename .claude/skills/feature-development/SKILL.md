---
name: feature-development
description: Use for end-to-end feature requests requiring analysis, planning, implementation across files, validation, and a clear completion report.
---

# Feature Development Workflow

1. **Clarify outcome:** translate the request into observable acceptance criteria; ask only if a missing detail materially changes behavior or risk.
2. **Inspect:** Git status, architecture, routes, related UI, schema, permissions, integrations, and tests.
3. **Select skills:** read every relevant skill (Laravel, UI, database, auth, Excel, testing, etc.).
4. **Impact map:** list files/components, data changes, roles affected, API/report compatibility, and risks.
5. **Plan:** concise implementation steps and validation. Seek approval for major architecture, destructive changes, paid dependencies, or production operations.
6. **Implement:** smallest cohesive change; preserve unrelated behavior; update docs/tests as needed.
7. **Validate:** run focused tests, static checks, and manual checks available; test unauthorized/invalid/error paths.
8. **Review:** inspect complete diff, regressions, security, accessibility, migrations, and accidental secrets.
9. **Report:** acceptance criteria met, files changed, data/migration impact, commands and results, limitations.

Do not claim completion for untested behavior. Keep project-specific requirements in the project's own instructions, not this generic skill.
