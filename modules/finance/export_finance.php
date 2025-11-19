<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}
require_once '../../api/db_connect.php';

// Fetch finance summary
$query = "
    SELECT 
        DATE(s.date) AS report_date,
        IFNULL(SUM(s.total), 0) AS total_sales,
        IFNULL(SUM(e.amount), 0) AS total_expenses,
        (IFNULL(SUM(s.total), 0) - IFNULL(SUM(e.amount), 0)) AS net_profit
    FROM sales s
    LEFT JOIN expenses e ON DATE(s.date) = DATE(e.expense_date)
    GROUP BY DATE(s.date)
    ORDER BY s.date DESC
";
$data = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Export Finance Reports</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
<style>
.export-buttons {
    text-align: center;
    margin-top: 30px;
}
.export-buttons form {
    display: inline-block;
}
.export-buttons button {
    background: #2e7d32;
    color: #fff;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    margin: 5px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.export-buttons button:hover {
    background: #1b5e20;
}
</style>
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Finance Export Reports</h2>
        <p>Download or export your overall financial reports for analysis and records.</p>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Sales (₱)</th>
                    <th>Total Expenses (₱)</th>
                    <th>Net Profit (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($data->num_rows > 0) {
                    while ($row = $data->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['report_date']}</td>
                            <td>" . number_format($row['total_sales'], 2) . "</td>
                            <td>" . number_format($row['total_expenses'], 2) . "</td>
                            <td>" . number_format($row['net_profit'], 2) . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No financial records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="export-buttons">
        <form method="post" action="/tgif_bi/modules/finance/export_sales.php">
            <button type="submit">Export Sales</button>
        </form>
        <form method="post" action="/tgif_bi/modules/finance/export_expenses.php">
            <button type="submit">Export Expenses</button>
        </form>
        <form method="post" action="/tgif_bi/modules/finance/export_profitloss.php">
            <button type="submit">Export Profit/Loss</button>
        </form>
        <form method="post" action="/tgif_bi/modules/finance/export_cashflow.php">
            <button type="submit">Export Cash Flow</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
