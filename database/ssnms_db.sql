CREATE DATABASE IF NOT EXISTS ssnms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ssnms_db;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS analytics_logs;
DROP TABLE IF EXISTS security_logs;
DROP TABLE IF EXISTS fraud_alerts;
DROP TABLE IF EXISTS jeeb_transactions;
DROP TABLE IF EXISTS network_logs;
DROP TABLE IF EXISTS alerts;
DROP TABLE IF EXISTS devices;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Super Admin','NOC Engineer','Security Admin','Jeeb Service Manager','Executive Manager','System Admin') NOT NULL,
    status ENUM('Active','Suspended') NOT NULL DEFAULT 'Active',
    mfa_enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    type ENUM('Router','BTS','Core Switch','Firewall','Jeeb API','Server') NOT NULL,
    vendor VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    location VARCHAR(120) NOT NULL,
    status ENUM('Online','Offline','Warning') NOT NULL DEFAULT 'Online',
    cpu_usage INT NOT NULL DEFAULT 0,
    ram_usage INT NOT NULL DEFAULT 0,
    traffic_mbps DECIMAL(10,2) NOT NULL DEFAULT 0,
    uptime_percent DECIMAL(5,2) NOT NULL DEFAULT 99.00,
    last_seen DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NULL,
    alert_type ENUM('BTS Down','Router Offline','High Traffic','Security Threat','Jeeb Transaction Failure') NOT NULL,
    severity ENUM('Low','Medium','High','Critical') NOT NULL,
    message VARCHAR(255) NOT NULL,
    status ENUM('Open','Resolved') NOT NULL DEFAULT 'Open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    CONSTRAINT fk_alert_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE network_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    cpu_usage INT NOT NULL,
    ram_usage INT NOT NULL,
    traffic_mbps DECIMAL(10,2) NOT NULL,
    packet_loss DECIMAL(5,2) NOT NULL DEFAULT 0,
    latency_ms INT NOT NULL DEFAULT 0,
    upload_mbps DECIMAL(10,2) NOT NULL DEFAULT 0,
    download_mbps DECIMAL(10,2) NOT NULL DEFAULT 0,
    uptime_percent DECIMAL(5,2) NOT NULL,
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_network_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE jeeb_transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(80) NOT NULL UNIQUE,
    customer_msisdn VARCHAR(30) NOT NULL,
    sender_msisdn VARCHAR(30) NOT NULL,
    receiver_msisdn VARCHAR(30) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Success','Failed','Pending') NOT NULL,
    api_status ENUM('Healthy','Degraded','Offline') NOT NULL,
    channel ENUM('Mobile App','USSD','Merchant API','Agent') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fraud_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT NULL,
    risk_score INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    status ENUM('Open','Investigating','Closed') NOT NULL DEFAULT 'Open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fraud_transaction FOREIGN KEY (transaction_id) REFERENCES jeeb_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE security_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    failed_attempts INT NOT NULL DEFAULT 0,
    severity ENUM('Low','Medium','High','Critical') NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE analytics_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(120) NOT NULL,
    metric_value DECIMAL(12,2) NOT NULL,
    category VARCHAR(80) NOT NULL,
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    report_type VARCHAR(80) NOT NULL,
    generated_by INT NULL,
    file_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_user FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role, status, mfa_enabled) VALUES
('Somnet Super Admin','admin@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','Super Admin','Active',1),
('NOC Engineer','noc@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','NOC Engineer','Active',1),
('Security Admin','security@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','Security Admin','Active',1),
('Jeeb Manager','jeeb@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','Jeeb Service Manager','Active',1),
('Executive Manager','executive@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','Executive Manager','Active',1),
('System Admin','sysadmin@somnet.so','$2y$10$2kpdz/yNfVjQNiv7CjM0.OVH1.Di5Hw7xOsHhyoyt9VY5CuBZ5cSe','System Admin','Active',1);

INSERT INTO devices (name,type,vendor,ip_address,location,status,cpu_usage,ram_usage,traffic_mbps,uptime_percent,last_seen) VALUES
('Mogadishu-Core-RTR-01','Router','Cisco','10.10.0.1','Mogadishu Core','Online',52,61,72.40,99.92,NOW()),
('Hargeisa-BTS-221','BTS','Huawei','10.20.2.21','Hargeisa','Warning',88,76,91.10,98.20,NOW()),
('Bosaso-BTS-118','BTS','Ericsson','10.30.1.18','Bosaso','Online',44,53,36.80,99.44,NOW()),
('Kismayo-BTS-073','BTS','Nokia','10.40.0.73','Kismayo','Offline',0,0,0.00,94.15,DATE_SUB(NOW(), INTERVAL 34 MINUTE)),
('Jeeb-API-GW-01','Jeeb API','Somnet','10.50.0.10','Data Center','Online',63,58,48.25,99.88,NOW()),
('SOC-FW-EDGE-01','Firewall','Fortinet','10.60.0.1','Security Operations Center','Warning',71,82,66.90,99.10,NOW()),
('Mogadishu-Core-SW-02','Core Switch','Juniper','10.10.0.2','Mogadishu Core','Online',35,49,44.30,99.97,NOW()),
('Baidoa-BTS-044','BTS','Huawei','10.70.0.44','Baidoa','Online',57,62,55.75,99.23,NOW());

