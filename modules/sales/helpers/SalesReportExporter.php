<?php
declare(strict_types=1);

class SalesReportExporter
{
    public static function export(string $format, array $report, string $filePrefix, mysqli $db, int $userId, array $filters = []): void
    {
        $safeFormat = strtolower($format);
        $timestamp  = date('Ymd_His');
        $filename   = "{$filePrefix}_{$timestamp}";
        $dir        = $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/reports/sales/';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $extension = '';
        $fullPath  = '';
        switch ($safeFormat) {
            case 'csv':
                $extension = 'csv';
                $fullPath  = "{$dir}{$filename}.{$extension}";
                self::writeCsvFile($fullPath, $report);
                break;
            case 'excel':
                $extension = 'xls';
                $fullPath  = "{$dir}{$filename}.{$extension}";
                self::writeExcelFile($fullPath, $report);
                break;
            case 'pdf':
                require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/assets/libs/fpdf/fpdf.php';
                $extension = 'pdf';
                $fullPath  = "{$dir}{$filename}.{$extension}";
                self::writePdfFile($fullPath, $report);
                break;
            default:
                throw new InvalidArgumentException('Unsupported export format.');
        }

        $publicPath = "/tgif_bi/reports/sales/{$filename}.{$extension}";
        
        // Log to reports_generated table (use main database connection)
        require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/api/db_connect.php';
        self::logExport($conn, $report['title'] ?? $filePrefix, strtoupper($safeFormat), $publicPath, $userId);
        
        // Output file to browser
        if ($safeFormat === 'csv') {
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment; filename={$filename}.{$extension}");
            readfile($fullPath);
            exit;
        }
        
        if ($safeFormat === 'excel') {
            header("Content-Type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename={$filename}.{$extension}");
            readfile($fullPath);
            exit;
        }
        
        if ($safeFormat === 'pdf') {
            header("Content-Type: application/pdf");
            header("Content-Disposition: attachment; filename={$filename}.{$extension}");
            readfile($fullPath);
            exit;
        }
    }

    private static function writeCsvFile(string $path, array $report): void
    {
        $handle = fopen($path, 'w');
        if (!$handle) {
            throw new RuntimeException('Unable to create export file.');
        }

        // Write headers
        fputcsv($handle, $report['columns'] ?? []);
        
        // Write rows
        foreach ($report['rows'] as $row) {
            $values = is_array($row) ? array_values($row) : [];
            fputcsv($handle, array_map(fn($value) => strip_tags((string)$value), $values));
        }
        
        fclose($handle);
    }

    private static function writeExcelFile(string $path, array $report): void
    {
        $handle = fopen($path, 'w');
        if (!$handle) {
            throw new RuntimeException('Unable to create export file.');
        }

        // Write headers (tab-separated)
        fwrite($handle, implode("\t", $report['columns'] ?? []) . "\n");
        
        // Write rows
        foreach ($report['rows'] as $row) {
            $values = is_array($row) ? array_values($row) : [];
            fwrite($handle, implode("\t", array_map(fn($value) => strip_tags((string)$value), $values)) . "\n");
        }
        
        fclose($handle);
    }

    private static function writePdfFile(string $path, array $report): void
    {
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $report['title'] ?? 'Sales Report', 0, 1, 'C');
        $pdf->Ln(3);

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetFillColor(27, 94, 32);
        $pdf->SetTextColor(255);

        $columns = $report['columns'] ?? [];
        $colWidth = 280 / count($columns);
        
        foreach ($columns as $col) {
            $pdf->Cell($colWidth, 8, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(0);
        foreach ($report['rows'] as $row) {
            $values = is_array($row) ? array_values($row) : [];
            foreach ($values as $value) {
                $pdf->Cell($colWidth, 8, mb_strimwidth(strip_tags((string)$value), 0, 30, '...'), 1);
            }
            $pdf->Ln();
        }

        $pdf->Output($path, 'F');
    }

    private static function logExport(mysqli $db, string $reportName, string $exportType, string $filePath, int $userId): void
    {
        $stmt = $db->prepare("
            INSERT INTO reports_generated (report_name, module, generated_by, export_type, file_path)
            VALUES (?, 'sales', ?, ?, ?)
        ");

        $stmt->bind_param('siss', $reportName, $userId, $exportType, $filePath);
        $stmt->execute();
        $stmt->close();
    }
}

