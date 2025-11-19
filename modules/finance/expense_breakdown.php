<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// EXPORT HANDLING
if (isset($_POST['export_csv']) || isset($_POST['export_excel']) || isset($_POST['export_pdf'])) {
    $sql = "SELECT expense_id, expense_date, category, description, amount FROM expenses ORDER BY expense_date DESC";
    $result = $conn->query($sql);

    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expense_breakdown.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Expense ID', 'Date', 'Category', 'Description', 'Amount']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        exit;
    }

    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=expense_breakdown.xls");
        echo "Expense ID\tDate\tCategory\tDescription\tAmount\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['expense_id']}\t{$row['expense_date']}\t{$row['category']}\t{$row['description']}\t{$row['amount']}\n";
        }
        exit;
    }

    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,'Expense Breakdown Report',0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(20,8,'ID',1);
        $pdf->Cell(30,8,'Date',1);
        $pdf->Cell(40,8,'Category',1);
        $pdf->Cell(60,8,'Description',1);
        $pdf->Cell(30,8,'Amount',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        $total = 0;

        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(20,8,$row['expense_id'],1);
            $pdf->Cell(30,8,$row['expense_date'],1);
            $pdf->Cell(40,8,$row['category'],1);
            $pdf->Cell(60,8,$row['description'],1);
            $pdf->Cell(30,8,number_format($row['amount'],2),1);
            $pdf->Ln();

            $total += $row['amount'];
        }

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(150,8,'Total',1);
        $pdf->Cell(30,8,number_format($total,2),1);

        $pdf->Output('D','expense_breakdown.pdf');
        exit;
    }
}

// DATA QUERIES
$summary = $conn->query("
    SELECT category, SUM(amount) AS total_amount 
    FROM expenses 
    GROUP BY category 
    ORDER BY total_amount DESC
");

$details = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Breakdown</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

<style>
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
table th {
    background: #2e7d32;
    color: white;
    padding: 10px;
}
table td {
    padding: 8px 10px;
    border-bottom: 1px solid #ddd;
}
.export-buttons button {
    background: #2e7d32;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin: 5px;
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

        <h2>Expense Breakdown</h2>

        <h3>Category Summary</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Amount (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $overall = 0;
                if ($summary->num_rows > 0) {
                    while ($row = $summary->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['category']}</td>
                                <td>".number_format($row['total_amount'], 2)."</td>
                              </tr>";
                        $overall += $row['total_amount'];
                    }
                    echo "<tr style='font-weight:bold; background:#e8f5e9; color:#1b5e20;'>
                            <td>Overall Total</td>
                            <td>".number_format($overall, 2)."</td>
                          </tr>";
                } else {
                    echo "<tr><td colspan='2'>No data found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <h3>Expense Details</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount (₱)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($details->num_rows > 0) {
                    while ($row = $details->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['expense_id']}</td>
                                <td>{$row['expense_date']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['description']}</td>
                                <td>".number_format($row['amount'], 2)."</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No expense records found.</td></tr>";
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
