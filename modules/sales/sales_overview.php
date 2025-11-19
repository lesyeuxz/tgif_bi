<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}
require_once '../../api/db_connect.php';

// Include FPDF library for PDF export
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// Handle Exports
if (isset($_POST['export_csv']) || isset($_POST['export_excel']) || isset($_POST['export_pdf'])) {

    $sql = "SELECT s.sale_id, s.date, s.invoice_no, s.quantity, s.price, s.total, s.customer_name,
                   p.product_name
            FROM sales s
            LEFT JOIN products p ON s.product_id = p.product_id
            ORDER BY s.sale_id DESC";
    $result = $conn->query($sql);

    // CSV Export
    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_export.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Sale ID', 'Date', 'Invoice No', 'Product', 'Quantity', 'Price', 'Total', 'Customer']);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['sale_id'],
                    $row['date'],
                    $row['invoice_no'],
                    $row['product_name'] ?? $row['product_id'],
                    $row['quantity'],
                    number_format($row['price'],2),
                    number_format($row['total'],2),
                    $row['customer_name']
                ]);
            }
        }
        fclose($output);
        exit;
    }

    // Excel Export
    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=sales_export.xls");

        echo "Sale ID\tDate\tInvoice No\tProduct\tQuantity\tPrice\tTotal\tCustomer\n";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo $row['sale_id'] . "\t" .
                     $row['date'] . "\t" .
                     $row['invoice_no'] . "\t" .
                     ($row['product_name'] ?? $row['product_id']) . "\t" .
                     $row['quantity'] . "\t" .
                     number_format($row['price'],2) . "\t" .
                     number_format($row['total'],2) . "\t" .
                     $row['customer_name'] . "\n";
            }
        }
        exit;
    }

    // PDF Export
    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Sales Export',0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(15,8,'ID',1);
        $pdf->Cell(25,8,'Date',1);
        $pdf->Cell(30,8,'Invoice No',1);
        $pdf->Cell(40,8,'Product',1);
        $pdf->Cell(15,8,'Qty',1);
        $pdf->Cell(20,8,'Price',1);
        $pdf->Cell(20,8,'Total',1);
        $pdf->Cell(35,8,'Customer',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pdf->Cell(15,8,$row['sale_id'],1);
                $pdf->Cell(25,8,$row['date'],1);
                $pdf->Cell(30,8,$row['invoice_no'],1);
                $pdf->Cell(40,8,$row['product_name'] ?? $row['product_id'],1);
                $pdf->Cell(15,8,$row['quantity'],1);
                $pdf->Cell(20,8,number_format($row['price'],2),1);
                $pdf->Cell(20,8,number_format($row['total'],2),1);
                $pdf->Cell(35,8,$row['customer_name'],1);
                $pdf->Ln();
            }
        }
        $pdf->Output('D','sales_export.pdf');
        exit;
    }
}

// Fetch Top 5 Products
$topSql = "SELECT p.product_name, SUM(s.quantity) AS total_quantity, SUM(s.total) AS total_revenue
           FROM sales s
           LEFT JOIN products p ON s.product_id = p.product_id
           GROUP BY s.product_id
           ORDER BY total_quantity DESC
           LIMIT 5";
$topResult = $conn->query($topSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Overview</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

<style>
.top-products-grid {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 10px;
}
.top-product-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    padding: 12px 16px;
    border-radius: 8px;
    min-width: 180px;
    flex: 1 1 180px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.tp-name {
    font-weight: 600;
    color: #1b5e20;
    font-size: 0.95rem;
}
.tp-qty {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 4px;
}

/* ✅ Scrollable Table */
.scroll-table {
    max-height: 450px;
    overflow-y: auto;
    overflow-x: auto;
    margin-top: 20px;
    border: 1px solid #e5e7eb;
    background: white;
    -webkit-overflow-scrolling: touch;
}

.scroll-table table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

/* Sticky Header */
.scroll-table thead th {
    position: sticky;
    top: 0;
    background: #1b5e20;
    z-index: 5;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .top-products-grid {
        flex-direction: column;
        gap: 8px;
    }
    
    .top-product-card {
        min-width: 100%;
        flex: 1 1 100%;
    }
    
    .scroll-table {
        max-height: 400px;
        margin-top: 15px;
    }
    
    .scroll-table table {
        font-size: 0.85rem;
        min-width: 700px;
    }
    
    .scroll-table th,
    .scroll-table td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .top-product-card {
        padding: 10px 12px;
    }
    
    .tp-name {
        font-size: 0.85rem;
    }
    
    .tp-qty {
        font-size: 0.75rem;
    }
    
    .scroll-table {
        max-height: 350px;
    }
    
    .scroll-table table {
        font-size: 0.75rem;
        min-width: 600px;
    }
    
    .scroll-table th,
    .scroll-table td {
        padding: 6px 4px;
        font-size: 0.7rem;
    }
}
</style>

</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Sales Overview</h2>

        <!-- Top 5 Products Summary -->
        <div class="top-products-summary">
            <h3>Top 5 Products</h3>
            <div class="top-products-grid">
                <?php while($row = $topResult->fetch_assoc()): ?>
                    <div class="top-product-card">
                        <div class="tp-name"><?php echo htmlspecialchars($row['product_name']); ?></div>
                        <div class="tp-qty"><?php echo $row['total_quantity']; ?> sold</div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Scrollable Table Wrapper -->
        <div class="scroll-table">
            <table>
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Customer</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT s.sale_id, s.date, s.invoice_no, s.quantity, s.price, s.total, s.customer_name,
                               p.product_name
                        FROM sales s
                        LEFT JOIN products p ON s.product_id = p.product_id
                        ORDER BY s.sale_id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['sale_id']}</td>
                            <td>" . date("M d, Y", strtotime($row['date'])) . "</td>
                            <td>{$row['invoice_no']}</td>
                            <td>" . ($row['product_name'] ?? $row['product_id']) . "</td>
                            <td>{$row['quantity']}</td>
                            <td>" . number_format($row['price'], 2) . "</td>
                            <td>" . number_format($row['total'], 2) . "</td>
                            <td>{$row['customer_name']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No sales found.</td></tr>";
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
