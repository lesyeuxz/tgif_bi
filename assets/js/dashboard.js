// === TGIF BI DASHBOARD SCRIPT ===
// Loads live KPI data and handles charts & navigation

document.addEventListener("DOMContentLoaded", async () => {
  try {
    // === Fetch BI Summary Data ===
    const res = await fetch("api/bi_summary.php");
    const data = await res.json();

    // === Update KPI Cards ===
    document.getElementById("salesValue").textContent =
      "₱" + parseFloat(data.total_sales || 0).toLocaleString();
    document.getElementById("profitValue").textContent =
      "₱" + parseFloat(data.gross_profit || 0).toLocaleString();
    document.getElementById("transactionsValue").textContent =
      data.total_transactions || 0;

    console.log("✅ BI Summary Loaded:", data);

    // === Overview Sales Chart (Static for Dashboard Overview) ===
    const salesCtx = document.getElementById("salesChart").getContext("2d");
    new Chart(salesCtx, {
      type: "line",
      data: {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri"],
        datasets: [
          {
            label: "Sales (₱)",
            data: [
              10000,
              12000,
              9000,
              15000,
              parseFloat(data.total_sales || 17000),
            ],
            borderColor: "#2e7d32",
            backgroundColor: "rgba(46,125,50,0.2)",
            fill: true,
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } },
      },
    });

    // === Inventory Report Chart (Static Sample) ===
    const invCtx = document.getElementById("inventoryChart").getContext("2d");
    new Chart(invCtx, {
      type: "bar",
      data: {
        labels: ["Potatoes", "Flavoring", "Ice Cubes", "Cooking Oil", "Water"],
        datasets: [
          {
            label: "Stock Level",
            data: [100, 30, 50, 40, 70],
            backgroundColor: [
              "#2e7d32",
              "#66bb6a",
              "#a5d6a7",
              "#81c784",
              "#43a047",
            ],
          },
        ],
      },
      options: { scales: { y: { beginAtZero: true } } },
    });

    // === Load Dynamic Sales Report Chart ===
    loadSalesTrend();

  } catch (error) {
    console.error("❌ Error loading BI data:", error);
  }
});

// === Sidebar Navigation ===
function showSection(id) {
  document.querySelectorAll(".section").forEach((sec) =>
    sec.classList.remove("active")
  );
  document.getElementById(id).classList.add("active");

  document.querySelectorAll(".nav-item").forEach((item) =>
    item.classList.remove("active")
  );
  event.target.classList.add("active");

  // Load dynamic data when switching to Sales Report
  if (id === "sales") {
    loadSalesTrend();
  }
}

// === Dynamic Sales Trend (Real DB Data) ===
async function loadSalesTrend() {
  try {
    const res = await fetch("api/sales_report.php");
    const data = await res.json();

    if (!data || data.length === 0) {
      console.warn("⚠️ No sales data found in database.");
      return;
    }

    const labels = data.map((item) => item.date);
    const values = data.map((item) => parseFloat(item.total_sales));

    const ctx = document.getElementById("salesTrend").getContext("2d");

    // Destroy existing chart if reloaded
    if (window.salesTrendChart) window.salesTrendChart.destroy();

    window.salesTrendChart = new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            label: "Total Sales (₱)",
            data: values,
            borderColor: "#2e7d32",
            backgroundColor: "rgba(46,125,50,0.2)",
            fill: true,
            tension: 0.3,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } },
      },
    });

    console.log("📈 Sales Trend Chart Loaded:", data);
  } catch (err) {
    console.error("❌ Error loading sales report:", err);
  }
}

// === Custom Report Generator ===
let customChart;
function generateCustomReport() {
  const ctx = document.getElementById("customChart").getContext("2d");
  if (customChart) customChart.destroy();

  const type = document.getElementById("reportType").value;

  if (type === "sales") {
    customChart = new Chart(ctx, {
      type: "line",
      data: {
        labels: ["Jun", "Jul", "Aug", "Sep", "Oct"],
        datasets: [
          {
            label: "Custom Sales (₱)",
            data: [17000, 21000, 19000, 22000, 25000],
            borderColor: "#ffb300",
            backgroundColor: "rgba(255,179,0,0.2)",
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
      },
    });
  } else {
    customChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Cups", "Lids", "Tissues", "Gloves"],
        datasets: [
          {
            label: "Custom Stock",
            data: [1000, 950, 870, 600],
            backgroundColor: [
              "#81c784",
              "#66bb6a",
              "#43a047",
              "#2e7d32",
            ],
          },
        ],
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
      },
    });
  }
}
// === EXPORT REPORT FUNCTION ===
function exportReport(format) {
  window.open(`api/export_report.php?format=${format}`, "_blank");
}
