<?php
// api/sales_report.php
header('Content-Type: application/json');
include 'db_connect.php'; //

$sql = "SELECT 
            date_aggregated AS date,
            total_sales 
        FROM bi_summary 
        ORDER BY date_aggregated ASC";

$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}

echo json_encode($data);
$conn->close();
?>
