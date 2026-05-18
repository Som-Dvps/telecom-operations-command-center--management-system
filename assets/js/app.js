const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const html = document.documentElement;
        const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
        html.dataset.theme = next;
        document.cookie = `ssnms_theme=${next}; path=/; max-age=31536000`;
    });
}

function counter(el) {
    const target = Number(el.dataset.count || 0);
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 32));
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = current.toLocaleString();
    }, 24);
}
document.querySelectorAll('[data-count]').forEach(counter);

async function getJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    return res.json();
}

function renderChart(id, type, labels, datasets) {
    const canvas = document.getElementById(id);
    if (!canvas || !window.Chart) return;
    return new Chart(canvas, {
        type,
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: getComputedStyle(document.body).color } } },
            scales: {
                x: { ticks: { color: '#8b98aa' }, grid: { color: 'rgba(148,163,184,.15)' } },
                y: { ticks: { color: '#8b98aa' }, grid: { color: 'rgba(148,163,184,.15)' } }
            }
        }
    });
}

if (document.getElementById('trafficChart')) {
    getJson('api/metrics.php').then(data => {
        renderChart('trafficChart', 'line', data.traffic.labels, [
            { label: 'Traffic Mbps', data: data.traffic.values, borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,.15)', tension: .35, fill: true }
        ]);
        renderChart('alertsChart', 'doughnut', data.alerts.labels, [
            { label: 'Alerts', data: data.alerts.values, backgroundColor: ['#dc2626', '#d97706', '#2563eb', '#16a34a'] }
        ]);
        renderChart('jeebChart', 'bar', data.jeeb.labels, [
            { label: 'Transactions', data: data.jeeb.success, backgroundColor: '#16a34a' },
            { label: 'Failed', data: data.jeeb.failed, backgroundColor: '#dc2626' }
        ]);
    });
}

if (document.getElementById('liveDevices')) {
    const refresh = async () => {
        const data = await getJson('api/live.php');
        document.getElementById('liveDevices').innerHTML = data.devices.map(device => `
            <tr>
                <td><span class="status-dot ${device.status}"></span>${device.name}</td>
                <td>${device.type}</td>
                <td>${device.location}</td>
                <td>${device.cpu_usage}%</td>
                <td>${device.ram_usage}%</td>
                <td>${device.traffic_mbps} Mbps</td>
                <td>${device.status}</td>
            </tr>
        `).join('');
        const alerts = document.getElementById('liveAlerts');
        if (alerts) {
            alerts.innerHTML = data.alerts.map(alert => `
                <tr>
                    <td>${alert.alert_type}</td>
                    <td>${alert.severity}</td>
                    <td>${alert.message}</td>
                    <td>${alert.created_at}</td>
                </tr>
            `).join('');
        }
    };
    refresh();
    setInterval(refresh, 12000);
}

