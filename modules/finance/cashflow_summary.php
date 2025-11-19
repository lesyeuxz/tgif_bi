<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// Handle export
if (isset($_POST['export_csv']) || isset($_POST['export_excel']) || isset($_POST['export_pdf'])) {
    $sql = "
        SELECT 
            s.date AS transaction_date,
            SUM(s.total) AS total_sales,
            IFNULL(SUM(e.amount), 0) AS total_expenses,
            (SUM(s.total) - IFNULL(SUM(e.amount), 0)) AS net_cashflow
        FROM sales s
        LEFT JOIN expenses e ON DATE(s.date) = DATE(e.expense_date)
        GROUP BY DATE(s.date)
        ORDER BY s.date DESC
    ";
    $result = $conn->query($sql);

    // CSV Export
    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="cashflow_summary.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Total Sales', 'Total Expenses', 'Net Cash Flow']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    // Excel Export
    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=cashflow_summary.xls");
        echo "Date\tTotal Sales\tTotal Expenses\tNet Cash Flow\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['transaction_date']}\t{$row['total_sales']}\t{$row['total_expenses']}\t{$row['net_cashflow']}\n";
        }
        exit;
    }

    // PDF Export
    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,'Cash Flow Summary',0,1,'C');
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(40,8,'Date',1);
        $pdf->Cell(40,8,'Total Sales',1);
        $pdf->Cell(40,8,'Total Expenses',1);
        $pdf->Cell(40,8,'Net Cash Flow',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(40,8,$row['transaction_date'],1);
            $pdf->Cell(40,8,number_format($row['total_sales'],2),1);
            $pdf->Cell(40,8,number_format($row['total_expenses'],2),1);
            $pdf->Cell(40,8,number_format($row['net_cashflow'],2),1);
            $pdf->Ln();
        }
        $pdf->Output('D','cashflow_summary.pdf');
        exit;
    }
}

// Fetch display data
$query = "
    SELECT 
        s.date AS transaction_date,
        SUM(s.total) AS total_sales,
        IFNULL(SUM(e.amount), 0) AS total_expenses,
        (SUM(s.total) - IFNULL(SUM(e.amount), 0)) AS net_cashflow
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
<title>Cash Flow Summary</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Cash Flow Summary</h2>
        <p>Overview of total sales, total expenses, and net cash flow per day.</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Total Sales (₱)</th>
                <th>Total Expenses (₱)</th>
                <th>Net Cash Flow (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($data->num_rows > 0) {
                while ($row = $data->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['transaction_date']}</td>
                        <td>" . number_format($row['total_sales'], 2) . "</td>
                        <td>" . number_format($row['total_expenses'], 2) . "</td>
                        <td>" . number_format($row['net_cashflow'], 2) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No cash flow records found.</td></tr>";
            }
            ?>
            </tbody>
        </table>

        <div class="export-buttons" style="text-align: center;">
            <form method="post">
                <button type="submit" name="export_csv">Export CSV</button>
                <button type="submit" name="export_excel">Export Excel</button>
                <button type="submit" name="export_pdf">Export PDF</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
