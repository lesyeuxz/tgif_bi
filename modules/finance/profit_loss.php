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
            s.sale_id,
            s.date,
            p.product_name,
            s.quantity,
            s.price,
            p.cost_price,
            (s.price - p.cost_price) * s.quantity AS profit,
            s.total
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        ORDER BY s.date DESC
    ";
    $result = $conn->query($sql);

    if (!$result) {
        die('Query error: ' . $conn->error);
    }

    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="profit_loss.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Sale ID', 'Date', 'Product', 'Quantity', 'Selling Price', 'Cost Price', 'Profit', 'Total']);
        while ($row = $result->fetch_assoc()) {
            // Ensure numeric formatting for export
            $row['price'] = number_format((float)$row['price'], 2, '.', '');
            $row['cost_price'] = number_format((float)$row['cost_price'], 2, '.', '');
            $row['profit'] = number_format((float)$row['profit'], 2, '.', '');
            $row['total'] = number_format((float)$row['total'], 2, '.', '');
            fputcsv($output, [
                $row['sale_id'],
                $row['date'],
                $row['product_name'],
                $row['quantity'],
                $row['price'],
                $row['cost_price'],
                $row['profit'],
                $row['total']
            ]);
        }
        fclose($output);
        exit;
    }

    if (isset($_POST['export_excel'])) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=profit_loss.xls');
        echo "Sale ID\tDate\tProduct\tQuantity\tSelling Price\tCost Price\tProfit\tTotal\n";
        while ($row = $result->fetch_assoc()) {
            // format numbers
            $price = number_format((float)$row['price'], 2, '.', '');
            $cost = number_format((float)$row['cost_price'], 2, '.', '');
            $profit = number_format((float)$row['profit'], 2, '.', '');
            $total = number_format((float)$row['total'], 2, '.', '');
            echo "{$row['sale_id']}\t{$row['date']}\t{$row['product_name']}\t{$row['quantity']}\t{$price}\t{$cost}\t{$profit}\t{$total}\n";
        }
        exit;
    }

    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,'Profit & Loss Report',0,1,'C');
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',10);

        $pdf->Cell(15,8,'ID',1);
        $pdf->Cell(25,8,'Date',1);
        $pdf->Cell(40,8,'Product',1);
        $pdf->Cell(15,8,'Qty',1);
        $pdf->Cell(25,8,'Price',1);
        $pdf->Cell(25,8,'Cost',1);
        $pdf->Cell(25,8,'Profit',1);
        $pdf->Cell(25,8,'Total',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(15,8,$row['sale_id'],1);
            $pdf->Cell(25,8,$row['date'],1);
            $pdf->Cell(40,8,$row['product_name'],1);
            $pdf->Cell(15,8,$row['quantity'],1);
            $pdf->Cell(25,8,number_format((float)$row['price'],2),1);
            $pdf->Cell(25,8,number_format((float)$row['cost_price'],2),1);
            $pdf->Cell(25,8,number_format((float)$row['profit'],2),1);
            $pdf->Cell(25,8,number_format((float)$row['total'],2),1);
            $pdf->Ln();
        }
        $pdf->Output('D','profit_loss.pdf');
        exit;
    }
}

// Fetch table data for page display
$query = "
    SELECT 
        s.sale_id,
        s.date,
        p.product_name,
        s.quantity,
        s.price,
        p.cost_price,
        (s.price - p.cost_price) * s.quantity AS profit,
        s.total
    FROM sales s
    LEFT JOIN products p ON s.product_id = p.product_id
    ORDER BY s.date DESC
";
$data = $conn->query($query);
if (!$data) {
    die('Query error: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Profit & Loss Report</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css" />
<style>
/* Scrollable table */
.scroll-table {
    max-height: 480px;
    overflow-y: auto;
    overflow-x: auto;
    margin-top: 15px;
    border: 1px solid #e5e7eb;
    background: #fff;
    -webkit-overflow-scrolling: touch;
}

/* table base */
.scroll-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

/* Sticky header */
.scroll-table thead th {
    position: sticky;
    top: 0;
    background: #1b5e20;
    z-index: 10;
    padding: 8px;
    text-align: left;
}

/* Row cells */
.scroll-table td {
    padding: 8px;
    color: #374151;
}

/* Profit coloring */
.profit-positive {
    color: #0b6623; /* green */
    font-weight: 600;
}
.profit-negative {
    color: #b91c1c; /* red */
    font-weight: 600;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .scroll-table {
        max-height: 400px;
        margin-top: 12px;
    }
    
    .scroll-table table {
        font-size: 0.85rem;
        min-width: 800px;
    }
    
    .scroll-table th,
    .scroll-table td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .scroll-table {
        max-height: 350px;
        margin-top: 10px;
    }
    
    .scroll-table table {
        font-size: 0.75rem;
        min-width: 700px;
    }
    
    .scroll-table th,
    .scroll-table td {
        padding: 6px 4px;
        font-size: 0.7rem;
    }
    
    .profit-positive,
    .profit-negative {
        font-size: 0.7rem;
    }
}
</style>
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Profit & Loss Overview</h2>

        <div class="scroll-table">
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Selling Price</th>
                        <th>Cost Price</th>
                        <th>Profit</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($data->num_rows > 0) {
                        while ($row = $data->fetch_assoc()) {
                            // Ensure numeric values and format
                            $price = number_format((float)$row['price'], 2);
                            $cost = number_format((float)$row['cost_price'], 2);
                            $profit = (float)$row['profit'];
                            $profit_fmt = number_format($profit, 2);
                            $total = number_format((float)$row['total'], 2);

                            // Set profit class
                            $profit_class = $profit >= 0 ? 'profit-positive' : 'profit-negative';

                            // Format date (optional) - keep original if empty or invalid
                            $date_display = $row['date'];
                            $timestamp = strtotime($row['date']);
                            if ($timestamp !== false) {
                                $date_display = date('M d, Y', $timestamp);
                            }

                            echo "<tr>
                                <td>{$row['sale_id']}</td>
                                <td>{$date_display}</td>
                                <td>" . htmlspecialchars($row['product_name']) . "</td>
                                <td>{$row['quantity']}</td>
                                <td>{$price}</td>
                                <td>{$cost}</td>
                                <td class=\"{$profit_class}\">{$profit_fmt}</td>
                                <td>{$total}</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

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
