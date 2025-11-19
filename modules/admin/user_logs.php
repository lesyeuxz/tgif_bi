<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/vendor/autoload.php';

use Dompdf\Dompdf;

// ======================================
// FETCH LOG DATA (reusable for export)
// ======================================
$sql = "
    SELECT 
        l.log_id,
        u.username,
        l.action,
        l.timestamp,
        l.ip_address
    FROM user_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    ORDER BY l.timestamp DESC
";

$result = $conn->query($sql);

// Store logs in array
$logs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

// ======================================
// EXPORT HANDLERS
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -------- CSV EXPORT --------
    if (isset($_POST['export_csv'])) {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=user_logs.csv");

        $out = fopen("php://output", "w");
        fputcsv($out, ["Log ID", "User", "Action", "Timestamp", "IP Address"]);

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['log_id'],
                $log['username'] ?? "Unknown",
                $log['action'],
                $log['timestamp'],
                $log['ip_address']
            ]);
        }
        fclose($out);
        exit;
    }

    // -------- EXCEL EXPORT (TSV) --------
    if (isset($_POST['export_excel'])) {
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=user_logs.xls");

        echo "Log ID\tUser\tAction\tTimestamp\tIP Address\n";
        foreach ($logs as $log) {
            echo "{$log['log_id']}\t" .
                 ($log['username'] ?? 'Unknown') . "\t" .
                 "{$log['action']}\t" .
                 "{$log['timestamp']}\t" .
                 "{$log['ip_address']}\n";
        }
        exit;
    }

    // -------- PDF EXPORT --------
    if (isset($_POST['export_pdf'])) {

        $html = '
        <html>
        <head>
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; }
            h2 { text-align:center; color:#2e7d32; }
            table { width:100%; border-collapse:collapse; font-size:12px; }
            th, td { border:1px solid #ccc; padding:8px; }
            th { background:#2e7d32; color:#fff; }
        </style>
        </head>
        <body>
        <h2>User Activity Logs</h2>
        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
        ';

        foreach ($logs as $log) {
            $html .= '
            <tr>
                <td>' . $log['log_id'] . '</td>
                <td>' . ($log['username'] ?? 'Unknown') . '</td>
                <td>' . $log['action'] . '</td>
                <td>' . $log['timestamp'] . '</td>
                <td>' . $log['ip_address'] . '</td>
            </tr>';
        }

        $html .= '
            </tbody>
        </table>
        </body>
        </html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();
        $dompdf->stream("user_logs.pdf", ["Attachment" => 1]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Logs</title>

<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

<style>
.main-content {
    background: #fffef6;
    padding: 2rem;
    min-height: 100vh;
}

.table-container {
    overflow-x: auto;
    background: #fff;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    background: white;
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

.export-buttons {
    margin: 20px 0;
}

.export-buttons button {
    background: #2e7d32;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-right: 6px;
}
.export-buttons button:hover {
    background: #1b5e20;
}
</style>

</head>
<body>

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <h2>User Activity Logs</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $row): ?>
                    <tr>
                        <td><?= $row['log_id'] ?></td>
                        <td><?= $row['username'] ?? 'Unknown User' ?></td>
                        <td><?= $row['action'] ?></td>
                        <td><?= $row['timestamp'] ?></td>
                        <td><?= $row['ip_address'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No logs found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="export-buttons">
        <form method="post">
            <button type="submit" name="export_csv">Export CSV</button>
            <button type="submit" name="export_excel">Export Excel</button>
            <button type="submit" name="export_pdf">Export PDF</button>
        </form>
    </div>
</div>

</body>
</html>
