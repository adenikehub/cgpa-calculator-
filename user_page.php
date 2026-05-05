
<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}
if ($_SESSION['role'] === 'Admin') {
    header("Location: admin_page.php");
    exit();
}

$name       = isset($_SESSION['name'])       ? $_SESSION['name']       : '';
$matric     = isset($_SESSION['matric_no'])  ? $_SESSION['matric_no']  : '';
$department = isset($_SESSION['department']) ? $_SESSION['department'] : '';
$user_id    = isset($_SESSION['user_id'])    ? $_SESSION['user_id']    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGPA Calculator</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            min-height: 100vh;
        }

        /* ── Top Bar ── */
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
            background: white;
            color: #1e40af;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: background .2s;
        }
        .logout-btn:hover { background: #dbeafe; }

        /* ── Main Layout ── */
        .main {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px 20px 40px;
        }

        /* ── Cards ── */
        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }
        .card h3 {
            font-size: 15px;
            color: #1e40af;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dbeafe;
        }

        /* ── Form Elements ── */
        label {
            font-size: 12px;
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 14px;
            transition: border .2s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #1e40af;
        }
        input[readonly] {
            background: #f5f7ff;
            color: #555;
            cursor: default;
        }

        /* ── Grid Helpers ── */
        .row2 { display: grid; grid-template-columns: 1fr 1fr;     gap: 14px; }
        .row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

        /* ── Semester Blocks ── */
        .sem-block {
            border: 1.5px solid #dbeafe;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
            background: #f8faff;
        }
        .sem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .sem-title { font-weight: bold; color: #1e40af; font-size: 15px; }

        .course-header {
            display: grid;
            grid-template-columns: 1.6fr 2.2fr 0.7fr 0.7fr 36px;
            gap: 8px;
            margin-bottom: 6px;
        }
        .course-header span {
            font-size: 11px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
        }
        .course-row {
            display: grid;
            grid-template-columns: 1.6fr 2.2fr 0.7fr 0.7fr 36px;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }
        .course-row input { margin-bottom: 0; }

        /* ── Buttons ── */
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all .2s;
        }
        .btn-blue   { background: #1e40af; color: white; }
        .btn-blue:hover   { background: #1e3a8a; }
        .btn-green  { background: #16a34a; color: white; }
        .btn-green:hover  { background: #15803d; }
        .btn-outline {
            background: white;
            color: #1e40af;
            border: 1.5px solid #1e40af;
        }
        .btn-outline:hover { background: #eff6ff; }
        .btn-sm   { padding: 7px 14px; font-size: 13px; }
        .btn-del  {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 0;
            width: 32px; height: 32px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-del:hover { background: #fecaca; }

        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }

        /* ── Result Box ── */
        .result-box {
            display: none;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border-radius: 12px;
            padding: 28px;
            text-align: center;
            color: white;
            margin-top: 16px;
        }
        .cgpa-big   { font-size: 56px; font-weight: bold; line-height: 1; }
        .cgpa-label { font-size: 14px; opacity: .8; margin-bottom: 6px; }
        .degree     { font-size: 22px; font-weight: bold; margin: 8px 0 20px; }
        .stats {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .stat {
            background: rgba(255,255,255,.15);
            border-radius: 10px;
            padding: 12px 24px;
        }
        .stat-label { font-size: 12px; opacity: .8; margin-bottom: 4px; }
        .stat-val   { font-size: 20px; font-weight: bold; }

        /* ── Messages ── */
        .error-msg {
            color: #dc2626;
            font-size: 13px;
            margin-top: 10px;
            padding: 10px 14px;
            background: #fee2e2;
            border-radius: 8px;
            display: none;
        }
        .loading {
            color: #1e40af;
            font-size: 14px;
            margin-top: 10px;
            display: none;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <h2>CGPA Calculator</h2>
        <p>Student Academic Portal</p>
    </div>
    <div class="topbar-right">
        <span><?= htmlspecialchars($name) ?> &nbsp;|&nbsp; <?= htmlspecialchars($department) ?></span>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
    </div>
</div>

<div class="main">

    <!-- Student Info -->
    <div class="card">
        <h3>Student Information</h3>
        <div class="row2">
            <div>
                <label>Full Name</label>
                <input type="text" id="s_name" value="<?= htmlspecialchars($name) ?>" readonly>
            </div>
            <div>
                <label>Matric Number</label>
                <input type="text" id="s_matric" value="<?= htmlspecialchars($matric) ?>" readonly>
            </div>
        </div>
        <div class="row3">
            <div>
                <label>Department</label>
                <input type="text" id="s_dept" value="<?= htmlspecialchars($department) ?>" readonly>
            </div>
            <div>
                <label>Programme</label>
                <input type="text" id="s_prog" placeholder="e.g. B.Sc Computer Science">
            </div>
            <div>
                <label>Faculty</label>
                <input type="text" id="s_faculty" placeholder="e.g. Science">
            </div>
        </div>
        <div style="max-width:180px;">
            <label>Level</label>
            <select id="s_level">
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="300">300</option>
                <option value="400" selected>400</option>
                <option value="500">500</option>
            </select>
        </div>
    </div>

    <!-- Course Entry -->
    <div class="card">
        <h3>Course Entry</h3>
        <div id="semesters-container"></div>
        <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="addSemester()">+ Add Semester</button>
        </div>
    </div>

    <!-- Calculate & Result -->
    <div class="card">
        <h3>Result</h3>
        <div class="actions">
            <button class="btn btn-blue" onclick="calculate()">Calculate CGPA</button>
            <button class="btn btn-green" id="download-btn" onclick="downloadPDF()" style="display:none;">
                Download PDF Report
            </button>
        </div>
        <div class="loading"  id="loading">Calculating... please wait</div>
        <div class="error-msg" id="error-msg"></div>

        <div class="result-box" id="result-box">
            <div class="cgpa-label">Your CGPA</div>
            <div class="cgpa-big"  id="cgpa-display">0.00</div>
            <div class="degree"    id="degree-display"></div>
            <div class="stats">
                <div class="stat">
                    <div class="stat-label">Total Units</div>
                    <div class="stat-val" id="units-display">0</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Total Points</div>
                    <div class="stat-val" id="points-display">0</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Semesters</div>
                    <div class="stat-val" id="sems-display">0</div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /main -->

<script>
const FLASK   = 'http://127.0.0.1:5000';
const USER_ID = <?= intval($user_id) ?>;
let semCount    = 0;
let lastPayload = null;

function addSemester() {
    semCount++;
    const id  = semCount;
    const div = document.createElement('div');
    div.className = 'sem-block';
    div.id = `sem-${id}`;
    div.innerHTML = `
        <div class="sem-header">
            <div class="sem-title">Semester ${id}</div>
            ${id > 1 ? `<button class="btn-del" onclick="document.getElementById('sem-${id}').remove()">✕</button>` : ''}
        </div>
        <div class="course-header">
            <span>Course Code</span>
            <span>Course Title</span>
            <span>Units</span>
            <span>Score</span>
            <span></span>
        </div>
        <div id="courses-${id}"></div>
        <button class="btn btn-outline btn-sm" style="margin-top:8px" onclick="addCourse(${id})">+ Add Course</button>
    `;
    document.getElementById('semesters-container').appendChild(div);
    addCourse(id);
    addCourse(id);
}

function addCourse(semId) {
    const container = document.getElementById(`courses-${semId}`);
    const row = document.createElement('div');
    row.className = 'course-row';
    row.innerHTML = `
        <input type="text"   placeholder="e.g. CSC 401">
        <input type="text"   placeholder="Course Title">
        <input type="number" placeholder="3" min="1" max="6" value="3">
        <input type="number" placeholder="Score" min="0" max="100">
        <button class="btn-del" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(row);
}

function showError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 6000);
}

function collectData() {
    const semesters = [];
    document.querySelectorAll('.sem-block').forEach(semDiv => {
        const courses = [];
        semDiv.querySelectorAll('.course-row').forEach(row => {
            const inputs = row.querySelectorAll('input');
            const code   = inputs[0].value.trim();
            const title  = inputs[1].value.trim();
            const unit   = inputs[2].value.trim();
            const score  = inputs[3].value.trim();
            if (code && title && unit && score) {
                courses.push({ code, title, unit, score });
            }
        });
        if (courses.length > 0) semesters.push({ courses });
    });
    return semesters;
}

async function calculate() {
    const semesters = collectData();
    if (semesters.length === 0) {
        showError('Please fill in at least one semester with complete course details.');
        return;
    }

    lastPayload = {
        student: {
            name:       document.getElementById('s_name').value,
            matric_no:  document.getElementById('s_matric').value,
            department: document.getElementById('s_dept').value,
            programme:  document.getElementById('s_prog').value,
            faculty:    document.getElementById('s_faculty').value,
            level:      document.getElementById('s_level').value,
        },
        semesters
    };

    document.getElementById('loading').style.display    = 'block';
    document.getElementById('result-box').style.display = 'none';
    document.getElementById('download-btn').style.display = 'none';

    try {
        const res  = await fetch(`${FLASK}/calculate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(lastPayload)
        });
        const data = await res.json();
        if (data.error) { showError('Error: ' + data.error); return; }

        document.getElementById('cgpa-display').textContent   = data.cgpa.toFixed(2);
        document.getElementById('degree-display').textContent = data.degree_class;
        document.getElementById('units-display').textContent  = data.total_units;
        document.getElementById('points-display').textContent = data.total_points;
        document.getElementById('sems-display').textContent   = data.semesters.length;
        document.getElementById('result-box').style.display   = 'block';
        document.getElementById('download-btn').style.display = 'inline-block';

        // ── Save result to database ──
        fetch('save_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id:      USER_ID,
                matric_no:    lastPayload.student.matric_no,
                name:         lastPayload.student.name,
                department:   lastPayload.student.department,
                cgpa:         data.cgpa,
                degree_class: data.degree_class,
                total_courseunits:  data.total_units,
                total_gradepoints: data.total_points,
                semesters:    data.semesters.length,
            })
        });
    } catch(e) {
        showError('Cannot connect to the Python engine. Make sure cgpa_engine.py is running on port 5000.');
    } finally {
        document.getElementById('loading').style.display = 'none';
    }
}

async function downloadPDF() {
    if (!lastPayload) { showError('Please calculate first.'); return; }
    const btn = document.getElementById('download-btn');
    btn.textContent = 'Generating PDF...';
    btn.disabled    = true;
    try {
        const res  = await fetch(`${FLASK}/download_pdf`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(lastPayload)
        });
        const blob = await res.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `CGPA_Report_${lastPayload.student.matric_no.replace(/\//g,'_')}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
    } catch(e) {
        showError('PDF download failed. Make sure the Python engine is running.');
    } finally {
        btn.textContent = 'Download PDF Report';
        btn.disabled    = false;
    }
}

// Start with 2 semesters
addSemester();
addSemester();
</script>
</body>
</html>