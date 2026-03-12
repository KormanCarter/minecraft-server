<?php
/**
 * Student Notes Application
 * Single-Page Application (SPA) using PHP + MySQL
 * Supports full CRUD: Create, Read, Update, Delete
 * 
 * Author: Korman Carter
 * Course: PHP & MySQL Web Application Project
 */

// ── Database Configuration ──────────────────────
$db_host = 'localhost';
$db_user = 'notes_user';
$db_pass = 'SecurePass123!';
$db_name = 'student_notes';

// Connect to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('<div style="color:red;padding:2rem;font-family:sans-serif;">
         <h2>Database Connection Failed</h2>
         <p>' . htmlspecialchars($conn->connect_error) . '</p>
         <p>Make sure MySQL is running and the database has been set up.<br>
         See <code>database/setup.sql</code> for instructions.</p></div>');
}
$conn->set_charset('utf8mb4');

// ── Load User Profile ───────────────────────────
$profileResult = $conn->query("SELECT display_name FROM user_profile WHERE id=1");
$userName = 'Student';
if ($profileResult && $row = $profileResult->fetch_assoc()) {
    $userName = $row['display_name'];
}

// ── Handle Form Submissions (CRUD) ─────────────
$message = '';
$messageType = '';
$editNote = null;

// UPDATE USER NAME
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_name') {
    $newName = trim($_POST['display_name'] ?? '');
    if ($newName) {
        $stmt = $conn->prepare("UPDATE user_profile SET display_name=? WHERE id=1");
        $stmt->bind_param('s', $newName);
        if ($stmt->execute()) {
            $userName = $newName;
            $message = 'Name updated to "' . htmlspecialchars($newName) . '"!';
            $messageType = 'success';
        } else {
            $message = 'Error updating name.';
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = 'Name cannot be empty.';
        $messageType = 'error';
    }
}

// CREATE — Insert new note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $title   = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if ($title && $content) {
            $stmt = $conn->prepare("INSERT INTO notes (title, subject, content, priority) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $title, $subject, $content, $priority);
            if ($stmt->execute()) {
                $message = 'Note created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error creating note: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Title and content are required.';
            $messageType = 'error';
        }
    }

    // UPDATE — Edit existing note
    if ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id      = (int) $_POST['id'];
        $title   = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';

        if ($title && $content) {
            $stmt = $conn->prepare("UPDATE notes SET title=?, subject=?, content=?, priority=? WHERE id=?");
            $stmt->bind_param('ssssi', $title, $subject, $content, $priority, $id);
            if ($stmt->execute()) {
                $message = 'Note updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error updating note: ' . $stmt->error;
                $messageType = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Title and content are required.';
            $messageType = 'error';
        }
    }

    // DELETE — Remove note
    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM notes WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $message = 'Note deleted.';
            $messageType = 'success';
        } else {
            $message = 'Error deleting note: ' . $stmt->error;
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Check if we're editing
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM notes WHERE id=?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editNote = $result->fetch_assoc();
    $stmt->close();
}

// READ — Fetch all notes (with optional search)
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT * FROM notes";
$conditions = [];
$params = [];
$types = '';

if ($search) {
    $conditions[] = "(title LIKE ? OR subject LIKE ? OR content LIKE ?)";
    $searchWild = "%$search%";
    $params[] = &$searchWild;
    $params[] = &$searchWild;
    $params[] = &$searchWild;
    $types .= 'sss';
}

if ($filter !== 'all') {
    $conditions[] = "priority = ?";
    $params[] = &$filter;
    $types .= 's';
}

if ($conditions) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY updated_at DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$totalNotes = $conn->query("SELECT COUNT(*) as c FROM notes")->fetch_assoc()['c'];
$highCount  = $conn->query("SELECT COUNT(*) as c FROM notes WHERE priority='high'")->fetch_assoc()['c'];
$normalCount = $conn->query("SELECT COUNT(*) as c FROM notes WHERE priority='normal'")->fetch_assoc()['c'];
$lowCount   = $conn->query("SELECT COUNT(*) as c FROM notes WHERE priority='low'")->fetch_assoc()['c'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Notes — PHP + MySQL Application</title>
<style>
/* ── Reset & Base ──────────────────────────── */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
    --bg:        #0f172a;
    --surface:   #1e293b;
    --surface2:  #334155;
    --border:    #475569;
    --text:      #e2e8f0;
    --text-dim:  #94a3b8;
    --accent:    #6366f1;
    --accent2:   #818cf8;
    --green:     #22c55e;
    --red:       #ef4444;
    --yellow:    #eab308;
    --orange:    #f97316;
    --cyan:      #06b6d4;
    --radius:    12px;
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    line-height: 1.6;
}

/* ── Header ───────────────────────────────── */
.header {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e293b 100%);
    border-bottom: 1px solid var(--border);
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    background: linear-gradient(135deg, #818cf8, #c084fc);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.header .subtitle {
    color: var(--text-dim);
    font-size: 0.85rem;
}

