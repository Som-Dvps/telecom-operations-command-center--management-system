# SOMNET SMART NETWORK MANAGEMENT SYSTEM (SSNMS)

Tagline: **Predict. Protect. Perform. Somnet.**

SSNMS is a PHP 8+, XAMPP, MySQL, Bootstrap 5, AJAX, and Chart.js telecom network management platform for academic demonstration and enterprise-style NOC prototyping.

## Modules

- Authentication, registration, secure sessions, password hashing, RBAC, and MFA simulation
- Executive dashboard with KPI cards, dark/light mode, live charts, and analytics
- Network device, router, BTS, CPU/RAM, uptime, and traffic monitoring
- Telecom Data Console for AJAX CRUD on devices, traffic logs, Jeeb transactions, security logs, and alert logs
- Search/filter, live previews, statistics cards, Chart.js data previews, and dummy monitoring data generation
- Real-time alert management with severity levels and incident history
- Jeeb financial transaction protection and fraud anomaly simulation
- Security monitoring with IDS, firewall, suspicious IP, and failed-login events
- Rule-based AI predictive analytics for capacity, outage, BTS, and Jeeb anomaly risks
- Reports with CSV, Excel-compatible, and browser PDF export simulation
- Academic blueprint with methodology, KPIs, architecture, cybersecurity, and 5G roadmap

## XAMPP Setup

1. Copy this folder to `C:\xampp\htdocs\somnet-smart-network`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin and import `database/ssnms_db.sql`.
4. Visit `http://localhost/somnet-smart-network/`.
5. Demo login:
   - Email: `admin@somnet.so`
   - Password: `password123`
   - MFA simulation code: `123456`

## Demo Role Accounts

All demo accounts use password `password123` and MFA code `123456`.

- `admin@somnet.so` - Super Admin
- `noc@somnet.so` - NOC Engineer
- `security@somnet.so` - Security Admin
- `jeeb@somnet.so` - Jeeb Service Manager
- `executive@somnet.so` - Executive Manager
- `sysadmin@somnet.so` - System Admin

## Security Notes

The application uses prepared PDO statements, password hashing, CSRF tokens for forms, role checks, XSS escaping, HTTP-only session cookies, and simulated MFA. For production, add HTTPS, real email/SMS MFA, audit trails, rate limiting, and environment-based secrets.
