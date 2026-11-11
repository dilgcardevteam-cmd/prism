---
name: authentication-authorization
description: Use when implementing or changing login, OTP, MFA, sessions, roles, permissions, policies, impersonation, or account security.
---

# Authentication and Authorization

- Inspect existing authentication provider, guards, middleware, session configuration, role/permission package, policies, and audit logging before changes.
- Enforce authorization server-side for every protected action and object; hiding a button is not access control.
- Apply least privilege, deny by default, validate ownership/scope, and prevent IDOR.
- For OTP/MFA: use cryptographically secure generation, expiration, one-time consumption, rate limiting, attempt limits, safe storage (hash where appropriate), and generic responses that avoid account enumeration. Never log or email passwords/secrets.
- Protect session lifecycle: secure cookies, HTTPS in production, session regeneration after login/privilege changes, logout invalidation, and CSRF protection.
- Passwords must use framework password hashing; never store plaintext or reversible encryption.
- Impersonation must be explicitly permissioned, visibly indicated, audited (actor, target, reason, start/end), constrained, and safely reversible; prevent privilege escalation and sensitive actions unless policy permits.
- Avoid weakening MFA, validation, throttling, or authorization to make tests pass.
- Add tests for allowed, denied, expired, replayed, throttled, and cross-user access cases as applicable.
- Report security assumptions and any unverified controls.