/* ── Stats Row ────────────────────────────── */
.stats {
    display: flex;
    gap: 1rem;
    padding: 1rem 2rem;
    flex-wrap: wrap;
}

.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1rem 1.5rem;
    flex: 1;
    min-width: 140px;
    text-align: center;
    transition: transform 0.2s, border-color 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: var(--accent);
}

.stat-card .number {
    font-size: 2rem;
    font-weight: 800;
}

.stat-card .label {
    font-size: 0.8rem;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-total .number  { color: var(--accent2); }
.stat-high .number   { color: var(--red); }
.stat-normal .number { color: var(--green); }
.stat-low .number    { color: var(--text-dim); }

/* ── Layout ───────────────────────────────── */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 2rem;
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 2rem;
}

@media(max-width: 900px) {
    .container { grid-template-columns: 1fr; }
}

/* ── Form Panel ───────────────────────────── */
.form-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.5rem;
    position: sticky;
    top: 1rem;
    height: fit-content;
}

.form-panel h2 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    color: var(--text-dim);
    margin-bottom: 0.3rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.7rem 0.9rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.95rem;
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group select {
    cursor: pointer;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.7rem 1.4rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent), #7c3aed);
    color: #fff;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-danger {
    background: var(--red);
    color: #fff;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-secondary {
    background: var(--surface2);
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    border-color: var(--accent);
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
}

.form-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

/* ── Search & Filter ──────────────────────── */
.toolbar {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.toolbar form {
    display: contents;
}

.search-input {
    flex: 1;
    min-width: 200px;
    padding: 0.6rem 1rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.9rem;
    font-family: inherit;
}

.search-input:focus {
    outline: none;
    border-color: var(--accent);
}

.filter-btn {
    padding: 0.5rem 1rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-dim);
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.filter-btn:hover { border-color: var(--accent); color: var(--text); }
.filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }

/* ── Notes List ───────────────────────────── */
.notes-panel h2 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.note-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.2rem 1.5rem;
    margin-bottom: 0.75rem;
    transition: transform 0.2s, border-color 0.2s;
    position: relative;
}

.note-card:hover {
    transform: translateX(4px);
    border-color: var(--accent);
}

.note-card .note-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.note-card .note-title {
    font-size: 1.1rem;
    font-weight: 700;
}

.note-card .note-subject {
    display: inline-block;
    background: var(--surface2);
    padding: 0.15rem 0.6rem;
    border-radius: 20px;
    font-size: 0.75rem;
    color: var(--cyan);
    margin-top: 0.2rem;
}

.note-card .note-content {
    color: var(--text-dim);
    font-size: 0.9rem;
    margin: 0.5rem 0;
    white-space: pre-line;
}

.note-card .note-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.8rem;
    padding-top: 0.6rem;
    border-top: 1px solid var(--border);
}

.note-card .note-date {
    font-size: 0.75rem;
    color: var(--text-dim);
}

.note-card .note-actions {
    display: flex;
    gap: 0.4rem;
}

.priority-badge {
    display: inline-block;
    padding: 0.15rem 0.6rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.priority-high   { background: rgba(239,68,68,0.15); color: var(--red); }
.priority-normal { background: rgba(34,197,94,0.15); color: var(--green); }
.priority-low    { background: rgba(148,163,184,0.15); color: var(--text-dim); }

/* ── Messages ─────────────────────────────── */
.message {
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    animation: slideIn 0.3s ease;
}

.message-success { background: rgba(34,197,94,0.15); color: var(--green); border: 1px solid rgba(34,197,94,0.3); }
.message-error   { background: rgba(239,68,68,0.15); color: var(--red); border: 1px solid rgba(239,68,68,0.3); }

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Empty State ──────────────────────────── */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-dim);
}

.empty-state .icon { font-size: 3rem; margin-bottom: 1rem; }
.empty-state p { font-size: 0.95rem; }

/* ── Footer ───────────────────────────────── */
.footer {
    text-align: center;
    padding: 2rem;
    color: var(--text-dim);
    font-size: 0.8rem;
    border-top: 1px solid var(--border);
    margin-top: 2rem;
}
</style>
</head>
<body>

<!-- ── Header ───────────────────────────── -->
<div class="header">
    <div>
        <h1>📝 Student Notes</h1>
        <div class="subtitle">PHP + MySQL CRUD Application &middot; Korman Carter</div>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <div style="text-align:right;">
            <div style="color:var(--text); font-weight:600; font-size:0.95rem;" id="userGreeting">
                👋 Hello, <?= htmlspecialchars($userName) ?>!
            </div>
            <div style="color:var(--text-dim); font-size:0.75rem;">
                <?= date('l, F j, Y — g:i A') ?>
            </div>
        </div>
        <button onclick="document.getElementById('nameModal').style.display='flex'"
                style="background:var(--surface2); border:1px solid var(--border); color:var(--text); border-radius:8px; padding:0.4rem 0.8rem; cursor:pointer; font-size:0.8rem; transition:border-color 0.2s;"
                onmouseover="this.style.borderColor='var(--accent)'"
                onmouseout="this.style.borderColor='var(--border)'"
                title="Change your name">✏️ Edit Name</button>
    </div>
