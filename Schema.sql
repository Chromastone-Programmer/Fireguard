-- CREATE DATABASE IF NOT EXISTS firewall_db;
-- USE firewall_db;

-- CREATE TABLE users(
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     username VARCHAR()50 UNIQUE NOT NULL,
--     password_has VARCHAR(255) NOT NULL,
--     role ENUM('admin','viewer') DEFAULT 'viewer',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );
-- CREATE TABLE firewall_rules(
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     rule_name VARCHAR(100) NOT NULL,
--     protocal ENUM('TCP','UDP','ICMP','ANY') NOT NULL,
--     source_ip VARCHAR(45) DEFAULT 'ANY',
--     dest_ip VARCHAR (45) DEFAULT 'ANY',
--     phd VARCHAR   
-- );





-- ============================================
-- WHY: IF NOT EXISTS means running this file
-- twice won't throw an error or destroy data
-- ============================================
CREATE DATABASE IF NOT EXISTS firewall_db;
USE firewall_db;

-- ============================================
-- USERS TABLE
-- WHY: Stores login credentials
-- password_hash → never store plain passwords
-- role → controls what each user can do
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,        -- bcrypt hash
    role ENUM('admin', 'viewer') DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- FIREWALL RULES TABLE
-- WHY: Each row = one rule like iptables
-- protocol + source_ip + dest_ip + port = 
-- defines what traffic this rule matches
-- action = what to do when traffic matches
-- priority = lower number = checked first
-- is_active = toggle rule on/off without deleting
-- ============================================
CREATE TABLE firewall_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    protocol ENUM('TCP', 'UDP', 'ICMP', 'ANY') NOT NULL,
    source_ip VARCHAR(45) DEFAULT 'ANY',
    dest_ip VARCHAR(45) DEFAULT 'ANY',
    source_port VARCHAR(10) DEFAULT 'ANY',
    dest_port VARCHAR(10) DEFAULT 'ANY',
    action ENUM('ALLOW', 'BLOCK', 'LOG') NOT NULL,
    priority INT DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ============================================
-- TRAFFIC LOGS TABLE
-- WHY: Every packet decision gets recorded
-- This is how you audit what happened
-- rule_matched → which rule caused this decision
-- ON DELETE SET NULL → if rule deleted log stays
-- ============================================
CREATE TABLE traffic_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_ip VARCHAR(45) NOT NULL,
    dest_ip VARCHAR(45) NOT NULL,
    protocol VARCHAR(10),
    dest_port INT,
    action_taken ENUM('ALLOWED', 'BLOCKED') NOT NULL,
    rule_matched INT,
    bytes_transferred INT DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rule_matched) REFERENCES firewall_rules(id) ON DELETE SET NULL
);

-- ============================================
-- BLOCKED IPS TABLE
-- WHY: Manual blacklist separate from rules
-- Sometimes you want to block a specific IP
-- immediately without creating a full rule
-- expires_at → temporary blocks auto expire
-- ============================================
CREATE TABLE blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    reason VARCHAR(255),
    blocked_by INT,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (blocked_by) REFERENCES users(id)
);

-- ============================================
-- ALERTS TABLE
-- WHY: Suspicious patterns need to be flagged
-- This is basic IDS functionality
-- is_resolved → track which alerts were handled
-- severity → prioritize what needs attention first
-- ============================================
CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('PORT_SCAN','BRUTE_FORCE','DDoS',
                    'SUSPICIOUS_TRAFFIC','RULE_VIOLATION') NOT NULL,
    source_ip VARCHAR(45),
    description TEXT,
    severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') DEFAULT 'MEDIUM',
    is_resolved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SAMPLE DATA
-- WHY: So dashboard shows real content
-- password hash below = 'password' in bcrypt
-- ============================================
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer');

-- WHY these specific rules:
-- Port 23 (Telnet) blocked → unencrypted, dangerous
-- Port 80/443 allowed → normal web traffic
-- Port 21 (FTP) blocked → unencrypted file transfer
-- Port 53 (DNS) allowed → needed for domain resolution
-- Port 22 (SSH) blocked from external → prevent remote attacks
INSERT INTO firewall_rules (rule_name, protocol, source_ip, dest_ip, dest_port, action, priority) VALUES
('Block Telnet',          'TCP',  'ANY',       'ANY', '23',  'BLOCK', 10),
('Allow HTTP',            'TCP',  'ANY',       'ANY', '80',  'ALLOW', 20),
('Allow HTTPS',           'TCP',  'ANY',       'ANY', '443', 'ALLOW', 20),
('Block FTP',             'TCP',  'ANY',       'ANY', '21',  'BLOCK', 10),
('Allow DNS',             'UDP',  'ANY',       'ANY', '53',  'ALLOW', 30),
('Block SSH from External','TCP', '0.0.0.0/0', 'ANY', '22', 'BLOCK',  5),
('Log ICMP',              'ICMP', 'ANY',       'ANY', 'ANY', 'LOG',   50);

INSERT INTO blocked_ips (ip_address, reason) VALUES
('192.168.1.99', 'Multiple failed login attempts'),
('10.0.0.55',    'Port scan detected'),
('172.16.0.200', 'Known malicious IP');

INSERT INTO alerts (alert_type, source_ip, description, severity) VALUES
('PORT_SCAN',          '10.0.0.55',    'Rapid sequential port probing detected', 'HIGH'),
('BRUTE_FORCE',        '192.168.1.99', '15 failed SSH login attempts in 60 seconds', 'CRITICAL'),
('SUSPICIOUS_TRAFFIC', '172.16.0.200', 'Unusual outbound traffic to known C2 server', 'HIGH');

INSERT INTO traffic_logs (source_ip, dest_ip, protocol, dest_port, action_taken, rule_matched, bytes_transferred) VALUES
('192.168.1.10', '8.8.8.8',    'TCP', 443, 'ALLOWED', 3, 1240),
('192.168.1.15', '8.8.8.8',    'TCP', 80,  'ALLOWED', 2, 890),
('10.0.0.55',    '192.168.1.1','TCP', 22,  'BLOCKED', 6, 0),
('172.16.0.200', '192.168.1.20','TCP',23,  'BLOCKED', 1, 0),
('192.168.1.30', '1.1.1.1',    'UDP', 53,  'ALLOWED', 5, 120),
('192.168.1.99', '192.168.1.1','TCP', 22,  'BLOCKED', 6, 0),
('192.168.1.12', '104.16.0.1', 'TCP', 443, 'ALLOWED', 3, 5430);