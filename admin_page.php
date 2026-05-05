<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: user_page.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// ── Handle Delete ────────────────────────────────────────────────────────────
if (isset($_POST['delete_student'])) {
    $id = intval($_POST['student_id']);
    $conn->query("DELETE FROM cgpa_results WHERE user_id = $id");
    $conn->query("DELETE FROM users WHERE id = $id");
    header("Location: admin_page.php?msg=deleted");
    exit();
}

// ── Handle Edit ──────────────────────────────────────────────────────────────
if (isset($_POST['edit_student'])) {
    $id         = intval($_POST['student_id']);
    $name       = $conn->real_escape_string($_POST['name']);
    $matric_no  = $conn->real_escape_string($_POST['matric_no']);
    $department = $conn->real_escape_string($_POST['department']);
    $conn->query("UPDATE users SET name='$name', matric_no='$matric_no', department='$department' WHERE id=$id");
    header("Location: admin_page.php?msg=updated");
    exit();
}

// ── Stats ────────────────────────────────────────────────────────────────────
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$total_results  = $conn->query("SELECT COUNT(*) as c FROM cgpa_results")->fetch_assoc()['c'];
$avg_cgpa_row   = $conn->query("SELECT AVG(cgpa) as avg FROM cgpa_results")->fetch_assoc();
$avg_cgpa       = $avg_cgpa_row['avg'] ? round($avg_cgpa_row['avg'], 2) : 'N/A';

$dept_res = $conn->query("SELECT COUNT(*) as c, department FROM users WHERE role != 'admin' GROUP BY department ORDER BY c DESC LIMIT 1");
$top_dept = $dept_res->num_rows > 0 ? $dept_res->fetch_assoc()['department'] : 'N/A';

// ── Degree class breakdown ───────────────────────────────────────────────────
$degree_res = $conn->query("SELECT degree_class, COUNT(*) as c FROM cgpa_results GROUP BY degree_class ORDER BY c DESC");
$degree_breakdown = [];
while ($row = $degree_res->fetch_assoc()) {
    $degree_breakdown[] = $row;
}

// ── All Students ─────────────────────────────────────────────────────────────
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_dept = isset($_GET['dept']) ? $conn->real_escape_string($_GET['dept']) : '';

$where = "WHERE u.role != 'admin'";
if ($search)      $where .= " AND (u.name LIKE '%$search%' OR u.matric_no LIKE '%$search%')";
if ($filter_dept) $where .= " AND u.department = '$filter_dept'";

