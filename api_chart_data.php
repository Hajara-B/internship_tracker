<?php
header('Content-Type: application/json');
require 'db_connect.php';

$departments_sql = "SELECT branch, COUNT(id) as count FROM applications GROUP BY branch ORDER BY count DESC";
$departments_result = $conn->query($departments_sql);
$departments_data = ['labels' => [], 'data' => []];
while($row = $departments_result->fetch_assoc()) {
    $departments_data['labels'][] = $row['branch'];
    $departments_data['data'][] = $row['count'];
}

$companies_sql = "SELECT company_name, COUNT(id) as count FROM applications GROUP BY company_name ORDER BY count DESC LIMIT 10";
$companies_result = $conn->query($companies_sql);
$companies_data = ['labels' => [], 'data' => []];
while($row = $companies_result->fetch_assoc()) {
    $companies_data['labels'][] = $row['company_name'];
    $companies_data['data'][] = $row['count'];
}

echo json_encode([
    'departments' => $departments_data,
    'companies' => $companies_data
]);

$conn->close();
?>