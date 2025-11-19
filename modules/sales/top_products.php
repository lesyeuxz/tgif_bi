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
<title>Top Products</title>
<link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
</head>
<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT'].'/tgif_bi/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <h2>Top Products</h2>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Total Quantity Sold</th>
                <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT p.product_name, SUM(s.quantity) AS total_quantity, SUM(s.total) AS total_revenue
                FROM sales s
                LEFT JOIN products p ON s.product_id = p.product_id
                GROUP BY s.product_id
                ORDER BY total_quantity DESC";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['product_name']}</td>
                    <td>{$row['total_quantity']}</td>
                    <td>" . number_format($row['total_revenue'], 2) . "</td>
                </tr>";
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
