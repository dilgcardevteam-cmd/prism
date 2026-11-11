# Contributing to AI-SKILLS

## Add or update a skill
1. Check `SKILLS-INDEX.md` for overlap.
2. Use `skills/<lowercase-hyphen-name>/SKILL.md`.
3. Add YAML frontmatter:
   ```yaml
   ---
   name: example-skill
   description: Explain the task and when this skill should be used.
   ---
   ```
4. Write practical steps, safety boundaries, and verification criteria.
5. Avoid copying all of `AGENTS.md`; link to baseline expectations instead.
6. Update the index and any adapter only if needed.
7. Test with a realistic task and review the result.
8. Run synchronization only after checking the destinations.
9. Commit with a clear message, e.g. `docs(skills): improve excel import validation`.

## Quality standard
Skills should be concise enough to follow, detailed enough to act on, version-agnostic where possible, and explicit about assumptions. Never include credentials, production records, or confidential information.

## Validation and release
- Check that practical steps, workflow, safety requirements, and validation procedures are present.
- Run `.\scripts\sync-skills.ps1 -DryRun` and review any reported conflicts before syncing.
- Use Git diffs to review the canonical changes; do not commit generated destination copies unless the project intentionally versions them.
- Follow `SKILLS-MANAGEMENT.md` for the fuller lifecycle and cross-project workflow.
