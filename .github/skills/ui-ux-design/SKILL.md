---
name: ui-ux-design
description: Use when designing or improving dashboards, forms, tables, navigation, responsive layouts, visual hierarchy, accessibility, or user interaction.
---

# UI/UX Design

## Inspect first
- Review current layouts, design tokens, component library, CSS conventions, breakpoints, and screenshots if available.
- Preserve the established visual identity; do not add a competing UI framework without approval.

## Design principles
- Clear hierarchy, concise labels, consistent spacing, restrained color, and predictable interactions.
- Prefer reusable components and existing tokens over duplicated styles.
- Design responsive behavior for desktop, tablet, and mobile.
- Use semantic HTML, keyboard navigation, visible focus, accessible labels, sufficient contrast, and meaningful alt text.
- Tables should support readable columns, overflow strategy, sorting/filtering conventions, and clear empty/loading/error states.
- Forms should show required fields, inline validation, helpful error text, disabled/loading states, and confirmation for consequential actions.
- Distinguish status using text/icons as well as color.
- Avoid unnecessary animation, visual clutter, and hidden critical actions.

## Implementation
- Follow existing Blade/React/Vue and CSS architecture.
- Keep interaction logic separate from presentation when established.
- Do not introduce inaccessible custom controls where native controls work.
- Check layout at relevant breakpoints and long-content edge cases.

## Verify
- Review visual consistency and responsive behavior.
- Test keyboard flow and form errors.
- Report any checks that could not be performed.
