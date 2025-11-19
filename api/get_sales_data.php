<?php
require_once '../api/db_connect.php';

// Get total sales
$totalSalesQuery = "SELECT SUM(total) AS total_sales, COUNT(*) AS transactions FROM sales";
$result = $conn->query($totalSalesQuery);
$row = $result->fetch_assoc();
$total_sales = $row['total_sales'] ?? 0;
$transactions = $row['transactions'] ?? 0;

// Assume 30% gross profit margin (you can replace this logic with actual cost data)
$gross_profit = $total_sales * 0.30;

// Weekly sales trend
$trendQuery = "
  SELECT DATE_FORMAT(date, '%b %d') AS week_label, SUM(total) AS week_total
  FROM sales
  WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY week_label
  ORDER BY date ASC
";
$trendResult = $conn->query($trendQuery);
$labels = [];
$sales = [];
while ($t = $trendResult->fetch_assoc()) {
  $labels[] = $t['week_label'];
  $sales[] = $t['week_total'];
}

// Return JSON
echo json_encode([
  "total_sales" => $total_sales,
  "gross_profit" => $gross_profit,
  "transactions" => $transactions,
  "labels" => $labels,
  "sales" => $sales
]);
?>
