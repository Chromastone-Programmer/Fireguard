# 🔥 FireGuard - Mini Firewall Dashboard

A full-stack web application to **manage, monitor, and visualize firewall rules and network traffic** in real time. Built as a portfolio project demonstrating skills in PHP, MySQL, AJAX, and cybersecurity concepts.

---

## 🎯 Features

- **Firewall Rule Management** — Create, toggle, and delete rules with protocol/port/IP filters (ALLOW / BLOCK / LOG)
- **Live Traffic Logs** — View all inbound/outbound traffic decisions with AJAX auto-refresh (every 30s)
- **IP Blacklist** — Block/unblock IP addresses with reason tracking; validated on add
- **Threat Alerts** — Real-time alerts for PORT_SCAN, BRUTE_FORCE, DDoS, RULE_VIOLATION with severity levels
- **Admin Portal** — Role-based access (Admin vs Viewer); admins can mutate rules and blacklist
- **Dashboard Overview** — Stats cards + doughnut chart for traffic split; recent logs table

---

## 🛠️ Tech Stack

| Layer     | Technology              |
|-----------|-------------------------|
| Backend   | PHP 8.x + PDO           |
| Database  | MySQL 8.x               |
| Frontend  | HTML5 / CSS3 / JS       |
| AJAX      | Vanilla Fetch API       |
| Charts    | Chart.js                |

---

## 🚀 Setup

### Requirements
- PHP 8.0+
- MySQL 8.0+
- Apache / Nginx with mod_rewrite

### Install

```bash
# 1. Clone the repo
git clone https://github.com/yourusername/fireguard-dashboard.git
cd fireguard-dashboard

# 2. Import the database schema
mysql -u root -p < schema.sql

# 3. Configure database credentials
cp config.php config.local.php
# Edit DB_USER, DB_PASS in config.php

# 4. Point your web server to this directory
# Apache: Set DocumentRoot to /path/to/fireguard-dashboard
# OR use PHP built-in server for dev:
php -S localhost:8000
```

### Default Credentials
| Username | Password | Role  |
|----------|----------|-------|
| admin    | password | Admin |
| viewer   | password | Viewer|

> ⚠️ Change passwords immediately in production using `password_hash()`.

---

## 📁 Project Structure

```
fireguard-dashboard/
├── index.php          # Entry point + auth redirect
├── login.php          # Authentication page
├── dashboard.php      # Main overview dashboard
├── rules.php          # Firewall rules management
├── logs.php           # Traffic log viewer (paginated)
├── blacklist.php      # IP blacklist management
├── alerts.php         # Threat alert center
├── admin.php          # Admin-only user management
├── logout.php         # Session destroy
├── config.php         # DB connection + auth helpers
├── schema.sql         # Full database schema + sample data
└── api/
    ├── rules.php      # REST API: CRUD for firewall rules
    ├── logs.php       # REST API: paginated traffic logs
    └── blacklist.php  # REST API: IP blacklist management
```

---

## 🔐 Security Features Implemented

- **PDO Prepared Statements** — 100% SQL injection prevention
- **Session-based Auth** — `session_start()` + `session_destroy()` on logout
- **Role-Based Access Control** — Admin vs Viewer enforced server-side
- **Input Validation** — IP format via `filter_var(FILTER_VALIDATE_IP)`, port range checks
- **XSS Prevention** — `htmlspecialchars()` on all user-supplied output
- **Password Hashing** — `password_hash()` + `password_verify()` (bcrypt)
- **CSRF Awareness** — AJAX requests verified via `X-Requested-With` header check

---

## 🌐 Networking Concepts Applied

This project directly implements concepts from:
- **OSI Layer 3/4 filtering** — IP + Port + Protocol based rules (like iptables)
- **Stateless packet filtering** — Each traffic log represents a discrete decision
- **Blacklisting vs Whitelisting** — Block-by-default philosophy with ALLOW rules
- **Intrusion Detection concepts** — Port scan and brute-force pattern alerts
- **DMZ / Gateway architecture** — Admin / Partner / Device portal separation via roles

---

## 📸 Screenshots

> Dashboard, Rules, and Blacklist pages available in `/screenshots/` folder.

---

## 📄 License

MIT License — Free to use and modify.
