<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/api/db_connect_sales.php';
require_once __DIR__ . '/../services/SalesReportService.php';
require_once __DIR__ . '/../helpers/SalesReportExporter.php';

$period = $_GET['period'] ?? 'daily';
$fromDate = $_GET['from_date'] ?? null;
$toDate = $_GET['to_date'] ?? null;

// Get data from database (will use mock data if connection fails or no data available)
$service = new SalesReportService($conn_sales ?? new mysqli('localhost', 'root', '', 'customer_support'));
$reportData = $service->getSalesSummaryByDate($period, $fromDate, $toDate);
$exportFormat = isset($_GET['export']) ? strtolower((string) $_GET['export']) : null;

if ($exportFormat) {
    if (!in_array($exportFormat, ['pdf', 'excel', 'csv'], true)) {
        http_response_code(400);
        echo 'Unsupported export format.';
        exit;
    }

    SalesReportExporter::export(
        $exportFormat,
        $reportData,
        'sales_summary',
        $conn_sales,
        (int) $_SESSION['user_id']
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary by Date - TGIF BI</title>
    <link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
    <link rel="stylesheet" href="/tgif_bi/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        .chart-container h3 {
            margin-top: 0;
            color: #1b5e20;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        .chart-container canvas {
            height: 450px !important;
            max-height: 600px;
        }
        .chart-wrapper {
            position: relative;
            height: 500px;
            width: 100%;
        }
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .filters-panel {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-grid label {
            display: flex;
            flex-direction: column;
        }
        .filter-grid label span {
            margin-bottom: 5px;
            font-weight: 600;
            color: #1b5e20;
        }
        .filter-grid input,
        .filter-grid select {
            padding: 0.6rem 0.8rem;
            border: 1px solid #d6e3d3;
            border-radius: 8px;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: flex-end;
        }
        .export-buttons a {
            padding: 10px 18px;
            background-color: #2e7d32;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .export-buttons a:hover {
            background-color: #1b5e20;
        }
        .btn-submit {
            padding: 10px 18px;
            background-color: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #1b5e20;
        }
    </style>
</head>
<body class="dashboard-body">
<?php include $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/includes/sidebar.php'; ?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/includes/header.php'; ?>

<main class="main-content">
    <div class="content-wrapper">
        <header class="reports-header">
            <div>
                <p class="eyebrow">Sales Analytics</p>
                <h1>Sales Summary by Date</h1>
                <p class="subtext">View sales trends over time with customizable date ranges</p>
            </div>
        </header>

        <div class="filters-panel">
            <form method="get" class="filters-form">
                <div class="filter-grid">
                    <label>
                        <span>Period</span>
                        <select name="period">
                            <option value="daily" <?= $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="monthly" <?= $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </label>
                    <label>
                        <span>From Date</span>
                        <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate ?? ''); ?>">
                    </label>
                    <label>
                        <span>To Date</span>
                        <input type="date" name="to_date" value="<?= htmlspecialchars($toDate ?? ''); ?>">
                    </label>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="submit" class="btn-submit">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="export-buttons">
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-primary">Export CSV</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn-primary">Export Excel</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn-primary">Export PDF</a>
        </div>

        <div class="chart-container">
            <h3>Sales Trend - <?= ucfirst($period); ?> View</h3>
            <?php if (empty($reportData['chart']['labels']) || empty($reportData['chart']['data'])): ?>
                <div class="no-data-message">
                    No sales data available to display in chart
                </div>
            <?php else: ?>
                <div class="chart-wrapper">
                    <canvas id="salesChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <div class="data-table">
            <div class="table-header">
                <h3>Sales Summary Data</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <?php foreach ($reportData['columns'] as $column): ?>
                            <th><?= htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportData['rows'])): ?>
                        <tr>
                            <td colspan="<?= count($reportData['columns']); ?>" style="text-align: center; padding: 20px;">
                                No sales data available for the selected period
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportData['rows'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['period']); ?></td>
                                <td>₱<?= htmlspecialchars((string)$row['total_sales']); ?></td>
                                <td><?= htmlspecialchars((string)$row['transactions']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = <?= json_encode($reportData['chart'] ?? ['labels' => [], 'data' => []]); ?>;
    const chartCanvas = document.getElementById('salesChart');
    
    if (!chartCanvas) {
        console.error('Chart canvas not found');
        return;
    }
    
    if (!chartData.labels || chartData.labels.length === 0 || !chartData.data || chartData.data.length === 0) {
        console.log('No chart data available');
        return;
    }
    
    const ctx = chartCanvas.getContext('2d');
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded');
        return;
    }
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Total Sales (₱)',
                data: chartData.data,
                borderColor: 'rgba(46, 125, 50, 1)',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Total Sales: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>

