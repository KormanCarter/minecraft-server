# AI Prompts Documentation

**Author:** Korman Carter  
**AI Tool Used:** GitHub Copilot (Claude)  
**Course:** PHP & MySQL Web Application Project  
**Date:** March 2026

This document records all AI prompts used during the development of the Student Notes application and related project deliverables, as required by the assignment.

---

## 1. PHP Application Code Generation

### Prompt 1.1 — Initial Application Request
> "Students will use AI tools to help generate a simple single-page application (SPA) using PHP and MySQL. The application should run on Apache2, use PHP for server-side logic, use MySQL for data storage, include at least one form, store and retrieve data from a MySQL database, be functional enough to demonstrate working CRUD or basic data handling."

**Result:** Generated `app/index.php` — a complete Student Notes application with:
- CREATE: Form to add new notes with title, subject, content, priority
- READ: Display all notes with search and filter
- UPDATE: Edit button loads note into form for modification
- DELETE: Delete button with confirmation prompt
- Full dark-mode UI with responsive CSS
- Statistics dashboard showing note counts by priority

---

## 2. MySQL Database Schema

### Prompt 2.1 — Database Setup
> "Create the MySQL database setup script with the database, user, table, and sample data for the Student Notes application."

**Result:** Generated `database/setup.sql` containing:
- `CREATE DATABASE student_notes`
- `CREATE USER 'notes_user'@'localhost'`
- `GRANT ALL PRIVILEGES`
- `CREATE TABLE notes` with columns: id, title, subject, content, priority (ENUM), created_at, updated_at
- Indexes for priority and full-text search
- 4 sample notes inserted for demonstration

---

## 3. Apache2 Virtual Host Configuration

### Prompt 3.1 — Virtual Host File
> "Use AI to generate an Apache2 virtual host configuration file. The configuration should be placed in sites-available, point to the correct document root, reference the correct server name, and use port 8080 (not 80 or 443)."

**Result:** Generated `config/studentnotes.conf` with:
- `<VirtualHost *:8080>` listening on non-standard port
- `ServerName studentnotes.kormancarter.com`
- `DocumentRoot /var/www/studentnotes/app`
- Directory permissions with `AllowOverride All`
- Error and access log configuration

### Prompt 3.2 — Port Configuration
> "Configure Apache to listen on port 8080. Update ports.conf to add the Listen directive."

**Result:** Documented in DEPLOYMENT.md — add `Listen 8080` to `/etc/apache2/ports.conf`

---

## 4. Deployment Instructions

### Prompt 4.1 — Full Deployment Guide
> "Create deployment instructions covering prerequisites installation, MySQL setup, file deployment, Apache virtual host configuration, DNS record creation, firewall rules, and testing."

**Result:** Generated `docs/DEPLOYMENT.md` with step-by-step instructions for:
- Installing Apache2, PHP, MySQL on Debian/Ubuntu
- Running the SQL setup script
- Cloning the repo to `/var/www/studentnotes`
- Setting file ownership and permissions
- Copying and enabling the virtual host
- Adding `Listen 8080` to `ports.conf`
- Creating a DNS A record
- Opening firewall port 8080
- Testing the application in a browser
- Troubleshooting table for common issues

---

## 5. DNS Configuration

### Prompt 5.1 — DNS Record
> "Create a DNS record that points to the application server. Include an A record pointing a hostname to the server IP address."

**Result:** Documented in DEPLOYMENT.md:
- A record: `studentnotes.kormancarter.com` → server public IP
- Verification commands: `dig` and `nslookup`
- Note about DNS propagation time

---

## 6. Troubleshooting

### Prompt 6.1 — Error Resolution
> "Help troubleshoot: database connection failed, page not loading on custom port, 403 forbidden errors, Apache startup failures."

**Result:** Troubleshooting table in DEPLOYMENT.md with solutions for:
- Database connection failures → check credentials and run setup.sql
- Port not responding → verify Listen directive and restart Apache
- Permission errors → chown to www-data
- Apache syntax errors → run `apachectl configtest`
- DNS issues → wait for propagation, verify A record
- Firewall blocking → ufw allow and cloud security groups

---

## 7. Project Structure & Documentation

### Prompt 7.1 — Repository Organization
> "Organize the project with proper file structure including app code, config files, database scripts, and documentation."

**Result:** Created organized directory structure:
```
app/         — PHP application
config/      — Apache virtual host file
database/    — MySQL setup script
docs/        — Deployment guide and AI prompts
```

### Prompt 7.2 — AGENTS.md
> "Update AGENTS.md with project guidance covering the Student Notes app, file map, and agent behavior rules."

**Result:** Updated AGENTS.md with project overview, file inventory, rules, and troubleshooting.

---

## Summary of AI-Generated Deliverables

| Deliverable | File | AI-Generated? |
|-------------|------|:---:|
| PHP Application (CRUD) | `app/index.php` | ✅ |
| MySQL Schema + Setup | `database/setup.sql` | ✅ |
| Apache Virtual Host | `config/studentnotes.conf` | ✅ |
| Deployment Instructions | `docs/DEPLOYMENT.md` | ✅ |
| AI Prompts Documentation | `docs/AI_PROMPTS.md` | ✅ |
| Project Guidance | `AGENTS.md` | ✅ |
