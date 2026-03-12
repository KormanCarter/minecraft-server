# AGENTS

Instructions for AI coding agents and human contributors working in this workspace.

---

## Project Overview

| Field | Value |
|-------|-------|
| **Repo** | [minecraft-server](https://github.com/KormanCarter/minecraft-server) |
| **Stack** | PHP 8.x · MySQL 8.x · Apache2 · HTML/CSS/JS |
| **Main App** | `app/index.php` — Student Notes (PHP + MySQL CRUD) |
| **Bonus** | `impact_hub.php` — VoxelCraft Block Builder Game |
| **Hosting** | Apache2 virtual host on port **8080** (Debian/Ubuntu) |
| **Platform** | Windows (dev), Linux/Debian (production) |

### Student Notes Application
`app/index.php` is a single-page PHP + MySQL application with full CRUD (Create, Read, Update, Delete). Features: note creation form, search, priority filters, stats dashboard, dark-mode responsive UI. Data stored in a `student_notes` MySQL database.

### VoxelCraft Game
`impact_hub.php` is a standalone Minecraft-style isometric voxel building game with player character, mobs, combat system, inventory hotbar with diamond sword, and day/night cycle.

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
| `app/index.php` | PHP + HTML + CSS | Student Notes CRUD app. Main deliverable. |
| `database/setup.sql` | SQL | MySQL schema, user, sample data. Run before first use. |
| `config/studentnotes.conf` | Apache conf | Virtual host for port 8080. Copy to `/etc/apache2/sites-available/`. |
| `docs/DEPLOYMENT.md` | Markdown | Full deployment guide (Apache, MySQL, DNS, firewall). |
| `docs/AI_PROMPTS.md` | Markdown | All AI prompts used during development. |
| `impact_hub.php` | PHP + HTML + CSS + JS | VoxelCraft game. Self-contained single page. |
| `AGENTS.md` | Markdown | This file — agent/contributor guidance. |
| `src/`, `schemas/`, `docs/` (OpenSpec) | TS / JSON / MD | Original OpenSpec project files. Treat as read-only. |

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
