<?php
session_start();
require_once 'config.php';

// Only admins can access this
if (!isset($_SESSION['email']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: index.php");
    exit();
}

// ── Fetch Stats ──────────────────────────────────────────────────────────────
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$total_results  = $conn->query("SELECT COUNT(*) as c FROM cgpa_results")->fetch_assoc()['c'];
$avg_cgpa_row   = $conn->query("SELECT ROUND(AVG(cgpa), 2) as avg FROM cgpa_results")->fetch_assoc();
$avg_cgpa       = $avg_cgpa_row['avg'] ?? 'N/A';
$dept_res       = $conn->query("SELECT department, COUNT(*) as c FROM users WHERE role != 'admin' GROUP BY department ORDER BY c DESC LIMIT 1");
$top_dept       = $dept_res->num_rows > 0 ? $dept_res->fetch_assoc()['department'] : 'N/A';

// ── Fetch All Students with Latest CGPA ──────────────────────────────────────
$students_res = $conn->query("
    SELECT u.name, u.matric_no, u.department, u.email,
           r.cgpa, r.degree_class
    FROM users u
    LEFT JOIN (
        SELECT user_id, cgpa, degree_class
        FROM cgpa_results
        WHERE id IN (SELECT MAX(id) FROM cgpa_results GROUP BY user_id)
    ) r ON u.id = r.user_id
    WHERE u.role != 'admin'
    ORDER BY u.name ASC
");

$students = [];
while ($s = $students_res->fetch_assoc()) {
    $students[] = $s;
}

// ── Build Payload ────────────────────────────────────────────────────────────
$payload = [
    'stats' => [
        'total_students' => $total_students,
        'total_results'  => $total_results,
        'avg_cgpa'       => $avg_cgpa,
        'top_dept'       => $top_dept,
    ],
    'students' => $students,
];

// ── Send to Flask ────────────────────────────────────────────────────────────
$ch = curl_init('http://127.0.0.1:5000/admin_report');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error     = curl_error($ch);
curl_close($ch);

if ($error || $http_code !== 200) {
    header("Location: admin_page.php?report_error=1");
    exit();
}

// ── Stream PDF to browser ────────────────────────────────────────────────────
$filename = 'Admin_CGPA_Report_' . date('Y-m-d') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($response));
echo $response;
exit();
?>