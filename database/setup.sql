-- ============================================================
-- Student Notes — Database Setup Script
-- Run this on your MySQL server to create the database, user,
-- and table required by the application.
-- ============================================================

-- 1. Create the database
CREATE DATABASE IF NOT EXISTS student_notes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. Create a dedicated MySQL user (change password if desired)
CREATE USER IF NOT EXISTS 'notes_user'@'localhost'
    IDENTIFIED BY 'SecurePass123!';

-- 3. Grant privileges
GRANT ALL PRIVILEGES ON student_notes.* TO 'notes_user'@'localhost';
FLUSH PRIVILEGES;

-- 4. Switch to the database
USE student_notes;

-- 5. Create the notes table
CREATE TABLE IF NOT EXISTS notes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(255)  NOT NULL,
    subject    VARCHAR(100)  DEFAULT '',
    content    TEXT          NOT NULL,
    priority   ENUM('low', 'normal', 'high') DEFAULT 'normal',
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_priority (priority),
    FULLTEXT INDEX idx_search (title, subject, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Insert sample data so the app isn't empty on first load
INSERT INTO notes (title, subject, content, priority) VALUES
('Welcome to Student Notes', 'General',
 'This is your Student Notes application. You can create, read, update, and delete notes.\n\nFeatures:\n- Full CRUD operations\n- Search across all notes\n- Filter by priority\n- Responsive dark-mode UI',
 'normal'),

('Database Normalization', 'Database Systems',
 'First Normal Form (1NF): Eliminate repeating groups.\nSecond Normal Form (2NF): Remove partial dependencies.\nThird Normal Form (3NF): Remove transitive dependencies.\n\nRemember: \"The key, the whole key, and nothing but the key.\"',
 'high'),

('Apache Virtual Hosts', 'System Administration',
 'Virtual hosts allow one Apache server to host multiple sites.\n\nKey files:\n- /etc/apache2/sites-available/ — config files\n- /etc/apache2/ports.conf — listening ports\n\nCommands:\n  sudo a2ensite mysite.conf\n  sudo systemctl restart apache2',
 'normal'),

('PHP PDO vs MySQLi', 'Web Development',
 'MySQLi: MySQL-specific, procedural or OOP.\nPDO: Database-agnostic, OOP only.\n\nThis project uses MySQLi with prepared statements for security against SQL injection.',
 'low');
