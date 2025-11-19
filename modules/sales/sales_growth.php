<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}
require_once '../../api/db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Growth</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Sales Growth</h2>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Total Sales</th>
                <th>Growth (%)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Fetch monthly sales totals
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS month, SUM(total) AS total_sales
                FROM sales
                GROUP BY month
                ORDER BY month ASC";
        $result = $conn->query($sql);

        $previous = null;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $growth = ($previous !== null) ? (($row['total_sales'] - $previous) / $previous * 100) : 0;
                echo "<tr>
                    <td>{$row['month']}</td>
                    <td>" . number_format($row['total_sales'], 2) . "</td>
                    <td>" . number_format($growth, 2) . "%</td>
                </tr>";
                $previous = $row['total_sales'];
            }
        } else {
            echo "<tr><td colspan='3'>No sales data found.</td></tr>";
        }
        ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
