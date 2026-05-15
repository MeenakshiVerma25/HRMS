<?php
session_start();
include '../includes/db.php';
include 'leave_config.php';

$msg = '';
$msg_type = '';
$user_data = null;
$history_rows = [];

if (isset($_GET['id']) && $_GET['id'] !== '') {
    $uid = intval($_GET['id']);

    $q = mysqli_query($conn, "SELECT user_id, user_name, user_email 
                              FROM users 
                              WHERE user_id = '$uid' AND dele_te='0' AND is_left='no' ");
    if ($q && mysqli_num_rows($q) > 0) {
        $user_data = mysqli_fetch_assoc($q);

        $history = mysqli_query($conn, "SELECT application_id, leave_type_id, from_date, to_date,
                                               total_days, reason, status, applied_at, remarks
                                        FROM leave_applications
                                        WHERE user_id = '$uid'
                                        ORDER BY applied_at DESC
                                        LIMIT 20");
        if ($history) {
            while ($row = mysqli_fetch_assoc($history)) {
                $history_rows[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Portal — HRMS</title>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="../css/leave.css" rel="stylesheet">
</head>
<body>
<div class="page-wrap">

    <div class="portal-header">
        <div class="portal-logo">
            <div class="logo-mark"><i class="fa-solid fa-leaf"></i></div>
            <h1>HRMS</h1>
        </div>
        <span class="portal-badge">Employee Leave Portal</span>
    </div>

    <?php
    if (isset($_SESSION['flash_status'])) {
        if ($_SESSION['flash_status'] === 'success') {
            echo '<div class="alert-bar alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    Leave application submitted successfully!
                </div>';
        }

        if ($_SESSION['flash_status'] === 'error') {
            $err = htmlspecialchars($_SESSION['flash_msg'] ?? 'Something went wrong.');
            echo '<div class="alert-bar alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> ' . $err . '
                </div>';
        }
        
        unset($_SESSION['flash_status']);
        unset($_SESSION['flash_msg']);
    }
    ?>

    <div class="portal-card">
        <div class="section-head">
            <div class="icon-ring"><i class="fa-regular fa-calendar-days"></i></div>
            <div>
                <h2>Leave Application</h2>
                <p>Enter employee ID, verify employee, then fill leave details</p>
            </div>
        </div>

        <form action="leave_db.php" method="POST" id="leaveForm">
            <div class="form-grid">

                <!-- LEFT -->
                <div class="form-col">
                    <div class="col-label">Employee Details</div>

                    <div class="field-group">
                        <label><i class="fa-regular fa-user" style="margin-right:5px;"></i>Employee ID</label>
                        <input type="number" name="id" id="idField" placeholder="Enter your employee ID" required>
                        <small id="idError" style="color:#c0392b; display:none; margin-top:6px;">Invalid user id</small>
                    </div>

                    <div class="field-group">
                        <label><i class="fa-solid fa-id-badge" style="margin-right:5px;"></i>Employee Name</label>
                        <input type="text" id="userNameField" placeholder="Employee name will appear here" readonly>
                    </div>

                    <div class="field-group">
                        <label><i class="fa-regular fa-envelope" style="margin-right:5px;"></i>Email</label>
                        <input type="text" id="userEmailField" placeholder="Employee email will appear here" readonly>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="form-col">
                    <div class="col-label">Leave Details</div>

                    <div class="field-group">
                        <label><i class="fa-solid fa-tags" style="margin-right:5px;"></i>Leave Type</label>
                        <select name="leave_type_id" id="leaveType" required>
                            <option value="">— Select Type —</option>
                            <?php foreach ($LEAVE_TYPES as $id => $lt): ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($lt['name']) ?> (<?= $lt['max_days'] ?> days/yr)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div class="field-group">
                            <label><i class="fa-solid fa-calendar-day" style="margin-right:5px;"></i>From</label>
                            <input type="date" name="from_date" id="fromDate" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="field-group">
                            <label><i class="fa-solid fa-calendar-check" style="margin-right:5px;"></i>To</label>
                            <input type="date" name="to_date" id="toDate" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="day-display">
                        <div>
                            <div class="day-label">Total Working Days</div>
                            <div style="font-size:0.72rem; color:var(--text-ghost); margin-top:2px;">Sundays excluded</div>
                        </div>
                        <div class="day-count" id="dayCount">—</div>
                    </div>

                    <div class="balance-row" id="balanceRow" style="display:none">
                        <div class="balance-chip">
                            <div class="chip-val" id="balEntitled">—</div>
                            <div class="chip-key">Entitled</div>
                        </div>
                        <div class="balance-chip">
                            <div class="chip-val" id="balUsed">—</div>
                            <div class="chip-key">Used</div>
                        </div>
                        <div class="balance-chip ok" id="balChip">
                            <div class="chip-val" id="balRemaining">—</div>
                            <div class="chip-key">Remaining</div>
                        </div>
                    </div>

                    <div class="field-group">
                        <label><i class="fa-regular fa-comment-dots" style="margin-right:5px;"></i>Reason <span style="text-transform:none;font-size:0.7rem;color:var(--text-ghost)">(optional)</span></label>
                        <textarea name="reason" placeholder="Briefly describe the reason for leave…"></textarea>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fa-solid fa-paper-plane"></i>Submit Leave Application
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="history-card" id="historyCard" style="display:none;">
        <div class="section-head">
            <div class="icon-ring"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div>
                <h2>My Leave History</h2>
                <p id="historyTitle">Showing recent applications</p>
            </div>
        </div>

        <table class="history-tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                    <th>Applied On</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody id="historyBody">
                <tr class="empty-row">
                    <td colspan="8"><i class="fa-regular fa-folder-open"></i> No leave applications found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="foot-note">HRMS &copy; <?= date('Y') ?> · Leave Portal · All sessions are encrypted</p>
</div>

<script>
function countWorkingDays(from, to) {
    if (!from || !to) return 0;
    let start = new Date(from), end = new Date(to), count = 0;
    if (start > end) return -1;
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        if (d.getDay() !== 0) count++;
    }
    return count;
}

function updateDayCount() {
    const from = document.getElementById('fromDate').value;
    const to   = document.getElementById('toDate').value;
    const el   = document.getElementById('dayCount');

    if (!from || !to) {
        el.textContent = '—';
        return;
    }

    const days = countWorkingDays(from, to);

    if (days < 0) {
        el.textContent = '!';
        el.style.color = '#c0392b';
    } else if (days === 0) {
        el.textContent = '0';
        el.style.color = '#b87a2a';
    } else {
        el.textContent = days;
        el.style.color = 'var(--rose-deep)';
    }

    fetchBalance();
}

function clearUserDetails() {
    document.getElementById('userNameField').value = '';
    document.getElementById('userEmailField').value = '';

    const historyBody = document.getElementById('historyBody');
    const historyCard = document.getElementById('historyCard');

    if (historyBody) {
        historyBody.innerHTML = `
            <tr class="empty-row">
                <td colspan="8"><i class="fa-regular fa-folder-open"></i> No leave applications found.</td>
            </tr>
        `;
    }

    if (historyCard) {
        historyCard.style.display = 'none';
    }

    document.getElementById('balanceRow').style.display = 'none';
}

function renderHistory(history, userName) {
    const historyCard = document.getElementById('historyCard');
    const historyBody = document.getElementById('historyBody');
    const historyTitle = document.getElementById('historyTitle');

    if (!historyCard || !historyBody || !historyTitle) return;

    historyTitle.textContent = 'Showing recent applications for ' + userName;

    if (!history || history.length === 0) {
        historyBody.innerHTML = `
            <tr class="empty-row">
                <td colspan="8"><i class="fa-regular fa-folder-open"></i> No leave applications found.</td>
            </tr>
        `;
    } else {
        historyBody.innerHTML = history.map(row => {
            let icon = '';
            if (row.status === 'Pending') icon = '<i class="fa-regular fa-clock"></i>';
            if (row.status === 'Approved') icon = '<i class="fa-solid fa-check"></i>';
            if (row.status === 'Rejected') icon = '<i class="fa-solid fa-xmark"></i>';

            return `
                <tr>
                    <td>#${row.application_id}</td>
                    <td>${row.leave_type_name}</td>
                    <td>${row.from_date}</td>
                    <td>${row.to_date}</td>
                    <td>${row.total_days}</td>
                    <td>${row.applied_at}</td>
                    <td>
                        <span class="badge-status badge-${row.status.toLowerCase()}">
                            ${icon} ${row.status}
                        </span>
                    </td>
                    <td>${row.remarks}</td>
                </tr>
            `;
        }).join('');
    }

    historyCard.style.display = 'block';
}

function fetchUserDetails() {
    const id = document.getElementById('idField').value.trim();
    const idError = document.getElementById('idError');

    if (!id) {
        clearUserDetails();
        idError.style.display = 'none';
        return;
    }

    const fd = new FormData();
    fd.append('action', 'get_user_details');
    fd.append('id', id);

    fetch('leave_db.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                idError.style.display = 'none';
                document.getElementById('userNameField').value = d.user_name || '';
                document.getElementById('userEmailField').value = d.user_email || '';
                renderHistory(d.history, d.user_name);
                fetchBalance();
            } else {
                clearUserDetails();
                idError.style.display = 'block';
            }
        })
        .catch(() => {
            clearUserDetails();
            idError.style.display = 'block';
        });
}

function fetchBalance() {
    const id   = document.getElementById('idField').value.trim();
    const ltid = document.getElementById('leaveType').value;
    const from = document.getElementById('fromDate').value;

    if (!id || !ltid || !from) {
        document.getElementById('balanceRow').style.display = 'none';
        return;
    }

    const fd = new FormData();
    fd.append('action', 'get_balance');
    fd.append('id', id);
    fd.append('leave_type_id', ltid);
    fd.append('from_date', from);

    fetch('leave_db.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                document.getElementById('balanceRow').style.display = 'grid';
                document.getElementById('balEntitled').textContent = d.entitled;
                document.getElementById('balUsed').textContent = d.used;
                document.getElementById('balRemaining').textContent = d.remaining;

                const chip = document.getElementById('balChip');
                chip.className = 'balance-chip ' + (parseFloat(d.remaining) > 0 ? 'ok' : 'danger');
            } else {
                document.getElementById('balanceRow').style.display = 'none';
            }
        })
        .catch(() => {
            document.getElementById('balanceRow').style.display = 'none';
        });
}

document.getElementById('idField').addEventListener('input', fetchUserDetails);

document.getElementById('fromDate').addEventListener('change', function () {
    document.getElementById('toDate').min = this.value;
    if (document.getElementById('toDate').value < this.value) {
        document.getElementById('toDate').value = this.value;
    }
    updateDayCount();
});

document.getElementById('toDate').addEventListener('change', updateDayCount);
document.getElementById('leaveType').addEventListener('change', fetchBalance);

document.getElementById('leaveForm').addEventListener('submit', function (e) {
    const id = document.getElementById('idField').value.trim();
    const userName = document.getElementById('userNameField').value.trim();

    if (!id || !userName) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Invalid user id',
            text: 'Please enter a valid user id.',
            confirmButtonColor: '#c2637a'
        });
        return;
    }

    const days = countWorkingDays(
        document.getElementById('fromDate').value,
        document.getElementById('toDate').value
    );

    if (days < 1) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Dates',
            text: 'Please select valid dates spanning at least 1 working day.',
            confirmButtonColor: '#c2637a'
        });
        return;
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
});
</script>
</body>
</html>