<?php
// FILE: /api/bi_summary.php

include 'db_connect.php'; // Include the database connection script
header('Content-Type: application/json');

// Get the most recent aggregated summary record.
// Note: We select the exact fields that exist in your bi_summary table.
$sql = "SELECT
    total_sales_rev,
    gross_profit,
    total_expenses,
    avg_order_value,
    low_stock_count,
    supplier_reliability_pct,
    profit_margin_pct,
    cash_balance,
    cost_ratio,
    inventory_turnover,
    sales_growth_pct,
    report_date
FROM bi_summary 
ORDER BY report_date DESC 
LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Return the summary data if found
    echo json_encode($result->fetch_assoc());
} else {
    // Return a structured empty array if no summary data is found (NO HR metrics included)
    echo json_encode([
        "report_date" => null,
        "total_sales_rev" => 0.00,
        "total_expenses" => 0.00,
        "gross_profit" => 0.00,
        "avg_order_value" => 0.00,
        "low_stock_count" => 0,
        "supplier_reliability_pct" => 0.00,
        "profit_margin_pct" => 0.00,
        "cash_balance" => 0.00,
        "cost_ratio" => 0.00,
        "inventory_turnover" => 0.00,
        "sales_growth_pct" => 0.00
    ]);
}

$conn->close();
?>