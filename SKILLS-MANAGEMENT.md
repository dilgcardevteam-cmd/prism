# Skills Management Workflow

`skills/` is the canonical source. Edit those files; generated assistant copies are disposable and must be refreshed with `scripts/sync-skills.ps1`.

## Create or improve a skill

1. Inspect `SKILLS-INDEX.md` and the existing skills for overlap or conflicting guidance. Extend the closest skill instead of creating a duplicate.
2. Keep the scope reusable across projects. Put repository-specific paths, architecture, credentials, incident details, and one-off constraints in that project's own `AGENTS.md`, not in a portable skill.
3. Use `skills/<lowercase-hyphen-name>/SKILL.md` with YAML frontmatter containing a matching `name` and a useful `description`.
4. Include practical instructions, an ordered workflow, safety boundaries, and validation procedures. Use concrete checks and state when a user decision or authorization is required.
5. Convert repeated user corrections into skill guidance only after confirming the correction is generally reusable and factually verified. Ask before recording uncertain or sensitive policy.
6. Never persist temporary instructions, confidential information, secrets, production records, or unverified assumptions in a skill.
7. Update `SKILLS-INDEX.md`, then review the skill against at least one realistic example and check for contradictions with `AGENTS.md` and other skills.

## Detect duplicates and conflicts

- Search existing skill names, descriptions, workflows, and triggers before adding a new skill.
- Combine overlapping instructions when they serve the same task; keep separate skills when their triggers or risks differ materially.
- When two instructions disagree, follow the higher-level safety policy and project-specific verified requirements. Resolve the written conflict instead of preserving both as competing rules.
- Ensure index entries match the actual canonical skill folders. The synchronization script checks folder names, frontmatter names, and index entries before copying.

## Validate and synchronize

From the library/project root in PowerShell:

```powershell
.\scripts\sync-skills.ps1 -DryRun
.\scripts\sync-skills.ps1 -ReportPath '.\storage\app\skills-sync-report.csv'
```

The dry run does not change skill files. The synchronization report lists each skill file and destination. Existing destination-only files are retained. If the script detects different content at a destination, it stops before writing any skill files; inspect the differences, preserve any destination work you need, and rerun with `-OverwriteConflicts` only when replacing those conflicting files with canonical content is intended.

After synchronization, inspect `git status --short` and the diff. Test discovery in each installed assistant by asking it to list a relevant skill, open its complete `SKILL.md`, and explain which instructions apply. File presence and a successful copy do not prove an assistant runtime has discovered or invoked the skill.

## Use one library across multiple Laravel projects

Keep a clone of this skills library in a stable local directory or a private Git repository. For each Laravel project, preview and sync from that library:

```powershell
& 'D:\work\AI-SKILLS\scripts\sync-skills.ps1' `
  -ProjectPath 'D:\work\laravel-project-a' -DryRun
& 'D:\work\AI-SKILLS\scripts\sync-skills.ps1' `
  -ProjectPath 'D:\work\laravel-project-a'
```

Repeat with each project path. Pull reviewed library changes on other machines, then rerun the sync. Keep project-specific overrides in each project's `AGENTS.md`; do not fork canonical skills unless the different behavior is intentional and documented.

Copies are used instead of symbolic links to work consistently across Windows checkouts, tools, and CI environments. Links reduce duplicate storage but require correct permissions and path handling, and can break when the library is moved or omitted from a checkout.

## Version control

Review and commit canonical skills, index, shared instructions, and the sync script together when they form one change. Use descriptive commits and keep generated copies out of unrelated project diffs unless that repository intentionally checks them in. Do not commit credentials, private project files, or temporary validation output.
