<?php
declare(strict_types=1);

class ReportExporter
{
    public static function export(string $format, array $report, string $filePrefix, mysqli $db, int $userId, array $filters = []): void
    {
        $safeFormat = strtolower($format);
        $timestamp  = date('Ymd_His');
        $filename   = "{$filePrefix}_{$timestamp}";
        $dir        = $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/reports/inventory/';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $extension = '';
        $fullPath  = '';
        switch ($safeFormat) {
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

        $publicPath = "/tgif_bi/reports/inventory/{$filename}.{$extension}";
        self::logExport($db, $report['title'] ?? $filePrefix, strtoupper($safeFormat), $publicPath, $userId);
        header("Location: {$publicPath}");
        exit;
    }

    private static function writeExcelFile(string $path, array $report): void
    {
        $handle = fopen($path, 'w');
        if (!$handle) {
            throw new RuntimeException('Unable to create export file.');
        }

        fwrite($handle, implode("\t", $report['columns']) . "\n");
        foreach ($report['rows'] as $row) {
            fwrite($handle, implode("\t", array_map(fn($value) => strip_tags((string)$value), $row)) . "\n");
        }
        fclose($handle);
    }

    private static function writePdfFile(string $path, array $report): void
    {
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $report['title'] ?? 'Inventory Report', 0, 1, 'C');
        $pdf->Ln(3);

        $pdf->SetFont('Arial', '', 11);
        $pdf->SetFillColor(27, 94, 32);
        $pdf->SetTextColor(255);

        foreach ($report['columns'] as $col) {
            $pdf->Cell(40, 8, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetTextColor(0);
        foreach ($report['rows'] as $row) {
            foreach ($row as $value) {
                $pdf->Cell(40, 8, mb_strimwidth(strip_tags((string)$value), 0, 30, '...'), 1);
            }
            $pdf->Ln();
        }

        $pdf->Output($path, 'F');
    }

    private static function logExport(mysqli $db, string $reportName, string $exportType, string $filePath, int $userId): void
    {
        $stmt = $db->prepare("
            INSERT INTO reports_generated (report_name, module, generated_by, export_type, file_path)
            VALUES (?, 'inventory', ?, ?, ?)
        ");

        $stmt->bind_param('siss', $reportName, $userId, $exportType, $filePath);
        $stmt->execute();
        $stmt->close();
    }
}

