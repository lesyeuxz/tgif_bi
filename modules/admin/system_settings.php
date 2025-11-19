<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /tgif_bi/index.php");
    exit;
}

require_once "../../api/db_connect.php";

/* ==========================================================
   Ensure settings row exists
========================================================== */
$check = $conn->query("SELECT * FROM system_settings LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO system_settings (business_name) VALUES ('TGIF Business')");
}
$settings = $check->fetch_assoc();

/* ==========================================================
   HANDLE FORM SUBMISSION
========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $business_name   = $_POST['business_name'] ?? '';
    $business_email  = $_POST['business_email'] ?? '';
    $business_phone  = $_POST['business_phone'] ?? '';
    $default_format  = $_POST['default_export_format'] ?? 'PDF';
    $include_logo    = isset($_POST['include_logo']) ? 1 : 0;
    $session_timeout = intval($_POST['session_timeout'] ?? 30);
    $enable_admin    = isset($_POST['enable_admin_tools']) ? 1 : 0;

    /* ---- Handle Logo Upload ---- */
    $logo_path = $settings['logo_path'];
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . "/tgif_bi/assets/img/";

    if (!empty($_FILES['logo']['name'])) {
        $filename = "logo.png";  // overwrite old logo
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $logo_path = "/tgif_bi/assets/img/" . $filename;
        }
    }

    /* ---- UPDATE SQL ---- */
    $stmt = $conn->prepare("
        UPDATE system_settings SET
            business_name = ?,
            business_email = ?,
            business_phone = ?,
            logo_path = ?,
            default_export_format = ?,
            include_logo = ?,
            session_timeout = ?,
            enable_admin_tools = ?,
            updated_at = NOW()
        WHERE setting_id = 1
    ");

    $stmt->bind_param(
        "sssssiis",
        $business_name,
        $business_email,
        $business_phone,
        $logo_path,
        $default_format,
        $include_logo,
        $session_timeout,
        $enable_admin,
    );

    $stmt->execute();
    header("Location: system_settings.php?updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>System Settings</title>
    <link rel="stylesheet" href="/tgif_bi/assets/css/style.css">

    <style>
        .settings-card {
            margin-left:260px;
            margin-top:20px;
            background:#fff;
            padding:25px;
            width:650px;
            border-radius:12px;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
        }
        .settings-card h2 {
            color:#1b5e20;
            margin-bottom:20px;
        }
        .settings-card label {
            font-weight:600;
            color:#1b5e20;
        }
        .settings-card input, 
        .settings-card select {
            width:100%;
            padding:8px;
            margin-bottom:12px;
            border-radius:6px;
            border:1px solid #cfd8dc;
        }
        .settings-card button {
            background:#2e7d32;
            color:white;
            padding:10px 18px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:600;
        }
        .settings-card button:hover {
            background:#1b5e20;
        }
        .success {
            background:#e8f5e9;
            border-left:5px solid #2e7d32;
            padding:10px;
            margin-bottom:15px;
            color:#1b5e20;
            border-radius:8px;
            width:100%;
        }
    </style>
</head>

<body class="dashboard-body">

<?php include $_SERVER['DOCUMENT_ROOT']."/tgif_bi/includes/sidebar.php"; ?>

<div class="settings-card">
    
    <h2>System Settings</h2>

    <?php if (!empty($_GET['updated'])): ?>
        <div class="success">Settings updated successfully.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Business Name</label>
        <input type="text" name="business_name" 
            value="<?= htmlspecialchars($settings['business_name']); ?>">

        <label>Business Email</label>
        <input type="email" name="business_email" 
            value="<?= htmlspecialchars($settings['business_email']); ?>">

        <label>Business Phone</label>
        <input type="text" name="business_phone" 
            value="<?= htmlspecialchars($settings['business_phone']); ?>">

        <label>Upload Logo</label>
        <input type="file" name="logo" accept="image/*">
        <?php if (!empty($settings['logo_path'])): ?>
            <p style="font-size:12px;color:#2e7d32;">Current: <?= $settings['logo_path']; ?></p>
        <?php endif; ?>

        <label>Default Export Format</label>
        <select name="default_export_format">
            <option value="PDF"  <?= $settings['default_export_format']=="PDF" ? "selected" : "" ?>>PDF</option>
            <option value="Excel" <?= $settings['default_export_format']=="Excel" ? "selected" : "" ?>>Excel</option>
            <option value="CSV"   <?= $settings['default_export_format']=="CSV" ? "selected" : "" ?>>CSV</option>
        </select>

        <label>
            <input type="checkbox" name="include_logo" 
                <?= $settings['include_logo'] ? "checked" : "" ?>>
            Include logo in reports
        </label><br><br>

        <label>Session Timeout (minutes)</label>
        <input type="number" name="session_timeout" min="1" 
            value="<?= $settings['session_timeout']; ?>">

        <label>
            <input type="checkbox" name="enable_admin_tools"
                <?= $settings['enable_admin_tools'] ? "checked" : "" ?>>
            Enable Admin Tools
        </label>

        <br><br>
        <button type="submit">Save Settings</button>

    </form>
</div>

</body>
</html>
