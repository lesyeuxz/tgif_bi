<?php
header('Content-Type: application/json');
require_once "../config/database.php";

/* =============================
   1. KPI — TOTAL SALES
============================= */
$totalSalesQuery = mysqli_query($conn, "
  SELECT SUM(total_amount) AS total_sales 
  FROM sales
");
$totalSalesRow = mysqli_fetch_assoc($totalSalesQuery);
$total_sales = $totalSalesRow['total_sales'] ?? 0;


/* =============================
   2. KPI — GROSS PROFIT
   (example: 25% margin)
============================= */
$gross_profit = $total_sales * 0.25;


/* =============================
   3. KPI — LOW STOCK ITEMS
============================= */
$lowStockQuery = mysqli_query($conn, "
  SELECT COUNT(*) AS low_stock 
  FROM inventory
  WHERE quantity <= reorder_level
");
$lowStockRow = mysqli_fetch_assoc($lowStockQuery);
$low_stock = $lowStockRow['low_stock'] ?? 0;


/* =============================
   4. SALES TREND CHART
============================= */
$salesLabels = [];
$salesData   = [];

$salesTrendQuery = mysqli_query($conn, "
  SELECT DATE(created_at) as date, SUM(total_amount) as total
  FROM sales
  GROUP BY DATE(created_at)
  ORDER BY DATE(created_at) DESC
  LIMIT 7
");

while($row = mysqli_fetch_assoc($salesTrendQuery)){
  $salesLabels[] = $row['date'];
  $salesData[]   = $row['total'];
}


/* =============================
   5. INVENTORY MOVEMENT CHART
============================= */
$stockLabels = [];
$stockData   = [];

$stockQuery = mysqli_query($conn, "
  SELECT name, quantity
  FROM inventory
  ORDER BY quantity ASC
  LIMIT 7
");

while($row = mysqli_fetch_assoc($stockQuery)){
  $stockLabels[] = $row['name'];
  $stockData[]   = $row['quantity'];
}


/* =============================
   6. INVENTORY HISTORY TABLE
   (replace inventory_history if needed)
============================= */
$inventoryHistory = [];

$historyQuery = mysqli_query($conn, "
  SELECT 
    i.name,
    h.action,
    h.old_value,
    h.new_value,
    h.created_at
  FROM inventory_history h
  JOIN inventory i ON h.inventory_id = i.id
  ORDER BY h.created_at DESC
  LIMIT 5
");

while($row = mysqli_fetch_assoc($historyQuery)){
  $inventoryHistory[] = [
    "product" => $row['name'],
    "action"  => $row['action'],
    "old"     => $row['old_value'],
    "new"     => $row['new_value'],
    "date"    => $row['created_at']
  ];
}


/* =============================
   7. TOP 5 PRODUCTS BY SALES
============================= */
$topProducts = [];

$topQuery = mysqli_query($conn, "
  SELECT p.name, SUM(s.amount) AS total
  FROM sales_items s
  JOIN inventory p ON s.product_id = p.id
  GROUP BY p.id
  ORDER BY total DESC
  LIMIT 5
");

while($row = mysqli_fetch_assoc($topQuery)){
  $topProducts[] = [
    "name"  => $row['name'],
    "total" => $row['total']
  ];
}


/* =============================
   8. MOST CHANGED INVENTORY
============================= */
$mostChanged = [];

$changeQuery = mysqli_query($conn, "
  SELECT i.name, COUNT(h.id) AS changes
  FROM inventory_history h
  JOIN inventory i ON h.inventory_id = i.id
  GROUP BY i.id
  ORDER BY changes DESC
  LIMIT 5
");

while($row = mysqli_fetch_assoc($changeQuery)){
  $mostChanged[] = [
    "name"  => $row['name'],
    "count" => $row['changes']
  ];
}


/* =============================
   RETURN AS JSON
============================= */
echo json_encode([
  "total_sales"       => number_format($total_sales,2),
  "gross_profit"      => number_format($gross_profit,2),
  "low_stock"          => $low_stock,
  "sales_labels"       => array_reverse($salesLabels),
  "sales_data"         => array_reverse($salesData),
  "stock_labels"        => $stockLabels,
  "stock_data"          => $stockData,
  "inventory_history"   => $inventoryHistory,
  "top_products"        => $topProducts,
  "most_changed"        => $mostChanged
]);
