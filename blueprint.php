<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['Super Admin', 'Executive Manager', 'System Admin']);
$pageTitle = 'Academic & Enterprise Blueprint';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-12">
        <div class="panel">
            <h2 class="section-title">Conceptual Framework</h2>
            <div class="architecture-grid">
                <div class="panel layer"><strong>Input</strong><p class="muted mb-0">Network devices, traffic data, Jeeb transaction logs, security logs.</p></div>
                <div class="panel layer"><strong>Process</strong><p class="muted mb-0">AI analytics, monitoring engine, fault prediction, security analysis.</p></div>
                <div class="panel layer"><strong>Output</strong><p class="muted mb-0">Alerts, reports, reduced downtime, protected Jeeb, better customer experience.</p></div>
                <div class="panel layer"><strong>Impact</strong><p class="muted mb-0">Predictive digital sovereignty and resilient national communications.</p></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel h-100">
            <h2 class="section-title">Chapter 3 Methodology</h2>
            <p><strong>Research Design:</strong> Mixed method using qualitative interviews and quantitative incident analysis.</p>
            <p><strong>Data Collection:</strong> NOC interviews, observation, incident logs, Jeeb failure records, and security event samples.</p>
            <p><strong>Sampling:</strong> Somnet IT staff, NOC engineers, security administrators, Jeeb operations staff, and managers.</p>
            <p class="mb-0"><strong>Data Analysis:</strong> Comparative downtime analysis, detection-time measurement, transaction failure trends, and dashboard usability review.</p>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel h-100">
            <h2 class="section-title">Measurable KPIs</h2>
            <div class="table-responsive"><table class="table"><thead><tr><th>KPI</th><th>Current</th><th>SSNMS Goal</th></tr></thead><tbody>
                <tr><td>Fault Detection Time</td><td>30 mins</td><td>&lt;2 mins</td></tr>
                <tr><td>Downtime</td><td>High</td><td>Reduced by 70%</td></tr>
                <tr><td>Jeeb Failures</td><td>Frequent</td><td>&lt;5%</td></tr>
                <tr><td>Security Response</td><td>Manual</td><td>Automated workflow</td></tr>
            </tbody></table></div>
        </div>
    </div>
    <div class="col-12">
        <div class="panel">
            <h2 class="section-title">System Architecture</h2>
            <div class="architecture-grid">
                <div class="panel layer"><strong>Presentation Layer</strong><p class="muted mb-0">Dashboard, admin portal, charts, reports.</p></div>
                <div class="panel layer"><strong>Application Layer</strong><p class="muted mb-0">Monitoring engine, alert engine, security engine, Jeeb module.</p></div>
                <div class="panel layer"><strong>Data Layer</strong><p class="muted mb-0">MySQL, log storage, analytics database.</p></div>
                <div class="panel layer"><strong>Infrastructure Layer</strong><p class="muted mb-0">Routers, BTS, core network, APIs, firewall feeds.</p></div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel h-100">
            <h2 class="section-title">Cybersecurity Enhancement</h2>
            <p class="muted mb-0">IDS simulation, firewall log integration, RBAC, MFA simulation, SIEM-ready event tables, secure sessions, prepared SQL statements, XSS escaping, and password hashing.</p>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel h-100">
            <h2 class="section-title">Business Impact</h2>
            <p class="muted mb-0">If downtime costs Somnet $5,000/hour and monthly downtime is 20 hours, the exposure is $100,000/month. A 70% SSNMS reduction saves approximately $70,000/month.</p>
        </div>
    </div>
    <div class="col-12">
        <div class="panel">
            <h2 class="section-title">Future 5G Expansion</h2>
            <p class="muted mb-0">SSNMS should evolve into a 5G-ready autonomous telecom intelligence platform capable of network slicing management, edge analytics, and national smart infrastructure support.</p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
