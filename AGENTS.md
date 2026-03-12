# AGENTS

Instructions for AI coding agents and human contributors working in this workspace.

---

## Project Overview

| Field | Value |
|-------|-------|
| **Repo** | OpenSpec (Fission-AI) |
| **Stack** | TypeScript (core), PHP (demo pages) |
| **Custom page** | `impact_hub.php` — VoxelCraft Block Builder Game |
| **Platform** | Windows (PowerShell), PHP 8.3 via winget |

OpenSpec is a specification toolkit. The `impact_hub.php` file is a standalone demo page added locally to showcase a creative, real-world single-file PHP application (Minecraft-style isometric voxel building game with a visible player character, walking animation, terrain generation, block placement/breaking, inventory, and day/night cycle).

---

## Agent Behavior Rules

1. **One task at a time.** Finish work on the current file before touching another.
2. **Minimal blast radius.** Only edit files directly related to the request.
3. **Root-cause over band-aid.** Investigate before patching.
4. **Preserve style.** Match the indentation, naming, and patterns already in the file.
5. **No phantom dependencies.** Do not add libraries, packages, or imports unless the user asks.
6. **Validate before reporting done.** Run a syntax check or linter where available.
7. **Explain what changed.** After every edit, give a short plain-English summary.

---

## Quick Start — Running the PHP Demo

### Prerequisites

- PHP 8.x installed (`winget install PHP.PHP.8.3` on Windows)
- A terminal open in the repo root (`OpenSpec/`)

### Launch

```powershell
php -S localhost:8000
```

Then open **http://localhost:8000/impact_hub.php** in a browser.

### Troubleshooting

| Problem | Fix |
|---------|-----|
| `php` not recognized | Reopen terminal after install, or use the full path: `C:\Users\KC\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe` |
| Port 8000 busy | Use another port: `php -S localhost:9000` |
| Page shows raw PHP code | Make sure you access through `http://localhost:…`, not by opening the `.php` file directly |

---

## File Map

| File | Type | Notes |
|------|------|-------|
| `impact_hub.php` | PHP + HTML + CSS + JS | VoxelCraft game. Self-contained single page. Do not split unless asked. |
| `AGENTS.md` | Markdown | This file — agent/contributor guidance. |
| `src/`, `schemas/`, `docs/` | TypeScript / JSON / MD | Core OpenSpec project files. Treat as read-only unless the task explicitly targets them. |

---

## Editing Checklist

Before marking any task complete:

- [ ] File paths verified (absolute paths on Windows use `\`, URLs use `/`).
- [ ] Syntax validated (`php -l file.php`, `npx tsc --noEmit`, etc.).
- [ ] Run/test command provided so the user can verify.
- [ ] Summary of changes written in plain language.

---

## Boundaries

**In scope** — anything the user explicitly requests.

**Out of scope by default** (ask first):

- Large-scale refactors across multiple directories.
- Dependency version bumps or migrations.
- Rewriting documentation the user did not mention.
- Modifying CI/CD workflows or GitHub Actions.
