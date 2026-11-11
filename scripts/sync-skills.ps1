[CmdletBinding()]
param(
    [string]$ProjectPath = (Split-Path -Parent $PSScriptRoot),
    [switch]$DryRun,
    [switch]$OverwriteConflicts,
    [string]$ReportPath
)

$ErrorActionPreference = 'Stop'

$sourceRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\skills')).Path
$projectRoot = (Resolve-Path -LiteralPath $ProjectPath).Path
$indexPath = Join-Path (Split-Path -Parent $sourceRoot) 'SKILLS-INDEX.md'
$requiredDestinations = @(
    '.agents\skills',
    '.github\skills',
    '.claude\skills',
    '.cursor\skills'
)

if (-not (Test-Path -LiteralPath $indexPath -PathType Leaf)) {
    throw "Skills index not found: $indexPath"
}

$sourceSkills = @(Get-ChildItem -LiteralPath $sourceRoot -Directory | Sort-Object Name)
if ($sourceSkills.Count -eq 0) {
    throw "No canonical skills found under $sourceRoot"
}

$skillNames = @()
foreach ($skill in $sourceSkills) {
    $skillFile = Join-Path $skill.FullName 'SKILL.md'
    if (-not (Test-Path -LiteralPath $skillFile -PathType Leaf)) {
        throw "Missing SKILL.md: $skillFile"
    }

    $content = Get-Content -LiteralPath $skillFile -Raw
    $frontMatter = [regex]::Match($content, '(?s)\A---\r?\n(?<metadata>.*?)\r?\n---(?:\r?\n|\z)')
    if (-not $frontMatter.Success) {
        throw "Invalid YAML frontmatter delimiters: $skillFile"
    }

    $metadata = $frontMatter.Groups['metadata'].Value
    $nameMatch = [regex]::Match($metadata, '(?m)^name:\s*(?<name>[a-z0-9][a-z0-9-]*)\s*$')
    $descriptionMatch = [regex]::Match($metadata, '(?m)^description:\s*(?<description>\S.*)$')
    $body = $content.Substring($frontMatter.Length).Trim()
    $hasWorkflow = $body -match '(?im)^\s*(?:\d+[.)]\s+|[-*]\s+)|\bworkflow\b'
    $hasSafety = $body -match '(?i)\b(safety|security|never|do not|must not|avoid|protect|authorization|untrusted)\b'
    $hasValidation = $body -match '(?i)\b(verify|validation|validate|test|benchmark|confirm|check)\b'
    if (-not $nameMatch.Success -or $nameMatch.Groups['name'].Value -ne $skill.Name) {
        throw "Skill name must match its folder ($($skill.Name)): $skillFile"
    }
    if (-not $descriptionMatch.Success) {
        throw "Skill description is missing or invalid: $skillFile"
    }
    if ($body.Length -lt 150 -or [regex]::Matches($body, '(?m)^#\s+\S').Count -lt 1 -or
        -not $hasWorkflow -or -not $hasSafety -or -not $hasValidation) {
        throw "Skill instructions are incomplete: $skillFile"
    }

    $skillNames += $skill.Name
}

$indexContent = Get-Content -LiteralPath $indexPath -Raw
$indexedNames = @(
    [regex]::Matches($indexContent, '(?m)^\|\s*`(?<name>[a-z0-9-]+)`\s*\|') |
        ForEach-Object { $_.Groups['name'].Value }
)
$sortedSkillNames = ($skillNames | Sort-Object) -join "`n"
$sortedIndexedNames = ($indexedNames | Sort-Object) -join "`n"
if ($sortedSkillNames -ne $sortedIndexedNames) {
    throw 'SKILLS-INDEX.md does not match the canonical skill folders.'
}
if (($indexedNames | Sort-Object -Unique).Count -ne $indexedNames.Count) {
    throw 'SKILLS-INDEX.md contains duplicate skill entries.'
}

$resolvedReportPath = $null
if ($ReportPath) {
    if ([System.IO.Path]::IsPathRooted($ReportPath)) {
        $resolvedReportPath = [System.IO.Path]::GetFullPath($ReportPath)
    } else {
        $resolvedReportPath = [System.IO.Path]::GetFullPath((Join-Path $projectRoot $ReportPath))
    }
    if (Test-Path -LiteralPath $resolvedReportPath) {
        throw "Refusing to overwrite an existing report: $resolvedReportPath"
    }
}

