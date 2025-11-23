<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/api/db_connect_sales.php';
require_once __DIR__ . '/services/SalesReportService.php';

// Get dashboard data
$service = new SalesReportService($conn_sales ?? new mysqli('localhost', 'root', '', 'customer_support'));
$dashboardData = $service->getDashboardData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics Dashboard - TGIF BI</title>
    <link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
    <link rel="stylesheet" href="/tgif_bi/assets/css/dashboard.css">
    <link rel="stylesheet" href="/tgif_bi/assets/css/inventory-reports.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #2e7d32;
        }
        .kpi-card.primary {
            border-left-color: #2e7d32;
        }
        .kpi-card.success {
            border-left-color: #43a047;
        }
        .kpi-card.info {
            border-left-color: #2196f3;
        }
        .kpi-card.warning {
            border-left-color: #ffb300;
        }
        .kpi-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1b5e20;
            margin-bottom: 5px;
        }
        .kpi-change {
            font-size: 0.85rem;
            color: #43a047;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .kpi-change.negative {
            color: #e53935;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        .chart-card h3 {
            margin-top: 0;
            color: #1b5e20;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        .chart-wrapper {
            position: relative;
            height: 450px;
            width: 100%;
        }
        .chart-card canvas {
            height: 450px !important;
            max-height: 600px;
        }
        .quick-links {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .quick-links h3 {
            margin-top: 0;
            color: #1b5e20;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .link-card {
            padding: 20px;
            background: #f5f7fa;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .link-card:hover {
            background: #e8f5e9;
            border-color: #2e7d32;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .link-card a {
            text-decoration: none;
            color: #1b5e20;
            font-weight: 600;
            display: block;
            font-size: 1.05rem;
            margin-bottom: 8px;
        }
        .link-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .kpi-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-card {
                min-height: 400px;
                padding: 20px;
            }
            .chart-wrapper {
                height: 350px;
            }
            .chart-card canvas {
                height: 350px !important;
            }
        }
        @media (max-width: 480px) {
            .kpi-cards {
                grid-template-columns: 1fr;
            }
            .links-grid {
                grid-template-columns: 1fr;
            }
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
                <h1>Sales Analytics Dashboard</h1>
                <p class="subtext">Real-time insights and key performance indicators for sales operations</p>
            </div>
        </header>

        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card primary">
                <div class="kpi-label">Total Sales</div>
                <div class="kpi-value">₱<?= number_format($dashboardData['total_sales'], 2); ?></div>
                <div class="kpi-change">
                    <span>All time</span>
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label">Total Orders</div>
                <div class="kpi-value"><?= number_format($dashboardData['total_orders'], 0); ?></div>
                <div class="kpi-change">
                    <span>Completed orders</span>
                </div>
            </div>
            <div class="kpi-card info">
                <div class="kpi-label">Average Order Value</div>
                <div class="kpi-value">₱<?= number_format($dashboardData['avg_order_value'], 2); ?></div>
                <div class="kpi-change">
                    <span>Per transaction</span>
                </div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-label">This Month Sales</div>
                <div class="kpi-value">₱<?= number_format($dashboardData['this_month_sales'], 2); ?></div>
                <div class="kpi-change <?= $dashboardData['mom_growth'] < 0 ? 'negative' : ''; ?>">
                    <span><?= $dashboardData['mom_growth'] >= 0 ? '↑' : '↓'; ?> <?= number_format(abs($dashboardData['mom_growth']), 2); ?>%</span>
                    <span>vs last month</span>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Sales Trend - Last 7 Days</h3>
                <div class="chart-wrapper">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Top 3 Products</h3>
                <div class="chart-wrapper">
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <h3>Detailed Reports</h3>
            <div class="links-grid">
                <div class="link-card">
                    <a href="/tgif_bi/modules/sales/reports/sales_performance_report.php">
                        📊 Sales Performance by Product
                    </a>
                    <p>View detailed product sales analysis with bar charts</p>
                </div>
                <div class="link-card">
                    <a href="/tgif_bi/modules/sales/reports/sales_summary_report.php">
                        📈 Sales Summary by Date
                    </a>
                    <p>Analyze sales trends over time with customizable date ranges</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dashboardData = <?= json_encode($dashboardData); ?>;

    // Sales Trend Chart (Last 7 Days)
    const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: dashboardData.last_7_days.labels,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: dashboardData.last_7_days.data,
                borderColor: 'rgba(46, 125, 50, 1)',
                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Top Products Chart
    const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
    const topProducts = dashboardData.top_products || [];
    
    if (topProducts.length > 0) {
        new Chart(topProductsCtx, {
            type: 'doughnut',
            data: {
                labels: topProducts.map(p => p.name),
                datasets: [{
                    data: topProducts.map(p => p.sales),
                    backgroundColor: [
                        'rgba(46, 125, 50, 0.8)',
                        'rgba(67, 160, 71, 0.8)',
                        'rgba(102, 187, 106, 0.8)'
                    ],
                    borderColor: [
                        'rgba(27, 94, 32, 1)',
                        'rgba(46, 125, 50, 1)',
                        'rgba(67, 160, 71, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ₱' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    } else {
        topProductsCtx.canvas.parentElement.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No product data available</p>';
    }
});
</script>
</body>
</html>