const dataForm = document.getElementById('dataForm');
if (dataForm) {
    const state = { dataset: 'devices', chart: null, rows: [] };
    const dataTable = document.getElementById('dataTable');
    const fields = document.getElementById('dynamicFields');
    const search = document.getElementById('dataSearch');
    const predictions = document.getElementById('predictionPreview');
    const csrf = window.SSNMS_DATA.csrf;
    const deviceOptions = window.SSNMS_DATA.devices.map(d => `<option value="${d.id}">${d.name}</option>`).join('');

    const schemas = {
        devices: [
            ['name', 'Device Name'], ['type', 'Device Type', 'select', ['Router','BTS','Core Switch','Firewall','Jeeb API','Server']],
            ['vendor', 'Vendor'], ['ip_address', 'IP Address'], ['location', 'Location'],
            ['status', 'Status', 'select', ['Online','Offline','Warning']], ['cpu_usage', 'CPU Usage', 'number'],
            ['ram_usage', 'RAM Usage', 'number'], ['traffic_mbps', 'Bandwidth Mbps', 'number'], ['uptime_percent', 'Uptime %', 'number']
        ],
        traffic: [
            ['device_id', 'Device', 'device'], ['traffic_mbps', 'Bandwidth Usage Mbps', 'number'],
            ['packet_loss', 'Packet Loss %', 'number'], ['latency_ms', 'Latency ms', 'number'],
            ['upload_mbps', 'Upload Speed Mbps', 'number'], ['download_mbps', 'Download Speed Mbps', 'number'],
            ['cpu_usage', 'CPU Usage', 'number'], ['ram_usage', 'RAM Usage', 'number'], ['uptime_percent', 'Uptime %', 'number']
        ],
        jeeb: [
            ['reference_no', 'Transaction ID'], ['sender_msisdn', 'Sender'], ['receiver_msisdn', 'Receiver'],
            ['amount', 'Amount', 'number'], ['status', 'Status', 'select', ['Success','Failed','Pending']],
            ['api_status', 'API Status', 'select', ['Healthy','Degraded','Offline']], ['channel', 'Channel', 'select', ['Mobile App','USSD','Merchant API','Agent']]
        ],
        security: [
            ['event_type', 'Threat Type', 'select', ['IDS Alert','Firewall Block','Failed Login','Suspicious IP']],
            ['ip_address', 'IP Address'], ['failed_attempts', 'Failed Login Attempts', 'number'],
            ['severity', 'Severity', 'select', ['Low','Medium','High','Critical']], ['description', 'Firewall Event / Description']
        ],
        alerts: [
            ['device_id', 'Device Name', 'device'], ['alert_type', 'Alert Type', 'select', ['BTS Down','Router Offline','High Traffic','Security Threat','Jeeb Transaction Failure']],
            ['severity', 'Severity', 'select', ['Low','Medium','High','Critical']], ['status', 'Status', 'select', ['Open','Resolved']], ['message', 'Message']
        ]
    };

    const columns = {
        devices: ['name','type','ip_address','cpu_usage','ram_usage','status','location'],
        traffic: ['device_name','traffic_mbps','packet_loss','latency_ms','upload_mbps','download_mbps','logged_at'],
        jeeb: ['reference_no','sender_msisdn','receiver_msisdn','amount','status','created_at'],
        security: ['ip_address','failed_attempts','event_type','severity','description','created_at'],
        alerts: ['alert_type','severity','status','device_name','created_at']
    };

    function humanize(key) {
        return key.replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function renderFields(row = {}) {
        document.getElementById('recordId').value = row.id || '';
        fields.innerHTML = schemas[state.dataset].map(([name, label, type = 'text', options = []]) => {
            const value = row[name] ?? '';
            if (type === 'select') {
                return `<label class="form-label">${label}</label><select class="form-select mb-3" name="${name}">${options.map(o => `<option ${value === o ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
            }
            if (type === 'device') {
                return `<label class="form-label">${label}</label><select class="form-select mb-3" name="${name}"><option value="">Platform</option>${deviceOptions}</select>`;
            }
            return `<label class="form-label">${label}</label><input class="form-control mb-3" type="${type}" step="0.01" name="${name}" value="${String(value).replaceAll('"', '&quot;')}">`;
        }).join('');
        if (row.device_id) {
            const select = fields.querySelector('[name="device_id"]');
            if (select) select.value = row.device_id;
        }
    }

    function renderTable(rows) {
        const keys = columns[state.dataset];
        dataTable.innerHTML = `<thead><tr>${keys.map(k => `<th>${humanize(k)}</th>`).join('')}<th>Actions</th></tr></thead><tbody>${rows.map((row, index) => `
            <tr>
                ${keys.map(k => `<td>${row[k] ?? ''}</td>`).join('')}
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary me-1" data-edit="${index}"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-delete="${row.id}"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        `).join('')}</tbody>`;
    }

    function renderPreviewChart(rows) {
        const ctx = document.getElementById('dataPreviewChart');
        if (!ctx) return;
        if (state.chart) state.chart.destroy();
        const labels = rows.slice(0, 10).map((row, i) => row.name || row.device_name || row.reference_no || row.event_type || row.alert_type || `#${i + 1}`).reverse();
        const values = rows.slice(0, 10).map(row => Number(row.cpu_usage || row.traffic_mbps || row.amount || row.failed_attempts || (row.severity === 'Critical' ? 4 : row.severity === 'High' ? 3 : row.severity === 'Medium' ? 2 : 1) || 0)).reverse();
        state.chart = renderChart('dataPreviewChart', 'bar', labels, [{ label: humanize(state.dataset), data: values, backgroundColor: '#0f766e' }]);
    }

    async function loadRows() {
        const data = await getJson(`api/data.php?action=list&dataset=${state.dataset}&search=${encodeURIComponent(search.value)}`);
        state.rows = data.rows || [];
        renderTable(state.rows);
        renderPreviewChart(state.rows);
    }

    async function loadStats() {
        const data = await getJson('api/data.php?action=stats');
        const values = [data.stats.devices, data.stats.active_devices, data.stats.alerts, data.stats.security, data.stats.jeeb_health, data.stats.uptime];
        document.querySelectorAll('#dataStats .value').forEach((el, i) => el.textContent = values[i]);
        predictions.innerHTML = (data.predictions || []).slice(0, 5).map(item => `
            <div class="d-flex justify-content-between border-bottom py-2">
                <div><strong>${item.target}</strong><div class="muted small">${item.label}</div></div>
                <span class="badge badge-soft">${item.risk}%</span>
            </div>
        `).join('');
    }

    async function postData(action, extra = {}) {
        const body = Object.fromEntries(new FormData(dataForm).entries());
        const res = await fetch('api/data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ...body, ...extra, action, dataset: state.dataset, csrf_token: csrf })
        });
        return res.json();
    }

    document.getElementById('datasetTabs').addEventListener('click', event => {
        const button = event.target.closest('[data-dataset]');
        if (!button) return;
        document.querySelectorAll('#datasetTabs .nav-link').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        state.dataset = button.dataset.dataset;
        renderFields();
        loadRows();
    });

    dataForm.addEventListener('submit', async event => {
        event.preventDefault();
        const data = await postData('save');
        if (!data.ok) alert(data.message || 'Save failed');
        renderFields();
        await loadRows();
        await loadStats();
    });

    dataTable.addEventListener('click', async event => {
        const edit = event.target.closest('[data-edit]');
        const del = event.target.closest('[data-delete]');
        if (edit) renderFields(state.rows[Number(edit.dataset.edit)]);
        if (del && confirm('Delete this record?')) {
            const data = await postData('delete', { id: del.dataset.delete });
            if (!data.ok) alert(data.message || 'Delete failed');
            await loadRows();
            await loadStats();
        }
    });

    document.getElementById('resetForm').addEventListener('click', () => renderFields());
    document.getElementById('generateSample').addEventListener('click', async () => {
        const data = await postData('generate');
        if (!data.ok) alert(data.message || 'Generation failed');
        await loadRows();
        await loadStats();
    });
    search.addEventListener('input', () => {
        clearTimeout(search._timer);
        search._timer = setTimeout(loadRows, 250);
    });

    renderFields();
    loadRows();
    loadStats();
    setInterval(() => { loadRows(); loadStats(); }, 15000);
}
