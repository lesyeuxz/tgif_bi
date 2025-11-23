<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.html");
  exit;
}

// Include reusable sidebar and header
include '../includes/sidebar.php';
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TGIF BI Dashboard</title>

  <!-- Chart.js Library -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Custom Styles -->
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <link rel="stylesheet" href="../assets/css/style.css" />
  
  <style>
    /* Responsive chart container */
    .chart-container {
      background: #ffffff;
      border-radius: 8px;
      padding: 20px;
      margin-top: 15px;
      margin-bottom: 15px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      overflow-x: auto;
    }
    
    #salesChart {
      max-width: 100%;
      height: auto !important;
    }
    
    @media (max-width: 768px) {
      .chart-container {
        padding: 15px;
      }
      
      #salesChart {
        max-width: 100%;
      }
    }
    
    @media (max-width: 480px) {
      .chart-container {
        padding: 10px;
      }
      
      .chart-container h3 {
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>
  <!-- ===== MAIN CONTAINER ===== -->
  <div class="main-content dashboard-main">

    <!-- === OVERVIEW SECTION === -->
    <section id="overview" class="section active">
      <h2>📊 TGIF BI Overview</h2>
      <p>Business performance summary — total sales, gross profit, and transactions overview.</p>

      <!-- KPI CARDS -->
      <div class="dashboard-cards">
        <div class="card">
          <div class="card-title">Total Sales</div>
          <div class="card-value" id="salesValue">₱0.00</div>
        </div>
        <div class="card">
          <div class="card-title">Gross Profit</div>
          <div class="card-value" id="profitValue">₱0.00</div>
        </div>
        <div class="card">
          <div class="card-title">Transactions</div>
          <div class="card-value" id="transactionsValue">0</div>
        </div>
      </div>

      <!-- SALES OVERVIEW CHART -->
      <div class="chart-container">
        <h3>Sales Overview (Real-Time)</h3>
        <canvas id="salesChart" height="180"></canvas>
      </div>

<script>
// ======== REAL-TIME DASHBOARD UPDATES ========

// Chart.js initialization
const ctx = document.getElementById("salesChart").getContext("2d");
let salesChart = new Chart(ctx, {
  type: "line",
  data: {
    labels: [],
    datasets: [{
      label: "Weekly Sales (₱)",
      data: [],
      borderColor: "#4e73df",
      backgroundColor: "rgba(78,115,223,0.1)",
      tension: 0.3,
      fill: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    scales: {
      y: { beginAtZero: true }
    },
    plugins: {
      legend: {
        display: true,
        position: 'top'
      }
    }
  }
});

// Function to fetch data from backend
async function updateDashboard() {
  try {
    const response = await fetch("../api/get_sales_data.php");
    const data = await response.json();

    // Update KPI values
    document.getElementById("salesValue").textContent = "₱" + parseFloat(data.total_sales).toLocaleString();
    document.getElementById("profitValue").textContent = "₱" + parseFloat(data.gross_profit).toLocaleString();
    document.getElementById("transactionsValue").textContent = data.transactions;

    // Update chart
    salesChart.data.labels = data.labels;
    salesChart.data.datasets[0].data = data.sales;
    salesChart.update();

  } catch (error) {
    console.error("Error updating dashboard:", error);

}

// Initial load
updateDashboard();

// Auto-refresh every 10 seconds
setInterval(updateDashboard, 10000);
</script>

    <!-- === SALES SECTION === -->
    <section id="sales" class="section">
      <h2>📈 Sales Report</h2>
      <p>Detailed weekly sales trend visualization.</p>
      <canvas id="salesTrend" height="200"></canvas>
    </section>

    <!-- === INVENTORY SECTION === -->
    <section id="inventory" class="section">
      <h2>📦 Inventory Status</h2>
      <p>View stock levels and supply trends.</p>
      <canvas id="inventoryChart" height="200"></canvas>
    </section>

    <!-- === CUSTOM REPORT SECTION === -->
    <section id="custom" class="section">
      <h2>🧾 Custom Report Generator</h2>
      <p>Select a report type to visualize custom data.</p>

      <div class="form-grid" style="max-width: 400px; margin-bottom: 1rem;">
        <select id="reportType">
          <option value="sales">Sales</option>
          <option value="inventory">Inventory</option>
        </select>
        <button class="btn btn-primary" onclick="generateCustomReport()">Generate</button>
      </div>

      <div style="margin-top: 1rem;">
        <button class="btn btn-success" onclick="exportReport('csv')">Export CSV</button>
        <button class="btn btn-primary" onclick="exportReport('excel')">Export Excel</button>
        <button class="btn btn-warning" onclick="exportReport('pdf')">Export PDF</button>
      </div>

      <canvas id="customChart" height="200"></canvas>
    </section>
  </div>

  <!-- ===== JAVASCRIPT ===== -->
  <script src="../assets/js/dashboard.js"></script>
</body>
</html>
