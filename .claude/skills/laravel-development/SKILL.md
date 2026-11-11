---
name: laravel-development
description: Use when building or modifying Laravel applications, routes, controllers, models, middleware, services, Blade views, APIs, jobs, or application architecture.
---

# Laravel Development

## Start with the existing application
- Read `composer.json`, `composer.lock`, `php artisan --version`, PHP version, relevant package versions, and project conventions.
- Inspect related routes, controller/action/service, model, policy, request, view/component, migrations, and tests before editing.
- Follow the installed Laravel major version and existing architecture; do not invent folders or APIs.

## Implementation
- Keep controllers focused on HTTP orchestration; use existing service/action patterns for domain logic.
- Use Form Requests or equivalent validation for complex inputs; validate all untrusted input.
- Enforce authorization using policies, gates, middleware, or established RBAC patterns—not only UI visibility.
- Prefer named routes, route model binding where appropriate, dependency injection, and configuration over hardcoding.
- Use Eloquent relationships and eager loading; avoid N+1 queries and mass-assignment vulnerabilities.
- Use transactions for logically atomic multi-record writes.
- Handle exceptions and user-facing errors without leaking internals.
- Keep secrets in environment/config; never commit `.env`.
- Avoid unnecessary packages and broad refactors.

## UI and API
- Match the application's existing Blade/React/Vue conventions and API response contracts.
- Preserve backward compatibility unless explicitly requested.
- Include validation, loading, empty, success, and failure states where relevant.

## Verify
- Run focused feature/unit tests and relevant formatter/static analysis if configured.
- Inspect route list or generated output when relevant.
- Review the diff and report changed files, behavior, and test results.
