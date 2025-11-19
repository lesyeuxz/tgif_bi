<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// Fetch low stock alerts dynamically
$sql = "SELECT 
            i.inventory_id, 
            p.product_name, 
            i.quantity_in_stock AS quantity, 
            CASE 
                WHEN i.quantity_in_stock = 0 THEN 'Out of Stock'
                WHEN i.quantity_in_stock <= 5 THEN 'Low'
                ELSE 'In Stock'
            END AS status, 
            i.last_updated AS updated_at
        FROM inventory i
        LEFT JOIN products p ON i.product_id = p.product_id
        WHERE i.quantity_in_stock <= 5
        ORDER BY i.quantity_in_stock ASC";
$result = $conn->query($sql);

// Export handling
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="low_stock_alerts.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Product','Quantity','Status','Last Updated']);
    while($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['inventory_id'],
            $row['product_name'],
            $row['quantity'],
            $row['status'],
            $row['updated_at']
        ]);
    }
    fclose($output);
    exit;
}

if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=low_stock_alerts.xls");
    echo "ID\tProduct\tQuantity\tStatus\tLast Updated\n";
    while($row = $result->fetch_assoc()) {
        echo $row['inventory_id'] . "\t" .
             $row['product_name'] . "\t" .
             $row['quantity'] . "\t" .
             $row['status'] . "\t" .
             $row['updated_at'] . "\n";
    }
    exit;
}

if (isset($_POST['export_pdf'])) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Low Stock Alerts',0,1,'C');
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(10,8,'ID',1);
    $pdf->Cell(50,8,'Product',1);
    $pdf->Cell(20,8,'Qty',1);
    $pdf->Cell(25,8,'Status',1);
    $pdf->Cell(40,8,'Last Updated',1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(10,8,$row['inventory_id'],1);
        $pdf->Cell(50,8,$row['product_name'],1);
        $pdf->Cell(20,8,$row['quantity'],1);
        $pdf->Cell(25,8,$row['status'],1);
        $pdf->Cell(40,8,$row['updated_at'],1);
        $pdf->Ln();
    }
    $pdf->Output('D','low_stock_alerts.pdf');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Low Stock Alerts</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Low Stock Alerts</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Re-run the query to fetch results for the table display
                $result = $conn->query($sql);

                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        echo "<tr>
                            <td>{$row['inventory_id']}</td>
                            <td>".htmlspecialchars($row['product_name'])."</td>
                            <td>{$row['quantity']}</td>
                            <td>";
                        // Apply status badge styling
                        if($row['status'] == 'Out of Stock'){
                            echo '<span class="status-badge out-of-stock">Out of Stock</span>';
                        } elseif($row['status'] == 'Low'){
                            echo '<span class="status-badge low-stock">Low</span>';
                        } else {
                            echo '<span class="status-badge in-stock">In Stock</span>';
                        }
                        echo "</td>
                            <td>{$row['updated_at']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No low stock items found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="export-buttons" style="text-align: center; margin-top: 15px;">
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
