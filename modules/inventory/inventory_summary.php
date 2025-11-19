<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// Export handling
if (isset($_POST['export_csv']) || isset($_POST['export_excel']) || isset($_POST['export_pdf'])) {

    $sql = "SELECT i.inventory_id, i.product_id, p.product_name, 
                   i.quantity_in_stock AS quantity, i.status, i.last_updated AS created_at
            FROM inventory i
            LEFT JOIN products p ON i.product_id = p.product_id
            ORDER BY i.inventory_id DESC";
    $result = $conn->query($sql);

    /* CSV EXPORT */
    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_export.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID','Product','Quantity','Status','Last Updated']);

        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['inventory_id'],
                $row['product_name'],
                $row['quantity'],
                $row['status'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    /* EXCEL EXPORT */
    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=inventory_export.xls");

        echo "ID\tProduct\tQuantity\tStatus\tLast Updated\n";
        while($row = $result->fetch_assoc()) {
            echo "{$row['inventory_id']}\t{$row['product_name']}\t{$row['quantity']}\t{$row['status']}\t{$row['created_at']}\n";
        }
        exit;
    }

    /* PDF EXPORT */
    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,'Inventory Summary',0,1,'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(15,8,'ID',1);
        $pdf->Cell(55,8,'Product',1);
        $pdf->Cell(20,8,'Qty',1);
        $pdf->Cell(25,8,'Status',1);
        $pdf->Cell(40,8,'Last Updated',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',10);
        while($row = $result->fetch_assoc()) {
            $pdf->Cell(15,8,$row['inventory_id'],1);
            $pdf->Cell(55,8,$row['product_name'],1);
            $pdf->Cell(20,8,$row['quantity'],1);
            $pdf->Cell(25,8,$row['status'],1);
            $pdf->Cell(40,8,$row['created_at'],1);
            $pdf->Ln();
        }

        $pdf->Output('D','inventory_export.pdf');
        exit;
    }
}

// Fetch low stock alerts
$lowStockSql = "SELECT i.inventory_id, p.product_name, 
                       i.quantity_in_stock AS quantity, i.status
                FROM inventory i
                LEFT JOIN products p ON i.product_id = p.product_id
                WHERE i.quantity_in_stock <= 5
                ORDER BY i.quantity_in_stock ASC";
$lowStockResult = $conn->query($lowStockSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Summary</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

<style>
.table-container {
    max-height: 420px;
    overflow-y: auto;
    overflow-x: auto;
    border: 1px solid #dcdcdc;
    margin-top: 10px;
    -webkit-overflow-scrolling: touch;
}
table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
}
table th {
    color: #ffffffff;
    font-weight: 700;
    background: #1b5e20;
    position: sticky;
    top: 0;
    z-index: 5;
}
.status-badge {
    padding: 3px 8px;
    border-radius: 6px;
    color: #fff;
    font-size: 0.8rem;
    display: inline-block;
    white-space: nowrap;
}
.out-of-stock {
    background: #c62828;
}
.low-stock {
    background: #ef6c00;
}
.in-stock {
    background: #2e7d32;
}
.top-products-summary {
    margin-bottom: 20px;
    background: #fafafa;
    padding: 15px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}
.top-products-summary h3 {
    margin-bottom: 10px;
    font-size: 1.1rem;
    color: #1b5e20;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .table-container {
        max-height: 400px;
    }
    
    table {
        font-size: 0.85rem;
        min-width: 550px;
    }
    
    table th,
    table td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
    
    .top-products-summary {
        padding: 12px;
    }
    
    .top-products-summary h3 {
        font-size: 1rem;
    }
    
    .top-products-summary ul {
        font-size: 0.9rem;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
    }
}

@media (max-width: 480px) {
    .table-container {
        max-height: 350px;
    }
    
    table {
        font-size: 0.75rem;
        min-width: 500px;
    }
    
    table th,
    table td {
        padding: 6px 4px;
        font-size: 0.7rem;
    }
    
    .top-products-summary {
        padding: 10px;
    }
    
    .top-products-summary h3 {
        font-size: 0.95rem;
    }
    
    .top-products-summary ul {
        font-size: 0.85rem;
        padding-left: 20px;
    }
    
    .status-badge {
        font-size: 0.7rem;
        padding: 2px 5px;
    }
}
</style>

</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Inventory Summary</h2>

        <!-- Low stock section -->
        <div class="top-products-summary">
            <h3>Low Stock Alerts</h3>
            <ul>
                <?php while($row = $lowStockResult->fetch_assoc()): ?>
                    <li>
                        <?= htmlspecialchars($row['product_name']) ?> - Qty: <?= $row['quantity'] ?> (
                        <?php
                            if ($row['status'] == 'Out of Stock') {
                                echo '<span class="status-badge out-of-stock">Out of Stock</span>';
                            } elseif ($row['status'] == 'Low') {
                                echo '<span class="status-badge low-stock">Low</span>';
                            } else {
                                echo '<span class="status-badge in-stock">In Stock</span>';
                            }
                        ?>
                        )
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- TABLE -->
        <div class="table-container">
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
                $sql = "SELECT i.inventory_id, p.product_name, 
                               i.quantity_in_stock AS quantity, i.status, 
                               i.last_updated AS created_at
                        FROM inventory i
                        LEFT JOIN products p ON i.product_id = p.product_id
                        ORDER BY i.inventory_id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['inventory_id']}</td>
                                <td>".htmlspecialchars($row['product_name'])."</td>
                                <td>{$row['quantity']}</td>
                                <td>";
                        if ($row['status'] == 'Out of Stock') {
                            echo '<span class="status-badge out-of-stock">Out of Stock</span>';
                        } elseif ($row['status'] == 'Low') {
                            echo '<span class="status-badge low-stock">Low</span>';
                        } else {
                            echo '<span class="status-badge in-stock">In Stock</span>';
                        }
                        echo "</td>
                                <td>{$row['created_at']}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No inventory found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

       <!-- Export Buttons -->
<div class="export-buttons" style="text-align: center;">       
    <form method="post">
        <button type="submit" name="export_csv">Export CSV</button>
        <button type="submit" name="export_excel">Export Excel</button>
        <button type="submit" name="export_pdf">Export PDF</button>
    </form>
</div>
