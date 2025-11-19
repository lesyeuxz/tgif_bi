<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/assets/libs/fpdf/fpdf.php';

// Handle exports
if (isset($_POST['export_csv']) || isset($_POST['export_excel']) || isset($_POST['export_pdf'])) {
    $query = "SELECT supplier_id, supplier_name, contact_person, phone, email, reliability_score, created_at
              FROM suppliers ORDER BY supplier_name ASC";
    $result = $conn->query($query);

    // Export CSV
    if (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="supplier_performance.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Supplier ID', 'Supplier Name', 'Contact', 'Phone', 'Email', 'Reliability', 'Created At']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['supplier_id'],
                $row['supplier_name'],
                $row['contact_person'],
                $row['phone'],
                $row['email'],
                $row['reliability_score'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    // Export Excel
    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=supplier_performance.xls");
        echo "Supplier ID\tSupplier Name\tContact\tPhone\tEmail\tReliability\tCreated At\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['supplier_id']}\t{$row['supplier_name']}\t{$row['contact_person']}\t{$row['phone']}\t{$row['email']}\t{$row['reliability_score']}\t{$row['created_at']}\n";
        }
        exit;
    }

    // Export PDF
    if (isset($_POST['export_pdf'])) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Supplier Performance Report', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 8, 'ID', 1);
        $pdf->Cell(40, 8, 'Supplier', 1);
        $pdf->Cell(35, 8, 'Contact', 1);
        $pdf->Cell(30, 8, 'Phone', 1);
        $pdf->Cell(40, 8, 'Email', 1);
        $pdf->Cell(20, 8, 'Score', 1);
        $pdf->Cell(30, 8, 'Created', 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 10);
        while ($row = $result->fetch_assoc()) {
            $pdf->Cell(15, 8, $row['supplier_id'], 1);
            $pdf->Cell(40, 8, $row['supplier_name'], 1);
            $pdf->Cell(35, 8, $row['contact_person'], 1);
            $pdf->Cell(30, 8, $row['phone'], 1);
            $pdf->Cell(40, 8, $row['email'], 1);
            $pdf->Cell(20, 8, $row['reliability_score'], 1);
            $pdf->Cell(30, 8, $row['created_at'], 1);
            $pdf->Ln();
        }
        $pdf->Output('D', 'supplier_performance.pdf');
        exit;
    }
}

// Fetch suppliers for display
$sql = "SELECT supplier_id, supplier_name, contact_person, phone, email, reliability_score, created_at
        FROM suppliers ORDER BY supplier_name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supplier Performance</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
<style>
.reliability-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
}
.reliability-high {
    background-color: #2e7d32; /* green - consistent with brand */
}
.reliability-medium {
    background-color: #facc15; /* yellow */
    color: #1e293b;
}
.reliability-low {
    background-color: #ef4444; /* red */
}
</style>
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Supplier Performance</h2>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Reliability</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $score = floatval($row['reliability_score']);
                        if ($score >= 90) {
                            $badge = "<span class='reliability-badge reliability-high'>{$score}%</span>";
                        } elseif ($score >= 70) {
                            $badge = "<span class='reliability-badge reliability-medium'>{$score}%</span>";
                        } else {
                            $badge = "<span class='reliability-badge reliability-low'>{$score}%</span>";
                        }

                        echo "<tr>
                            <td>{$row['supplier_id']}</td>
                            <td>".htmlspecialchars($row['supplier_name'])."</td>
                            <td>".htmlspecialchars($row['contact_person'])."</td>
                            <td>{$row['phone']}</td>
                            <td>".htmlspecialchars($row['email'])."</td>
                            <td>{$badge}</td>
                            <td>{$row['created_at']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No supplier data available.</td></tr>";
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
