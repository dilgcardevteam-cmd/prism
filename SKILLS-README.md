# AI-SKILLS — Portable AI Development Library

A single source of reusable instructions and Agent Skills for Laravel and general software work. Designed to be shared across GitHub Copilot, Claude Code, OpenAI Codex, Cursor, Google Antigravity, and other compatible agents.

## Design
- `skills/` is the canonical source of task-specific Agent Skills.
- `AGENTS.md` is the shared baseline for agents that recognize it.
- `SKILLS-INDEX.md` maps tasks to skills.
- Assistant-native instruction files are lightweight entry points; keep them short and point to canonical rules rather than duplicating them.
- `scripts/` helps copy skills into native discovery locations. Synchronization copies files; it does not guarantee every product automatically invokes every skill.

## Agent Skills format
Each skill lives in `skills/<skill-name>/SKILL.md` and starts with YAML frontmatter containing `name` and `description`. The description should clearly state what the skill does and when it should be used. Keep names lowercase and hyphenated. Add optional supporting files only when useful.

## Quick start
1. Review `AGENTS.md`, `SKILLS-INDEX.md`, and the full skill relevant to the task.
2. Keep canonical skills in `skills/`; update only the canonical files.
3. Preview and synchronize native skill copies with `.\scripts\sync-skills.ps1 -DryRun` and `.\scripts\sync-skills.ps1`.
4. For another project, pass its path with `-ProjectPath`; keep its project-specific constraints in that project's `AGENTS.md`.
5. Test discovery by asking the assistant to identify and read a relevant skill before implementation.

## Tool integration overview

This project uses the following documented project-level skill locations. Discovery and automatic invocation still depend on the installed product version and workspace settings.

| Assistant | Typical project entry point | Skills discovery / notes |
|---|---|---|
| GitHub Copilot | `.github/copilot-instructions.md` and `AGENTS.md` | Project skill folders can be stored in `.github/skills/` or `.agents/skills/`; support depends on the Copilot surface/version. |
| Claude Code | `CLAUDE.md` | Project skills use `.claude/skills/<name>/SKILL.md`. |
| OpenAI Codex | `AGENTS.md` | Project skills use `.agents/skills/<name>/SKILL.md`. |
| Cursor | `.cursor/rules/*.mdc` and `AGENTS.md` | Project skills use `.cursor/skills/` or `.agents/skills/`. |
| Google Antigravity | `GEMINI.md`, `AGENTS.md`, `.agents/rules/` | Project skills use `.agents/skills/<name>/SKILL.md`; rules can be stored in `.agents/rules/*.md`. |

**Important:** A portable `SKILL.md` is reusable, but no single path or setting guarantees identical automatic behavior across all assistants. Native adapters and explicit references may be needed.

## Synchronization
- Keep `skills/` canonical; do not hand-edit generated copies.
- Preview: `.\scripts\sync-skills.ps1 -DryRun`
- Synchronize this project: `.\scripts\sync-skills.ps1`
- Synchronize another project: `.\scripts\sync-skills.ps1 -ProjectPath 'D:\work\another-laravel-project'`
- Save a CSV report: `.\scripts\sync-skills.ps1 -ReportPath '.\storage\app\skills-sync-report.csv'`
- The script validates the index and skill frontmatter, never deletes destination-only files, and stops before writing if it finds conflicts.
- To replace conflicting destination files after reviewing them, explicitly run with `-OverwriteConflicts`. This overwrites only colliding files with canonical versions.
- The sync uses copies rather than symbolic links for portability. Links can avoid duplicate files, but Windows link creation may require Developer Mode or elevated privileges, can behave differently across tools, and may not survive archive/checkout workflows. Copies are predictable but must be synchronized after canonical edits.

## Improve the library
Use [SKILLS-MANAGEMENT.md](SKILLS-MANAGEMENT.md) and [SKILLS-CONTRIBUTING.md](SKILLS-CONTRIBUTING.md). Promote repeated successful instructions or corrections into a skill only when they are reusable, verified, and not project-specific.

## License and ownership
Choose a license before publishing publicly. Keep organization-specific or confidential procedures in a private repository.