$students_res = $conn->query("
    SELECT u.id, u.name, u.matric_no, u.department, u.email,
           r.cgpa, r.degree_class, r.calculated_at
    FROM users u
    LEFT JOIN (
        SELECT user_id, cgpa, degree_class, calculated_at
        FROM cgpa_results
        WHERE id IN (SELECT MAX(id) FROM cgpa_results GROUP BY user_id)
    ) r ON u.id = r.user_id
    $where
    ORDER BY u.name ASC
");

// ── Top Students ─────────────────────────────────────────────────────────────
$top_res = $conn->query("
    SELECT u.name, u.matric_no, u.department, r.cgpa, r.degree_class
    FROM cgpa_results r
    JOIN users u ON r.user_id = u.id
    WHERE r.id IN (SELECT MAX(id) FROM cgpa_results GROUP BY user_id)
    ORDER BY r.cgpa DESC LIMIT 5
");

// ── Department list for filter ────────────────────────────────────────────────
$depts_res = $conn->query("SELECT DISTINCT department FROM users WHERE role != 'admin' ORDER BY department");
$departments = [];
while ($d = $depts_res->fetch_assoc()) $departments[] = $d['department'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4ff;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            background: #1e40af;
            color: white;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .topbar-left h2 { font-size: 20px; margin-bottom: 2px; }
        .topbar-left p  { font-size: 13px; opacity: .8; }
        .topbar-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .topbar-right span { font-size: 13px; opacity: .85; }
        .logout-btn {
            background: white; color: #1e40af; border: none;
            padding: 8px 20px; border-radius: 8px; cursor: pointer;
            font-weight: bold; font-size: 13px; transition: background .2s;
        }
        .logout-btn:hover { background: #dbeafe; }
        .download-report-btn {
            background: #16a34a; color: white;
            padding: 8px 18px; border-radius: 8px;
            font-weight: bold; font-size: 13px;
            text-decoration: none; transition: background .2s;
        }
        .download-report-btn:hover { background: #15803d; }

        /* ── Main ── */
        .main { max-width: 1100px; margin: 30px auto; padding: 20px 20px 40px; }

        /* ── Alert ── */
        .alert {
            padding: 12px 18px; border-radius: 8px; margin-bottom: 20px;
            font-size: 14px; font-weight: bold;
        }
        .alert-green { background: #dcfce7; color: #16a34a; }

        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            border-left: 4px solid #1e40af;
        }
        .stat-card.green  { border-color: #16a34a; }
        .stat-card.orange { border-color: #f59e0b; }
        .stat-card.purple { border-color: #7c3aed; }
        .stat-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #1e40af; }
        .stat-card.green  .stat-value { color: #16a34a; }
        .stat-card.orange .stat-value { color: #f59e0b; }
        .stat-card.purple .stat-value { color: #7c3aed; }

        /* ── Cards ── */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #dbeafe;
        }
        .card-header h3 { font-size: 15px; color: #1e40af; }

        /* ── Search & Filter ── */
        .filters {
            display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;
        }
        .filters input, .filters select {
            padding: 9px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; outline: none; transition: border .2s;
        }
        .filters input:focus, .filters select:focus { border-color: #1e40af; }
        .filters input { flex: 1; min-width: 200px; }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead th {
            background: #1e40af; color: white; padding: 12px 14px;
            text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em;
        }
        tbody tr { border-bottom: 1px solid #f0f4ff; transition: background .15s; }
        tbody tr:hover { background: #f8faff; }
        tbody td { padding: 12px 14px; color: #333; vertical-align: middle; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: bold;
        }
        .badge-gold   { background: #fef9c3; color: #854d0e; }
        .badge-silver { background: #f1f5f9; color: #475569; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-green  { background: #dcfce7; color: #16a34a; }
        .badge-red    { background: #fee2e2; color: #dc2626; }
        .badge-gray   { background: #f3f4f6; color: #6b7280; }

        /* ── Action Buttons ── */
        .btn { padding: 7px 14px; border: none; border-radius: 7px; cursor: pointer; font-size: 13px; font-weight: bold; transition: all .2s; }
        .btn-edit   { background: #dbeafe; color: #1e40af; }
        .btn-edit:hover   { background: #bfdbfe; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fecaca; }
        .btn-blue   { background: #1e40af; color: white; }
        .btn-blue:hover { background: #1e3a8a; }

        /* ── Top Students ── */
        .top-student {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 0; border-bottom: 1px solid #f0f4ff;
        }
        .top-student:last-child { border-bottom: none; }
        .rank {
            width: 32px; height: 32px; border-radius: 50%;
            background: #1e40af; color: white; font-weight: bold;
            font-size: 14px; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .rank.gold   { background: #f59e0b; }
        .rank.silver { background: #94a3b8; }
        .rank.bronze { background: #b45309; }
        .top-name    { font-weight: bold; font-size: 14px; color: #1e3a8a; }
        .top-dept    { font-size: 12px; color: #888; }
        .top-cgpa    { margin-left: auto; font-size: 20px; font-weight: bold; color: #1e40af; }

        /* ── Degree Breakdown ── */
        .degree-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .degree-label { font-size: 13px; width: 160px; color: #444; }
        .degree-bar-wrap { flex: 1; background: #f0f4ff; border-radius: 20px; height: 10px; overflow: hidden; }
        .degree-bar { height: 10px; border-radius: 20px; background: #1e40af; transition: width .6s; }
        .degree-count { font-size: 13px; font-weight: bold; color: #1e40af; width: 30px; text-align: right; }

        /* ── Two Column Layout ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* ── Modal ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.4); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white; border-radius: 14px; padding: 28px;
            width: 440px; max-width: 95vw;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
        }
        .modal h3 { font-size: 16px; color: #1e40af; margin-bottom: 18px; }
        .modal label { font-size: 12px; font-weight: bold; color: #555; display: block; margin-bottom: 5px; text-transform: uppercase; }
        .modal input {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd;
            border-radius: 8px; font-size: 14px; margin-bottom: 14px; outline: none;
        }
        .modal input:focus { border-color: #1e40af; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px; }
        .btn-cancel { background: #f3f4f6; color: #444; }
        .btn-cancel:hover { background: #e5e7eb; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .two-col    { grid-template-columns: 1fr; }
            .topbar     { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-left">
        <h2>Admin Dashboard</h2>
        <p>CGPA Calculator — Management Panel</p>
    </div>
    <div class="topbar-right">
        <span><?= htmlspecialchars($admin_name) ?> &nbsp;|&nbsp; Administrator</span>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="admin_report.php" class="download-report-btn">⬇ Download Full Report</a>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>
</div>

<div class="main">

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-green">
            <?= $_GET['msg'] === 'deleted' ? '✅ Student deleted successfully.' : '✅ Student updated successfully.' ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['report_error'])): ?>
        <div class="alert" style="background:#fee2e2; color:#dc2626;">
            ❌ Could not generate report. Make sure the Python engine (cgpa_engine.py) is running on port 5000.
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Students</div>
            <div class="stat-value"><?= $total_students ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Results Calculated</div>
            <div class="stat-value"><?= $total_results ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Average CGPA</div>
            <div class="stat-value"><?= $avg_cgpa ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Top Department</div>
            <div class="stat-value" style="font-size:16px; margin-top:6px;"><?= htmlspecialchars($top_dept) ?></div>
        </div>
    </div>

    <!-- Top Students & Degree Breakdown -->
    <div class="two-col">

        <!-- Top 5 Students -->
        <div class="card">
            <div class="card-header">
                <h3>🏆 Top 5 Students by CGPA</h3>
            </div>
            <?php
            $rank = 1;
            $rank_classes = ['gold', 'silver', 'bronze', '', ''];
            while ($s = $top_res->fetch_assoc()):
                $rc = $rank_classes[$rank - 1] ?? '';
            ?>
            <div class="top-student">
                <div class="rank <?= $rc ?>"><?= $rank ?></div>
                <div>
                    <div class="top-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="top-dept"><?= htmlspecialchars($s['department']) ?> &nbsp;|&nbsp; <?= htmlspecialchars($s['matric_no']) ?></div>
                </div>
                <div class="top-cgpa"><?= number_format($s['cgpa'], 2) ?></div>
            </div>
            <?php $rank++; endwhile; ?>
            <?php if ($rank === 1): ?>
                <p style="color:#888; font-size:13px; text-align:center; padding:20px 0;">No results yet.</p>
            <?php endif; ?>
        </div>

        <!-- Degree Class Breakdown -->
        <div class="card">
            <div class="card-header">
                <h3>📊 Degree Class Breakdown</h3>
            </div>
            <?php
            $max_count = 1;
            foreach ($degree_breakdown as $d) if ($d['c'] > $max_count) $max_count = $d['c'];
            foreach ($degree_breakdown as $d):
                $pct = round(($d['c'] / $max_count) * 100);
            ?>
            <div class="degree-row">
                <div class="degree-label"><?= htmlspecialchars($d['degree_class']) ?></div>
                <div class="degree-bar-wrap">
                    <div class="degree-bar" style="width:<?= $pct ?>%"></div>
                </div>
                <div class="degree-count"><?= $d['c'] ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($degree_breakdown)): ?>
                <p style="color:#888; font-size:13px; text-align:center; padding:20px 0;">No results yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student Management -->
    <div class="card">
        <div class="card-header">
            <h3>👥 Student Management</h3>
            <span style="font-size:13px; color:#888;"><?= $total_students ?> registered student(s)</span>
        </div>

        <!-- Filters -->
        <div class="filters">
            <input type="text" id="search-input" placeholder="🔍 Search by name or matric number..." value="<?= htmlspecialchars($search) ?>">
            <select id="dept-filter">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $filter_dept === $d ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-blue" onclick="applyFilter()">Filter</button>
            <button class="btn" style="background:#f3f4f6;color:#444;" onclick="clearFilter()">Clear</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Matric No</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Latest CGPA</th>
                        <th>Degree Class</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                while ($s = $students_res->fetch_assoc()):
                    $badge = 'badge-gray';
                    $dc = $s['degree_class'] ?? '';
                    if ($dc === '1st Class')         $badge = 'badge-gold';
                    elseif ($dc === '2nd Class Upper') $badge = 'badge-silver';
                    elseif ($dc === '2nd Class Lower') $badge = 'badge-blue';
                    elseif ($dc === '3rd Class')       $badge = 'badge-green';
                    elseif ($dc === 'Pass')            $badge = 'badge-red';
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><?= htmlspecialchars($s['matric_no']) ?></td>
                    <td><?= htmlspecialchars($s['department']) ?></td>
                    <td style="font-size:13px; color:#888;"><?= htmlspecialchars($s['email']) ?></td>
                    <td>
                        <?php if ($s['cgpa']): ?>
                            <strong><?= number_format($s['cgpa'], 2) ?></strong>
                        <?php else: ?>
                            <span style="color:#bbb; font-size:12px;">No result yet</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($dc): ?>
                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($dc) ?></span>
                        <?php else: ?>
                            <span style="color:#bbb; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-edit" onclick="openEdit(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>', '<?= addslashes($s['matric_no']) ?>', '<?= addslashes($s['department']) ?>')">Edit</button>
                        <button class="btn btn-delete" onclick="confirmDelete(<?= $s['id'] ?>, '<?= addslashes($s['name']) ?>')">Delete</button>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($i === 1): ?>
                <tr><td colspan="8" style="text-align:center; color:#888; padding:30px;">No students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main -->

<!-- Edit Modal -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal">
        <h3>✏️ Edit Student</h3>
        <form method="POST">
            <input type="hidden" name="student_id" id="edit-id">
            <label>Full Name</label>
            <input type="text" name="name" id="edit-name" required>
            <label>Matric Number</label>
            <input type="text" name="matric_no" id="edit-matric" required>
            <label>Department</label>
            <input type="text" name="department" id="edit-dept" required>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="edit_student" class="btn btn-blue">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form method="POST" id="delete-form">
    <input type="hidden" name="student_id" id="delete-id">
    <input type="hidden" name="delete_student" value="1">
</form>

<script>
function openEdit(id, name, matric, dept) {
    document.getElementById('edit-id').value     = id;
    document.getElementById('edit-name').value   = name;
    document.getElementById('edit-matric').value = matric;
    document.getElementById('edit-dept').value   = dept;
    document.getElementById('edit-modal').classList.add('active');
}
function closeModal() {
    document.getElementById('edit-modal').classList.remove('active');
}
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This will also remove their CGPA results.`)) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}
function applyFilter() {
    const s = document.getElementById('search-input').value;
    const d = document.getElementById('dept-filter').value;
    window.location.href = `admin_page.php?search=${encodeURIComponent(s)}&dept=${encodeURIComponent(d)}`;
}
function clearFilter() {
    window.location.href = 'admin_page.php';
}
// Close modal on overlay click
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>