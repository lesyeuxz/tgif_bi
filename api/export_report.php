<?php
// api/export_report.php
// Exports bi_summary data to CSV, Excel (TSV), or PDF + saves file + logs to DB

declare(strict_types=1);

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

include 'db_connect.php';

// FORMAT (csv | excel | pdf)
$format = strtolower($_GET['format'] ?? 'csv');

// Fetch data
$sql = "SELECT date_aggregated, total_sales, total_cost, gross_profit, total_transactions
        FROM bi_summary
        ORDER BY date_aggregated ASC";
$result = $conn->query($sql);

if ($result === false || $result->num_rows === 0) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "No data available for export.";
    exit;
}

// Load rows
$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}

// ───────────────────────────────────────────────
// SAVE TO /reports/ HELPER FUNCTION
// ───────────────────────────────────────────────
function save_report_file($filename, $content) {
    $folder = $_SERVER['DOCUMENT_ROOT'] . "/tgif_bi/reports/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $path = $folder . $filename;
    file_put_contents($path, $content);

    return "/tgif_bi/reports/" . $filename; // stored in DB
}

// ───────────────────────────────────────────────
//  CSV EXPORT
// ───────────────────────────────────────────────
if ($format === 'csv') {

    $filename = "report_" . date("Ymd_His") . ".csv";

    // Build CSV content
    $csv = "Date,Total Sales,Total Cost,Gross Profit,Transactions\n";
    foreach ($rows as $row) {
        $csv .= "{$row['date_aggregated']},{$row['total_sales']},{$row['total_cost']},{$row['gross_profit']},{$row['total_transactions']}\n";
    }

    // Save file physically
    $filePath = save_report_file($filename, $csv);

    // Insert into database
    $conn->query("
        INSERT INTO reports_generated (report_name, module, export_type, file_path, generated_by)
        VALUES ('BI Summary Report', 'admin', 'CSV', '$filePath', 1)
    ");

    // Send to browser
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=$filename");
    echo $csv;
    exit;
}

// ───────────────────────────────────────────────
//  EXCEL EXPORT (TSV but .xls extension)
// ───────────────────────────────────────────────
if ($format === 'excel') {

    $filename = "report_" . date("Ymd_His") . ".xls";

    $excel = "Date\tTotal Sales\tTotal Cost\tGross Profit\tTransactions\n";
    foreach ($rows as $row) {
        $excel .= "{$row['date_aggregated']}\t{$row['total_sales']}\t{$row['total_cost']}\t{$row['gross_profit']}\t{$row['total_transactions']}\n";
    }

    // Save file
    $filePath = save_report_file($filename, $excel);

    // Log
    $conn->query("
        INSERT INTO reports_generated (report_name, module, export_type, file_path, generated_by)
        VALUES ('BI Summary Report', 'admin', 'Excel', '$filePath', 1)
    ");

    // Output
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename");
    echo $excel;
    exit;
}

// ───────────────────────────────────────────────
//  PDF EXPORT USING DOMPDF
// ───────────────────────────────────────────────
if ($format === 'pdf') {

    $filename = "report_" . date("Ymd_His") . ".pdf";

    // Build HTML
    $html = '
    <html><head><meta charset="utf-8" />
    <style>
      body { font-family: DejaVu Sans, Arial; color:#1f2b16; }
      h2 { text-align:center; color:#2e7d32; margin-bottom:10px; }
      table { width:100%; border-collapse:collapse; font-size:12px; }
      th, td { border:1px solid #ddd; padding:8px; text-align:center; }
      th { background:#e8f5e9; color:#1b5e20; }
      .small { font-size:11px; color:#666; }
    </style>
    </head><body>
    <h2>TGIF Business Intelligence Report</h2>
    <p class="small">Generated: ' . date('Y-m-d H:i:s') . '</p>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Total Sales</th>
          <th>Total Cost</th>
          <th>Gross Profit</th>
          <th>Transactions</th>
        </tr>
      </thead>
      <tbody>';

    foreach ($rows as $row) {
        $html .= "<tr>
                    <td>{$row['date_aggregated']}</td>
                    <td>₱" . number_format((float)$row['total_sales'], 2) . "</td>
                    <td>₱" . number_format((float)$row['total_cost'], 2) . "</td>
                    <td>₱" . number_format((float)$row['gross_profit'], 2) . "</td>
                    <td>{$row['total_transactions']}</td>
                  </tr>";
    }

    $html .= "</tbody></table></body></html>";

    // Create PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Save file
    $pdfOutput = $dompdf->output();
    $filePath = save_report_file($filename, $pdfOutput);

    // Log
    $conn->query("
        INSERT INTO reports_generated (report_name, module, export_type, file_path, generated_by)
        VALUES ('BI Summary Report', 'admin', 'PDF', '$filePath', 1)
    ");

    // Download to browser
    $dompdf->stream($filename, ["Attachment" => 1]);
    exit;
}

// INVALID FORMAT
header('Content-Type: text/plain; charset=utf-8');
echo "Invalid format. Use format=csv|excel|pdf";
exit;