INSERT INTO alerts (device_id, alert_type, severity, message, status, created_at) VALUES
(4,'BTS Down','Critical','Kismayo BTS 073 is unreachable and requires immediate field escalation.','Open',DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(2,'High Traffic','High','Hargeisa BTS 221 traffic and CPU are above predictive threshold.','Open',DATE_SUB(NOW(), INTERVAL 18 MINUTE)),
(6,'Security Threat','High','Firewall detected repeated suspicious IP probing.','Open',DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(5,'Jeeb Transaction Failure','Medium','Jeeb API latency increased and transaction failures rose above baseline.','Open',DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
(1,'Router Offline','Low','Short routing adjacency flap recovered automatically.','Resolved',DATE_SUB(NOW(), INTERVAL 2 HOUR));

INSERT INTO network_logs (device_id,cpu_usage,ram_usage,traffic_mbps,packet_loss,latency_ms,upload_mbps,download_mbps,uptime_percent,logged_at) VALUES
(1,48,58,61.20,0.20,18,18.30,42.90,99.91,DATE_SUB(NOW(), INTERVAL 55 MINUTE)),(1,51,59,68.50,0.35,22,20.10,48.40,99.92,DATE_SUB(NOW(), INTERVAL 45 MINUTE)),(1,52,61,72.40,0.45,24,21.70,50.70,99.92,DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(2,78,70,82.10,1.20,64,27.50,54.60,98.30,DATE_SUB(NOW(), INTERVAL 55 MINUTE)),(2,84,73,87.90,1.75,88,31.20,56.70,98.25,DATE_SUB(NOW(), INTERVAL 45 MINUTE)),(2,88,76,91.10,2.10,104,34.10,57.00,98.20,DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(5,58,55,39.80,0.10,15,11.50,28.30,99.89,DATE_SUB(NOW(), INTERVAL 55 MINUTE)),(5,60,57,43.20,0.20,18,12.80,30.40,99.88,DATE_SUB(NOW(), INTERVAL 45 MINUTE)),(5,63,58,48.25,0.40,25,15.25,33.00,99.88,DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(6,62,78,58.40,0.85,42,19.40,39.00,99.14,DATE_SUB(NOW(), INTERVAL 55 MINUTE)),(6,68,80,62.60,1.00,56,22.20,40.40,99.12,DATE_SUB(NOW(), INTERVAL 45 MINUTE)),(6,71,82,66.90,1.45,73,24.60,42.30,99.10,DATE_SUB(NOW(), INTERVAL 35 MINUTE));

INSERT INTO jeeb_transactions (reference_no,customer_msisdn,sender_msisdn,receiver_msisdn,amount,status,api_status,channel,created_at) VALUES
('JEEB-900001','252615001001','252615001001','252622001111',18.50,'Success','Healthy','Mobile App',DATE_SUB(NOW(), INTERVAL 6 DAY)),
('JEEB-900002','252615001002','252615001002','252622001112',240.00,'Success','Healthy','Merchant API',DATE_SUB(NOW(), INTERVAL 5 DAY)),
('JEEB-900003','252615001003','252615001003','252622001113',11.75,'Failed','Degraded','USSD',DATE_SUB(NOW(), INTERVAL 4 DAY)),
('JEEB-900004','252615001004','252615001004','252622001114',510.00,'Success','Healthy','Agent',DATE_SUB(NOW(), INTERVAL 3 DAY)),
('JEEB-900005','252615001005','252615001005','252622001115',75.20,'Failed','Degraded','Mobile App',DATE_SUB(NOW(), INTERVAL 2 DAY)),
('JEEB-900006','252615001006','252615001006','252622001116',39.00,'Success','Healthy','USSD',DATE_SUB(NOW(), INTERVAL 1 DAY)),
('JEEB-900007','252615001007','252615001007','252622001117',880.00,'Pending','Degraded','Merchant API',DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('JEEB-900008','252615001008','252615001008','252622001118',28.40,'Success','Healthy','Mobile App',DATE_SUB(NOW(), INTERVAL 30 MINUTE));

INSERT INTO fraud_alerts (transaction_id,risk_score,description,status,created_at) VALUES
(7,84,'Unusual merchant API value spike from new device fingerprint.','Investigating',DATE_SUB(NOW(), INTERVAL 90 MINUTE)),
(5,68,'Repeated failed payment attempts from same MSISDN.','Open',DATE_SUB(NOW(), INTERVAL 2 HOUR));

INSERT INTO security_logs (event_type,ip_address,failed_attempts,severity,description,created_at) VALUES
('IDS Alert','196.201.45.10',0,'High','Possible port scan targeting edge firewall.',DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('Failed Login','10.60.4.25',3,'Medium','Three failed dashboard logins for security role.',DATE_SUB(NOW(), INTERVAL 18 MINUTE)),
('Firewall Block','185.44.77.21',0,'Critical','Blocked repeated SQL injection payload pattern.',DATE_SUB(NOW(), INTERVAL 14 MINUTE)),
('Suspicious IP','102.22.18.8',0,'Medium','Traffic pattern does not match historical baseline.',DATE_SUB(NOW(), INTERVAL 8 MINUTE));

INSERT INTO analytics_logs (metric_name,metric_value,category,logged_at) VALUES
('Fault Detection Time',1.80,'KPI',NOW()),
('Projected Downtime Reduction',70.00,'KPI',NOW()),
('Jeeb Failure Rate',4.70,'Financial',NOW()),
('Security Mean Response Time',3.20,'Security',NOW());

INSERT INTO reports (title,report_type,generated_by,file_path) VALUES
('Monthly Network Availability','Downtime',1,'exports/monthly-network-availability.pdf'),
('Jeeb Transaction Health','Financial',4,'exports/jeeb-transaction-health.csv'),
('Security Incident Summary','Security',3,'exports/security-incident-summary.pdf');

INSERT INTO system_settings (setting_key,setting_value) VALUES
('site_name','SOMNET SMART NETWORK MANAGEMENT SYSTEM'),
('tagline','Predict. Protect. Perform. Somnet.'),
('alert_email_simulation','noc-alerts@somnet.so'),
('sms_gateway_simulation','+252610000000'),
('ai_threshold_critical','80');
