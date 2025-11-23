<!-- includes/sidebar.php -->
<aside class="sidebar">
  <div class="logo-section">
    <img src="/tgif_bi/assets/images/logo.png" alt="TGIF Logo" class="logo">
    <h2>TGIF BI</h2>
  </div>

  <ul class="menu">
    <!-- Dashboard -->
    <li>
      <a href="/tgif_bi/dashboard/dashboard.php">
        <i class="icon">📊</i>
        <span>Dashboard</span>
      </a>
    </li>

    <!-- Sales Analytics -->
    <li class="menu-section">Sales Analytics</li>
    <li><a href="/tgif_bi/modules/sales/sales_overview.php">Sales Overview</a></li>
    <li><a href="/tgif_bi/modules/sales/sales_growth.php">Sales Growth</a></li>
    <li><a href="/tgif_bi/modules/sales/reports/sales_performance_report.php">Sales Performance</a></li>
    <li><a href="/tgif_bi/modules/sales/reports/sales_summary_report.php">Sales Summary</a></li>

    <!-- Inventory Insights -->
    <li class="menu-section">Inventory Insights</li>
    <li><a href="/tgif_bi/modules/inventory/inventory_history.php">Inventory History</a></li>
    <li><a href="/tgif_bi/modules/inventory/reports/stock_transactions.php">Stock Transactions</a></li>
    <li><a href="/tgif_bi/modules/inventory/reports/location_reports.php">Location Reports</a></li>
    <li><a href="/tgif_bi/modules/inventory/reports/purchase_order_reports.php">Purchase Orders</a></li>

    <!-- Financial Analytics -->
    <li class="menu-section">Financial Analytics</li>
    <li><a href="/tgif_bi/modules/finance/profit_loss.php">Profit & Loss</a></li>
    <li><a href="/tgif_bi/modules/finance/expense_breakdown.php">Expense Breakdown</a></li>
    <li><a href="/tgif_bi/modules/finance/cashflow_summary.php">Cash Flow Summary</a></li>

    <!-- Administration -->
    <li class="menu-section">Administration</li>
    <li><a href="/tgif_bi/modules/admin/manage_reports.php">Manage Reports</a></li>
    <li><a href="/tgif_bi/modules/admin/user_logs.php">User Activity Logs</a></li>
    <li><a href="/tgif_bi/modules/admin/system_settings.php">System Settings</a></li>

    <!-- Logout -->
    <li>
      <a href="/tgif_bi/logout.php" class="logout">
        <i class="icon">🚪</i>
        <span>Logout</span>
      </a>
    </li>
  </ul>
</aside>

<!-- SIDEBAR STYLES -->
<style>
.sidebar {
  width: 260px;
  height: 100vh;
  background: #1b5e20;
  color: #f8fafc;
  position: fixed;
  left: 0;
  top: 0;
  display: flex;
  flex-direction: column;
  padding: 20px;
  z-index: 999;
  overflow-y: auto;
  transition: transform 0.3s ease;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.logo {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}

.sidebar h2 {
  font-size: 1.2rem;
  font-weight: 600;
}

.menu {
  list-style: none;
  padding: 0;
  margin: 0;
  flex-grow: 1;
}

.menu li {
  margin: 6px 0;
}

.menu-section {
  font-size: 0.85rem;
  font-weight: 700;
  color: #94a3b8;
  margin-top: 20px;
  text-transform: uppercase;
}

.menu a {
  display: block;
  color: #e2e8f0;
  text-decoration: none;
  padding: 10px 12px;
  border-radius: 8px;
  transition: background 0.2s;
}

.menu a:hover {
  background: #2e7d32;
}

.logout {
  color: #f87171;
}

.icon {
  margin-right: 8px;
}

/* Mobile Menu Toggle Button */
.menu-toggle {
  display: none;
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 1001;
  background: #1b5e20;
  color: white;
  border: none;
  padding: 10px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.2rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Responsive Sidebar */
@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }
  
  .sidebar {
    transform: translateX(-100%);
    width: 260px;
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
  
  .logo-section {
    margin-bottom: 15px;
  }
  
  .sidebar h2 {
    font-size: 1.1rem;
  }
  
  .menu a {
    padding: 8px 10px;
    font-size: 0.9rem;
  }
}

@media (max-width: 480px) {
  .sidebar {
    width: 240px;
    padding: 15px;
  }
  
  .logo {
    width: 35px;
    height: 35px;
  }
  
  .sidebar h2 {
    font-size: 1rem;
  }
  
  .menu-section {
    font-size: 0.75rem;
    margin-top: 15px;
  }
  
  .menu a {
    padding: 7px 8px;
    font-size: 0.85rem;
  }
}
</style>

<script>
// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const menuToggle = document.createElement('button');
  menuToggle.className = 'menu-toggle';
  menuToggle.innerHTML = '☰';
  menuToggle.setAttribute('aria-label', 'Toggle menu');
  document.body.appendChild(menuToggle);
  
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.createElement('div');
  overlay.style.cssText = 'display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998;';
  document.body.appendChild(overlay);
  
  menuToggle.addEventListener('click', function() {
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
  });
  
  overlay.addEventListener('click', function() {
    sidebar.classList.remove('active');
    overlay.style.display = 'none';
  });
  
  // Close menu when clicking a link on mobile
  if (window.innerWidth <= 768) {
    const menuLinks = sidebar.querySelectorAll('.menu a');
    menuLinks.forEach(link => {
      link.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.style.display = 'none';
      });
    });
  }
});
</script>
