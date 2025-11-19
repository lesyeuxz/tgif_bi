<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once '../../api/db_connect.php';

/* ========================================================
   DELETE REPORT (DB row + physical file)
======================================================== */
if (isset($_GET['delete'])) {

    $report_id = intval($_GET['delete']);

    $getFile = $conn->query("SELECT file_path FROM reports_generated WHERE report_id = $report_id");

    if ($getFile && $getFile->num_rows > 0) {
        $file = $getFile->fetch_assoc()['file_path'];
        $abs = $_SERVER['DOCUMENT_ROOT'] . $file;

        if ($file && file_exists($abs)) {
            @unlink($abs);
        }

        $conn->query("DELETE FROM reports_generated WHERE report_id = $report_id");
    }

    header("Location: manage_reports.php?msg=deleted");
    exit;
}

/* ========================================================
   FILTERS
======================================================== */
$conditions = [];

if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    $conditions[] = "r.report_name LIKE '%$s%'";
}

if (!empty($_GET['module'])) {
    $m = $conn->real_escape_string($_GET['module']);
    $conditions[] = "r.module = '$m'";
}

if (!empty($_GET['type'])) {
    $t = $conn->real_escape_string($_GET['type']);
    $conditions[] = "r.export_type = '$t'";
}

if (!empty($_GET['from'])) {
    $from = $conn->real_escape_string($_GET['from']);
    $conditions[] = "DATE(r.generated_on) >= '$from'";
}

if (!empty($_GET['to'])) {
    $to = $conn->real_escape_string($_GET['to']);
    $conditions[] = "DATE(r.generated_on) <= '$to'";
}

$where = "";
if (count($conditions) > 0) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

/* ========================================================
   MAIN QUERY
======================================================== */
$sql = "
    SELECT r.*, u.full_name
    FROM reports_generated r
    LEFT JOIN users u ON r.generated_by = u.user_id
    $where
    ORDER BY r.generated_on DESC
";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Reports</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

<style>
.main-content {
    margin-left:260px;
    padding:30px;
}

/* Filter Bar */
.filter-bar {
    background:#fff;
    padding:12px;
    border-radius:8px;
    display:flex;
    gap:10px;
    align-items:center;
    margin-bottom:18px;
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
}
.filter-bar input, .filter-bar select {
    padding:8px;
    border-radius:6px;
    border:1px solid #cfd8dc;
}
.filter-bar button {
    background:#2e7d32;
    color:#fff;
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
}
.reset-btn {
    background:#c62828;
    color:#fff;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
}

/* Table */
.reports-table {
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 6px 18px rgba(0,0,0,0.05);
}
.reports-table th {
    background:#2e7d32;
    color:#fff;
    padding:12px;
}
.reports-table td {
    padding:12px;
    border-bottom:1px solid #eee;
    color:#1b5e20;
}

/* Buttons */
.action-btn {
    padding:6px 10px;
    border-radius:6px;
    color:#fff !important;
    text-decoration:none;
    font-size:0.9rem;
}
.download-btn { background:#1b5e20; }
.delete-btn { background:#c62828; }

.msg {
    padding:10px;
    background:#e6ffe6;
    border-left:4px solid #2e7d32;
    color:#1b5e20;
    margin-bottom:15px;
}

/* Icons */
.btn-icon {
    margin-right:6px;
}
</style>

</head>

<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">

    <h2>Manage Reports</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg']=="deleted"): ?>
        <div class="msg">Report deleted successfully.</div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <form method="get" action="manage_reports.php" class="filter-bar">

        <input type="text" name="search" placeholder="Search name..."
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

        <select name="module">
            <option value="">All Modules</option>
            <option value="sales"     <?= (($_GET['module'] ?? '')=='sales')?'selected':'' ?>>Sales</option>
            <option value="inventory" <?= (($_GET['module'] ?? '')=='inventory')?'selected':'' ?>>Inventory</option>
            <option value="finance"   <?= (($_GET['module'] ?? '')=='finance')?'selected':'' ?>>Finance</option>
            <option value="admin"     <?= (($_GET['module'] ?? '')=='admin')?'selected':'' ?>>Admin</option>
        </select>

        <select name="type">
            <option value="">All Types</option>
            <option value="PDF"   <?= (($_GET['type'] ?? '')=='PDF')?'selected':'' ?>>PDF</option>
            <option value="Excel" <?= (($_GET['type'] ?? '')=='Excel')?'selected':'' ?>>Excel</option>
            <option value="CSV"   <?= (($_GET['type'] ?? '')=='CSV')?'selected':'' ?>>CSV</option>
        </select>

        <input type="date" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>">
        <input type="date" name="to"   value="<?= htmlspecialchars($_GET['to'] ?? '') ?>">

        <button type="submit">Filter</button>
        <a href="manage_reports.php" class="reset-btn">Reset</a>

    </form>

    <!-- REPORTS TABLE -->
    <table class="reports-table">
        <thead>
            <tr>
                <th>Report Name</th>
                <th>Module</th>
                <th>Type</th>
                <th>Generated By</th>
                <th>Date</th>
                <th>Download</th>
                <th>Delete</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($r = $result->fetch_assoc()): ?>

                <tr>
                    <td><?= htmlspecialchars($r['report_name']); ?></td>
                    <td><?= ucfirst(htmlspecialchars($r['module'])); ?></td>
                    <td><?= htmlspecialchars($r['export_type']); ?></td>
                    <td><?= htmlspecialchars($r['full_name']); ?></td>
                    <td><?= date("M d, Y h:i A", strtotime($r['generated_on'])); ?></td>

                    <td>
                        <a class="action-btn download-btn"
                           href="<?= htmlspecialchars($r['file_path']); ?>"
                           download>
                           <span class="btn-icon">⬇</span> Download
                        </a>
                    </td>

                    <td>
                        <a class="action-btn delete-btn"
                           href="?delete=<?= $r['report_id']; ?>"
                           onclick="return confirm('Delete this report?');">
                           <span class="btn-icon">🗑</span> Delete
                        </a>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>
            <tr><td colspan="7" style="padding:15px;">No reports found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
