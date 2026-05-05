<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$user_id      = intval($data['user_id']);
$matric_no    = $conn->real_escape_string($data['matric_no']);
$name         = $conn->real_escape_string($data['name']);
$department   = $conn->real_escape_string($data['department']);
$cgpa         = floatval($data['cgpa']);
$degree_class = $conn->real_escape_string($data['degree_class']);
$total_units  = intval($data['total_units']);
$total_points = intval($data['total_points']);
$semesters    = intval($data['semesters']);

$sql = "INSERT INTO cgpa_results (user_id, matric_no, name, department, cgpa, degree_class, total_units, total_points, semesters)
        VALUES ('$user_id', '$matric_no', '$name', '$department', '$cgpa', '$degree_class', '$total_units', '$total_points', '$semesters')";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>