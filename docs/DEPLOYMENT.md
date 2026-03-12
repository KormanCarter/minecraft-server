# Deployment Guide — Student Notes Application

**Author:** Korman Carter  
**Stack:** PHP 8.x · MySQL 8.x · Apache2 · Debian/Ubuntu  
**Port:** 8080 (non-standard, as required)

---

## 1. Prerequisites

Make sure these packages are installed on your Debian/Ubuntu server:

```bash
sudo apt update
sudo apt install -y apache2 mysql-server php php-mysql libapache2-mod-php
```

Verify installations:

```bash
apache2 -v
php -v
mysql --version
```

---

## 2. Set Up the MySQL Database

Run the setup script to create the database, user, and table:

```bash
sudo mysql < /var/www/studentnotes/database/setup.sql
```

Or log into MySQL and run it manually:

```bash
sudo mysql -u root -p
```

```sql
SOURCE /var/www/studentnotes/database/setup.sql;
```

**Verify the database was created:**

```bash
sudo mysql -u notes_user -p'SecurePass123!' -e "USE student_notes; SHOW TABLES;"
```

Expected output:

```
+-------------------------+
| Tables_in_student_notes |
+-------------------------+
| notes                   |
+-------------------------+
```

---

## 3. Deploy the Application Files

Clone the repository (or copy the files) to the web directory:

```bash
sudo mkdir -p /var/www/studentnotes
sudo git clone https://github.com/KormanCarter/minecraft-server.git /var/www/studentnotes
```

Or if copying manually:

```bash
sudo cp -r /path/to/OpenSpec/* /var/www/studentnotes/
```

Set ownership so Apache can read the files:

```bash
sudo chown -R www-data:www-data /var/www/studentnotes
sudo chmod -R 755 /var/www/studentnotes
```

---

## 4. Configure Apache2 Virtual Host

### 4a. Copy the virtual host configuration

```bash
sudo cp /var/www/studentnotes/config/studentnotes.conf /etc/apache2/sites-available/studentnotes.conf
```

### 4b. Add port 8080 to Apache's listening ports

Edit `/etc/apache2/ports.conf` and add this line if it's not already there:

```
Listen 8080
```

The file should look like:

```apache
Listen 80
Listen 8080
```

### 4c. Enable the site and restart Apache

```bash
sudo a2ensite studentnotes.conf
sudo systemctl restart apache2
```

### 4d. Verify Apache is listening on port 8080

```bash
sudo ss -tlnp | grep 8080
```

Expected output should show Apache listening on `*:8080`.

---

## 5. Configure DNS

Create an **A record** for your domain/subdomain pointing to your server's public IP address.

| Record Type | Host                          | Value              | TTL  |
|-------------|-------------------------------|--------------------|------|
| A           | studentnotes.kormancarter.com | (your server IP)   | 300  |

**Verify DNS resolution:**

```bash
dig studentnotes.kormancarter.com
# or
nslookup studentnotes.kormancarter.com
```

The response should show your server's public IP address.

---

## 6. Firewall Rules

If you're using `ufw` (Uncomplicated Firewall):

```bash
sudo ufw allow 8080/tcp
sudo ufw reload
sudo ufw status
```

If you're on a cloud provider (AWS, DigitalOcean, etc.), also open port 8080 in the **security group / firewall rules** for your instance.

---

## 7. Test the Application

Open your browser and navigate to:

```
http://studentnotes.kormancarter.com:8080
```

Or using the server IP directly:

```
http://<your-server-ip>:8080
```

You should see the Student Notes application with:
- ✅ A form to create notes (CREATE)
- ✅ All existing notes displayed (READ)
- ✅ Edit button on each note (UPDATE)
- ✅ Delete button on each note (DELETE)
- ✅ Search bar and priority filter
- ✅ Statistics dashboard

---

## 8. Troubleshooting

| Problem | Solution |
|---------|----------|
| "Database Connection Failed" | Run `database/setup.sql` in MySQL. Check credentials in `app/index.php`. |
| Page not loading on port 8080 | Verify `Listen 8080` is in `ports.conf`. Restart Apache. |
| 403 Forbidden | Run `sudo chown -R www-data:www-data /var/www/studentnotes` |
| Apache won't start | Check syntax: `sudo apachectl configtest` |
| DNS not resolving | Wait for propagation (up to 24h). Verify A record is correct. |
| Firewall blocking | Run `sudo ufw allow 8080/tcp` and check cloud security groups. |

---

## File Structure

```
studentnotes/
├── app/
│   └── index.php              ← Main application (PHP + HTML + CSS)
├── config/
│   └── studentnotes.conf      ← Apache2 virtual host configuration
├── database/
│   └── setup.sql              ← MySQL database setup script
├── docs/
│   ├── DEPLOYMENT.md          ← This file
│   └── AI_PROMPTS.md          ← AI prompts used during development
├── AGENTS.md                  ← Project guidance for AI agents
├── impact_hub.php             ← VoxelCraft game (bonus project)
└── README.md
```