$plan = @()
foreach ($relativeDestination in $requiredDestinations) {
    $destinationRoot = Join-Path $projectRoot $relativeDestination
    if ((Test-Path -LiteralPath $destinationRoot) -and
        -not (Test-Path -LiteralPath $destinationRoot -PathType Container)) {
        throw "Destination exists but is not a directory: $destinationRoot"
    }

    foreach ($skill in $sourceSkills) {
        $skillDestination = Join-Path $destinationRoot $skill.Name
        $sourceFiles = @(Get-ChildItem -LiteralPath $skill.FullName -File -Recurse)
        if ($sourceFiles.Count -eq 0) {
            throw "Skill contains no files: $($skill.FullName)"
        }

        foreach ($sourceFile in $sourceFiles) {
            $relativeFile = $sourceFile.FullName.Substring($skill.FullName.Length).TrimStart('\', '/')
            $targetFile = Join-Path $skillDestination $relativeFile
            $action = 'Add'
            $existingHash = $null

            if (Test-Path -LiteralPath $targetFile) {
                if (-not (Test-Path -LiteralPath $targetFile -PathType Leaf)) {
                    throw "Destination path exists but is not a file: $targetFile"
                }

                $existingHash = (Get-FileHash -LiteralPath $targetFile -Algorithm SHA256).Hash
                if ($existingHash -eq (Get-FileHash -LiteralPath $sourceFile.FullName -Algorithm SHA256).Hash) {
                    $action = 'Unchanged'
                } else {
                    $action = 'Conflict'
                }
            }

            $plan += [pscustomobject]@{
                Skill = $skill.Name
                Destination = $relativeDestination
                RelativePath = (Join-Path $skill.Name $relativeFile)
                SourcePath = $sourceFile.FullName
                TargetPath = $targetFile
                ExistingHash = $existingHash
                Action = $action
                Status = $null
            }
        }
    }
}

$hasBlockingConflicts = (@($plan | Where-Object { $_.Action -eq 'Conflict' }).Count -gt 0) -and
    -not $OverwriteConflicts
foreach ($item in $plan) {
    if ($item.Action -eq 'Unchanged') {
        $item.Status = 'Unchanged'
    } elseif ($hasBlockingConflicts -and $item.Action -eq 'Conflict') {
        $item.Status = 'Conflict'
    } elseif ($hasBlockingConflicts -and $item.Action -eq 'Add') {
        $item.Status = 'BlockedByConflict'
    } elseif ($DryRun -and $item.Action -eq 'Add') {
        $item.Status = 'WouldAdd'
    } elseif ($DryRun -and $item.Action -eq 'Conflict') {
        $item.Status = 'WouldUpdate'
    } elseif ($item.Action -eq 'Conflict') {
        $item.Status = 'PendingUpdate'
    } else {
        $item.Status = 'PendingAdd'
    }
}

if (-not $DryRun -and -not $hasBlockingConflicts) {
    foreach ($item in $plan) {
        if ($item.Action -eq 'Unchanged') {
            continue
        }

        if ($item.Action -eq 'Conflict') {
            if (-not (Test-Path -LiteralPath $item.TargetPath -PathType Leaf) -or
                (Get-FileHash -LiteralPath $item.TargetPath -Algorithm SHA256).Hash -ne $item.ExistingHash) {
                throw "Destination changed during synchronization; review before retrying: $($item.TargetPath)"
            }
        } elseif (Test-Path -LiteralPath $item.TargetPath) {
            throw "A destination appeared during synchronization; review before retrying: $($item.TargetPath)"
        }

        $parentDirectory = Split-Path -Parent $item.TargetPath
        New-Item -ItemType Directory -Path $parentDirectory -Force | Out-Null
        if ($item.Action -eq 'Conflict') {
            Copy-Item -LiteralPath $item.SourcePath -Destination $item.TargetPath -Force
            $item.Status = 'Updated'
        } else {
            Copy-Item -LiteralPath $item.SourcePath -Destination $item.TargetPath
            $item.Status = 'Added'
        }
    }
}

if ($resolvedReportPath) {
    $reportDirectory = Split-Path -Parent $resolvedReportPath
    if (-not (Test-Path -LiteralPath $reportDirectory -PathType Container)) {
        New-Item -ItemType Directory -Path $reportDirectory -Force | Out-Null
    }

    $timestamp = (Get-Date).ToString('o')
    $plan | Select-Object @{Name = 'Timestamp'; Expression = { $timestamp }},
        Status, Skill, Destination, RelativePath |
        Export-Csv -LiteralPath $resolvedReportPath -NoTypeInformation -Encoding UTF8
}

Write-Host "Skills: $($sourceSkills.Count); destination files: $($plan.Count); mode: $(if ($DryRun) { 'dry run' } else { 'sync' })"
Write-Host "Added/would add: $(@($plan | Where-Object { $_.Status -in @('Added', 'WouldAdd') }).Count); updated/would update: $(@($plan | Where-Object { $_.Status -in @('Updated', 'WouldUpdate') }).Count); unchanged: $(@($plan | Where-Object { $_.Status -eq 'Unchanged' }).Count); conflicts: $(@($plan | Where-Object { $_.Status -eq 'Conflict' }).Count)"
if ($resolvedReportPath) {
    Write-Host "Report: $resolvedReportPath"
}
$plan | Select-Object Status, Skill, Destination, RelativePath | Format-Table -AutoSize

if ($hasBlockingConflicts) {
    throw 'Synchronization stopped before writing files because conflicts exist. Review the report and rerun with -OverwriteConflicts only when the canonical versions should replace those specific files.'
}
if ($DryRun) {
    Write-Host 'Dry run complete; no skill files were changed.'
}
