<?php
// Database connection for customer_support database (Sales Analytics)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "customer_support";

// First connect without selecting database to check if it exists
$conn_check = new mysqli($host, $user, $pass);

if ($conn_check->connect_error) {
  die("Connection failed: " . $conn_check->connect_error);
}

// Check if database exists
$result = $conn_check->query("SHOW DATABASES LIKE '{$db}'");
if ($result->num_rows == 0) {
  $conn_check->close();
  // Output proper HTML error page
  if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
  }
  ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Not Found - TGIF BI</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f7fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-container {
            max-width: 800px;
            background: #fff;
            border: 2px solid #e53935;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2 {
            color: #e53935;
            margin-top: 0;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }
        ol, ul {
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>Database Not Found</h2>
        <p><strong>The database 'customer_support' does not exist.</strong></p>
        <p>Please create the database and import the required tables. You can do this by:</p>
        <ol>
            <li>Open phpMyAdmin (<a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>)</li>
            <li>Create a new database named <code>customer_support</code></li>
            <li>Import the SQL structure provided by your groupmate, which should include:
                <ul>
                    <li><code>products</code> table</li>
                    <li><code>sales_orders</code> table</li>
                    <li><code>sales_order_items</code> table</li>
                </ul>
            </li>
            <li>Alternatively, you can use the setup file: <code>database/customer_support_setup.sql</code></li>
        </ol>
        <div class="info-box">
            <strong>Note:</strong> The Sales Analytics module requires the customer_support database to be set up by your groupmate who handles the Sales Module. Once the database is created and populated with data, refresh this page.
        </div>
    </div>
</body>
</html>
  <?php
  exit;
}

$conn_check->close();

// Now connect to the database
$conn_sales = new mysqli($host, $user, $pass, $db);

if ($conn_sales->connect_error) {
  die("Connection failed: " . $conn_sales->connect_error);
}

$conn_sales->set_charset('utf8mb4');
?>

