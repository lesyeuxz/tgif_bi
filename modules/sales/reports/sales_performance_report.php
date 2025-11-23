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

// Get data from database (will use mock data if connection fails or no data available)
$service = new SalesReportService($conn_sales ?? new mysqli('localhost', 'root', '', 'customer_support'));
$reportData = $service->getSalesPerformanceByProduct();
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
        'sales_performance',
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
    <title>Sales Performance by Product - TGIF BI</title>
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
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
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
                <h1>Sales Performance by Product</h1>
                <p class="subtext">Analyze total sales and quantity sold per product</p>
            </div>
        </header>

        <div class="export-buttons">
            <a href="?export=csv" class="btn-primary">Export CSV</a>
            <a href="?export=excel" class="btn-primary">Export Excel</a>
            <a href="?export=pdf" class="btn-primary">Export PDF</a>
        </div>

        <div class="chart-container">
            <h3>Total Sales by Product</h3>
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
                <h3>Sales Performance Data</h3>
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
                                No sales data available
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reportData['rows'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['product_name']); ?></td>
                                <td><?= htmlspecialchars((string)$row['total_quantity']); ?></td>
                                <td>₱<?= htmlspecialchars((string)$row['total_sales']); ?></td>
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
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Total Sales (₱)',
                data: chartData.data,
                backgroundColor: 'rgba(46, 125, 50, 0.8)',
                borderColor: 'rgba(27, 94, 32, 1)',
                borderWidth: 1
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