</div>

<!-- ── Name Change Modal ─────────────────── -->
<div id="nameModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:2rem; width:90%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="margin-bottom:1rem; font-size:1.1rem;">✏️ Change Your Name</h3>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="update_name">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.8rem; color:var(--text-dim); margin-bottom:0.3rem; text-transform:uppercase; letter-spacing:0.04em; font-weight:600;">Display Name</label>
                <input type="text" name="display_name" required maxlength="100"
                       value="<?= htmlspecialchars($userName) ?>"
                       style="width:100%; padding:0.7rem 0.9rem; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:0.95rem; font-family:inherit;"
                       placeholder="Enter your name">
            </div>
            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('nameModal').style.display='none'"
                        class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">💾 Save Name</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Stats ─────────────────────────────── -->
<div class="stats">
    <div class="stat-card stat-total">
        <div class="number"><?= $totalNotes ?></div>
        <div class="label">Total Notes</div>
    </div>
    <div class="stat-card stat-high">
        <div class="number"><?= $highCount ?></div>
        <div class="label">High Priority</div>
    </div>
    <div class="stat-card stat-normal">
        <div class="number"><?= $normalCount ?></div>
        <div class="label">Normal</div>
    </div>
    <div class="stat-card stat-low">
        <div class="number"><?= $lowCount ?></div>
        <div class="label">Low Priority</div>
    </div>
</div>

<!-- ── Main Layout ───────────────────────── -->
<div class="container">

    <!-- ── Left: Form Panel ──────────────── -->
    <div class="form-panel">
        <h2><?= $editNote ? '✏️ Edit Note' : '➕ New Note' ?></h2>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="<?= $editNote ? 'update' : 'create' ?>">
            <?php if ($editNote): ?>
                <input type="hidden" name="id" value="<?= $editNote['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required
                       placeholder="e.g. Chapter 5 Review"
                       value="<?= htmlspecialchars($editNote['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject"
                       placeholder="e.g. Database Systems"
                       value="<?= htmlspecialchars($editNote['subject'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="low"    <?= ($editNote['priority'] ?? '') === 'low'    ? 'selected' : '' ?>>🟢 Low</option>
                    <option value="normal"  <?= ($editNote['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>🟡 Normal</option>
                    <option value="high"   <?= ($editNote['priority'] ?? '') === 'high'   ? 'selected' : '' ?>>🔴 High</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" required
                          placeholder="Write your notes here..."><?= htmlspecialchars($editNote['content'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editNote ? '💾 Update Note' : '➕ Save Note' ?>
                </button>
                <?php if ($editNote): ?>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Right: Notes List ─────────────── -->
    <div class="notes-panel">
        <h2>📋 Your Notes (<?= count($notes) ?>)</h2>

        <!-- Search & Filter Toolbar -->
        <div class="toolbar">
            <form method="GET" action="index.php">
                <input type="text" class="search-input" name="search"
                       placeholder="🔍 Search notes..."
                       value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            <a href="index.php?filter=all&search=<?= urlencode($search) ?>"
               class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="index.php?filter=high&search=<?= urlencode($search) ?>"
               class="filter-btn <?= $filter === 'high' ? 'active' : '' ?>">🔴 High</a>
            <a href="index.php?filter=normal&search=<?= urlencode($search) ?>"
               class="filter-btn <?= $filter === 'normal' ? 'active' : '' ?>">🟡 Normal</a>
            <a href="index.php?filter=low&search=<?= urlencode($search) ?>"
               class="filter-btn <?= $filter === 'low' ? 'active' : '' ?>">🟢 Low</a>
        </div>

        <!-- Notes Cards -->
        <?php if (empty($notes)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No notes found. Create your first note!</p>
            </div>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
                <div class="note-card">
                    <div class="note-header">
                        <div>
                            <div class="note-title"><?= htmlspecialchars($note['title']) ?></div>
                            <?php if ($note['subject']): ?>
                                <span class="note-subject"><?= htmlspecialchars($note['subject']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="priority-badge priority-<?= $note['priority'] ?>">
                            <?= $note['priority'] ?>
                        </span>
                    </div>
                    <div class="note-content"><?= nl2br(htmlspecialchars($note['content'])) ?></div>
                    <div class="note-footer">
                        <span class="note-date">
                            Created: <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                            <?php if ($note['updated_at'] !== $note['created_at']): ?>
                                &middot; Updated: <?= date('M j, Y g:i A', strtotime($note['updated_at'])) ?>
                            <?php endif; ?>
                        </span>
                        <div class="note-actions">
                            <a href="index.php?edit=<?= $note['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                            <form method="POST" action="index.php" style="display:inline;"
                                  onsubmit="return confirm('Delete this note?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $note['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ── Footer ────────────────────────────── -->
<div class="footer">
    Student Notes App &middot; Built with PHP <?= PHP_VERSION ?> + MySQL &middot; <?= htmlspecialchars($userName) ?> &middot; <?= date('Y') ?>
</div>

</body>
</html>
