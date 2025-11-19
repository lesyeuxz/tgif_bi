<?php
// login.php
session_start();
require_once 'db_connect.php'; // adjust path if needed

// Check if form data is sent
if (!isset($_POST['username'], $_POST['password'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Prepare statement to avoid SQL injection
$stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    header('Location: /tgif_bi/index.html');
    exit;
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $storedHash = $user['password'];
    $passwordOk = false;

    // 1) If stored hash is from password_hash()
    if (password_verify($password, $storedHash)) {
        $passwordOk = true;
    } 
    // 2) Support legacy MD5 hashed passwords
    elseif (md5($password) === $storedHash) {
        $passwordOk = true;

        // Upgrade password to password_hash()
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        if ($up) {
            $up->bind_param('si', $newHash, $user['user_id']);
            $up->execute();
            $up->close();
        }
    }

    if ($passwordOk) {
        // Login successful: set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to dashboard (absolute path)
        header('Location: /tgif_bi/dashboard/dashboard.php');
        exit;
    }
}

// Invalid credentials
echo "<script>alert('Invalid username or password'); window.location.href='/tgif_bi/index.html';</script>";

// Cleanup
$stmt->close();
$conn->close();
exit;
?>
