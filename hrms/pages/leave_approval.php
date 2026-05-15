<?php
include '../includes/header.php';
include '../leave_module/leave_config.php';

$counts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(status='Pending')  AS pending,
        SUM(status='Approved') AS approved,
        SUM(status='Rejected') AS rejected
     FROM leave_applications"));
?>

<div class="dashboard-wrapper">
    <div class="container-fluid">
        <div class="dash-section-title">Leave Management</div>
        <div class="dash-section-sub">Review, approve or reject employee leave applications</div>

        <div class="row g-4">
            <?php
            $chips = [
                ['Total',    $counts['total'],    'fa-layer-group',      'var(--rose-mid)'],
                ['Pending',  $counts['pending'],  'fa-clock',            '#b87a2a'],
                ['Approved', $counts['approved'], 'fa-circle-check',     '#2e7d52'],
                ['Rejected', $counts['rejected'], 'fa-circle-xmark',     'var(--rose-deep)'],
            ];
            foreach ($chips as [$label, $val, $icon, $color]):
            ?>
            <div class="col-xl-3 col-md-6">
                <div class="dash-card">
                    <div class="card-icon">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>  
                    <div class="card-info">
                        <div class="stat-value" style="color:<?= $color ?>"><?= intval($val) ?></div>
                        <div class="stat-label"><?= $label ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <hr class="dash-divider mt-5">

        <!-- ── Filter tabs ──────────────────────────────────────────────────── -->
        <div class="content-card dataTables_wrapper">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="icon-ring"><i class="fa-regular fa-calendar-days"></i></div>
                    <span style="font-weight:600;color:var(--text-primary)">All Leave Applications</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;" id="filterBtns">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="Pending">Pending</button>
                    <button class="filter-btn" data-status="Approved">Approved</button>
                    <button class="filter-btn" data-status="Rejected">Rejected</button>
                </div>
            </div>

            <!-- ── Table ────────────────────────────────────────────────────── -->
            <div class="table-responsive">
                <table id="leaveTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Reviewed By</th>
                            <?php if ($role === 'Super_admin' || $role === 'Manager' || $role === 'HR_admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($conn,
                        "SELECT la.*, u.user_name, u.user_email
                        FROM leave_applications la
                        JOIN users u ON u.user_id = la.user_id
                        ORDER BY la.applied_at DESC");

                    while ($row = mysqli_fetch_assoc($res)):
                        $lt_name = $LEAVE_TYPES[$row['leave_type_id']]['name'] ?? 'Unknown';
                        $status  = $row['status'];
                        $badgeCls = ['Pending'=>'badge-pending','Approved'=>'badge-approved','Rejected'=>'badge-rejected'][$status] ?? '';
                    ?>
                    <tr data-status="<?= $status ?>" style="font-size:0.875rem;">
                        <td><?= $row['application_id'] ?></td>
                        <td>
                            <div style="font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($row['user_name']) ?></div>
                            <div style="font-size:0.74rem;color:var(--text-secondary)"><?= htmlspecialchars($row['user_email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($lt_name) ?></td>
                        <td><?= date('d M Y', strtotime($row['from_date'])) ?></td>
                        <td><?= date('d M Y', strtotime($row['to_date'])) ?></td>
                        <td><strong><?= $row['total_days'] ?></strong></td>
                        <td style="max-width:160px;white-space:normal;"><?= htmlspecialchars($row['reason'] ?: '—') ?></td>
                        <td><?= date('d M Y', strtotime($row['applied_at'])) ?></td>
                        <td>
                            <span class="badge-status <?= $badgeCls ?>">
                                <?php if($status==='Pending')  echo '<i class="fa-regular fa-clock"></i>'; ?>
                                <?php if($status==='Approved') echo '<i class="fa-solid fa-check"></i>'; ?>
                                <?php if($status==='Rejected') echo '<i class="fa-solid fa-xmark"></i>'; ?>
                                <?= $status ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['reviewed_by'] ?: '—') ?></td>
                        <?php if ($role === 'Super_admin' || $role === 'Manager' || $role === 'HR_admin'): ?>
                        <td>
                            <?php if ($status === 'Pending'): ?>
                            <div style="display:flex;gap:6px;">
                                <button class="act-btn approve-btn"
                                        onclick="openReview(<?= $row['application_id'] ?>,'Approved')"
                                        title="Approve">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button class="act-btn reject-btn"
                                        onclick="openReview(<?= $row['application_id'] ?>,'Rejected')"
                                        title="Reject">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <button class="act-btn pending-btn"
                                    onclick="openReview(<?= $row['application_id'] ?>,'Pending')"
                                    title="Reset to Pending">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <!-- ── Review Modal ──────────────────────────────────────────────────────── -->
    <div class="modal-overlay" id="reviewModal" style="display:none;">
        <div class="modal-box" style="max-width:480px;">
            <div class="modal-head">
                <h3 id="modalTitle">Review Leave</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalAppId">
                <input type="hidden" id="modalStatus">

                <div style="margin-bottom:16px;">
                    <label class="form-label">Remarks <span style="font-weight:300;text-transform:none;font-size:0.75rem;">(optional)</span></label>
                    <textarea id="modalRemarks" class="form-control" rows="3"
                            placeholder="Add a note for the employee…"></textarea>
                </div>

                <div id="modalInfo" style="padding:12px 14px;background:var(--bg-card-hi);border-radius:10px;font-size:0.83rem;color:var(--text-secondary);margin-bottom:18px;"></div>

                <div style="display:flex;gap:10px;">
                    <button class="btn-cancel" onclick="closeModal()" style="flex:1;">Cancel</button>
                    <button class="btn-confirm" id="confirmBtn" onclick="submitReview()" style="flex:2;">
                        <i class="fa-solid fa-check" id="confirmIcon"></i>
                        <span id="confirmLabel">Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- ── Inline styles for this page ──────────────────────────────────────── -->
<style>
.badge-status {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px; font-size:0.73rem; font-weight:600;
}
.badge-pending  { background:rgba(201,151,90,0.13);color:#b87a2a;border:1px solid rgba(201,151,90,0.3); }
.badge-approved { background:rgba(46,125,82,0.1);color:#2e7d52;border:1px solid rgba(46,125,82,0.25); }
.badge-rejected { background:rgba(194,99,122,0.1);color:var(--rose-deep);border:1px solid rgba(194,99,122,0.25); }

.act-btn {
    width:32px; height:32px; border:none; border-radius:8px; cursor:pointer;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:0.8rem; transition:0.2s;
}
.approve-btn { background:rgba(46,125,82,0.12); color:#2e7d52; }
.approve-btn:hover { background:rgba(46,125,82,0.22); }
.reject-btn  { background:rgba(194,99,122,0.12); color:var(--rose-deep); }
.reject-btn:hover  { background:rgba(194,99,122,0.22); }
.pending-btn { background:rgba(201,151,90,0.12); color:#b87a2a; }
.pending-btn:hover { background:rgba(201,151,90,0.22); }

.filter-btn {
    padding:6px 14px; border:1.5px solid var(--border); border-radius:20px;
    background:transparent; color:var(--text-secondary); font-size:0.78rem;
    cursor:pointer; transition:0.2s; font-family:'DM Sans',sans-serif;
}
.filter-btn:hover, .filter-btn.active {
    background:var(--rose-deep); color:#fff; border-color:var(--rose-deep);
}

.modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,0.45);
    display:flex; align-items:center; justify-content:center;
    z-index:9999; backdrop-filter:blur(4px);
}
.modal-box {
    background:var(--modal-bg,#fff); border-radius:20px; width:94%;
    border:1px solid var(--border-hi);
    box-shadow:0 20px 60px rgba(0,0,0,0.3);
    animation:fadeUp 0.3s both;
}
.modal-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 24px; border-bottom:1px solid var(--border);
}
.modal-head h3 { font-size:1.1rem; font-weight:600; color:var(--text-primary); }
.modal-close {
    width:32px; height:32px; border:none; background:var(--bg-card-hi);
    border-radius:8px; cursor:pointer; color:var(--text-secondary);
    display:flex; align-items:center; justify-content:center;
}
.modal-body { padding:24px; }

.btn-cancel {
    padding:11px; border:1.5px solid var(--border-hi); background:transparent;
    border-radius:10px; color:var(--text-secondary); cursor:pointer;
    font-family:'DM Sans',sans-serif; font-size:0.88rem; font-weight:500;
    transition:0.2s;
}
.btn-cancel:hover { background:var(--bg-card-hi); }
.btn-confirm {
    padding:11px; border:none; border-radius:10px; cursor:pointer;
    font-family:'DM Sans',sans-serif; font-size:0.88rem; font-weight:600;
    background:linear-gradient(135deg,var(--rose-deep),#b85070);
    color:#fff; display:flex; align-items:center; justify-content:center; gap:8px;
    transition:0.2s;
}
.btn-confirm:hover { opacity:0.9; }
.btn-confirm.approve-mode { background:linear-gradient(135deg,#2e7d52,#3a9a66); }
.btn-confirm.reject-mode  { background:linear-gradient(135deg,#c2637a,#a04060); }
.btn-confirm.pending-mode { background:linear-gradient(135deg,#b87a2a,#d4843a); }
</style>

<!-- ── Scripts ───────────────────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script>
// ── DataTable init ────────────────────────────────────────────────────────
$(document).ready(function(){
    var tbl = $('#leaveTable').DataTable({
        pageLength: 10,
        order: [[0,'desc']],
    });

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'leaveTable') return true;

        let selectedStatus = $('#filterBtns .filter-btn.active').data('status');
        if (!selectedStatus || selectedStatus === 'all') return true;

        let rowStatus = $(tbl.row(dataIndex).node()).attr('data-status');
        return rowStatus === selectedStatus;
    })

    // ── Status filter buttons ─────────────────────────────────────────────
    $('#filterBtns .filter-btn').on('click', function(){
        $('#filterBtns .filter-btn').removeClass('active');
        $(this).addClass('active');
        tbl.draw();
    });
});

// ── Modal ─────────────────────────────────────────────────────────────────
function openReview(appId, newStatus) {
    document.getElementById('modalAppId').value  = appId;
    document.getElementById('modalStatus').value = newStatus;
    document.getElementById('modalRemarks').value = '';

    const titles = { Approved:'Approve Leave', Rejected:'Reject Leave', Pending:'Reset to Pending' };
    const infos  = {
        Approved: '✅ Approving this leave will deduct the days from the employee\'s balance.',
        Rejected: '❌ This leave will be marked as Rejected. The employee will be notified.',
        Pending:  '🔄 This will reset the leave status back to Pending for re-review.'
    };
    const modes = { Approved:'approve-mode', Rejected:'reject-mode', Pending:'pending-mode' };
    const icons = { Approved:'fa-check', Rejected:'fa-xmark', Pending:'fa-rotate-left' };

    document.getElementById('modalTitle').textContent     = titles[newStatus];
    document.getElementById('modalInfo').textContent      = infos[newStatus];
    document.getElementById('confirmLabel').textContent   = titles[newStatus];
    document.getElementById('confirmIcon').className      = 'fa-solid ' + icons[newStatus];

    const btn = document.getElementById('confirmBtn');
    btn.className = 'btn-confirm ' + modes[newStatus];

    document.getElementById('reviewModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

function submitReview() {
    const appId   = document.getElementById('modalAppId').value;
    const status  = document.getElementById('modalStatus').value;
    const remarks = document.getElementById('modalRemarks').value;

    const btn = document.getElementById('confirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing…';

    const fd = new FormData();
    fd.append('action', 'review_application');
    fd.append('application_id', appId);
    fd.append('status', status);
    fd.append('remarks', remarks);

    fetch('leave_approval_db.php', { method:'POST', body:fd })
        .then(r => r.text())
        .then(res => {
            closeModal();
            console.log(res);
            
            if (res.trim() === 'success') {
                Swal.fire({
                    icon:'success', title:'Done!',
                    text:'Leave status updated successfully.',
                    confirmButtonColor:'#c2637a', timer:1800, showConfirmButton:false
                }).then(()=> location.reload());
            } else {
                Swal.fire({ icon:'error', title:'Error', text: res, confirmButtonColor:'#c2637a' });
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm';
            }
        })
        .catch(()=>{
            Swal.fire({ icon:'error', title:'Network Error', text:'Could not connect to server.', confirmButtonColor:'#c2637a'});
            btn.disabled = false;
        });
}

// Close modal on overlay click
document.getElementById('reviewModal').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});
</script>

<script>
    // Highlight leave nav item in sidebar
    document.addEventListener('DOMContentLoaded', function(){
        const leaveLink = document.querySelector('#leave, [href*="leave_approval"]');
        if (leaveLink) leaveLink.classList.add('active');
    });
</script>

<?php include '../includes/footer.php'; ?>