<?php
$sanitize = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$recordsDisplayed = isset($tableRows) ? count($tableRows) : 0;
$perPageLimit = $perPage ?? 50;
$activeFilterCount = $activeFilterCount ?? 0;
$navLinks = $navLinks ?? [];
$filterFormHtml = $filterFormHtml ?? '';
$tableColumns = $tableColumns ?? [];
$tableRows = $tableRows ?? [];
$tableTitle = $tableTitle ?? $pageTitle ?? 'Inventory Report';
$pageEyebrow = $pageEyebrow ?? 'Inventory Intelligence';
$pageDescription = $pageDescription ?? '';
$emptyState = $emptyState ?? 'Try adjusting your filters or date range.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $sanitize($pageTitle ?? 'Inventory Reports'); ?></title>
    <link rel="stylesheet" href="/tgif_bi/assets/css/style.css">
    <link rel="stylesheet" href="/tgif_bi/assets/css/inventory-reports.css">
</head>
<body class="dashboard-body">

<?php
include $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/includes/sidebar.php';
include $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/includes/header.php';
?>

<main class="main-content inventory-reports">
    <div class="content-wrapper">
        <header class="reports-header">
            <div>
                <p class="eyebrow"><?= $sanitize($pageEyebrow); ?></p>
                <h1><?= $sanitize($pageTitle); ?></h1>
                <?php if ($pageDescription): ?>
                    <p class="subtext"><?= $sanitize($pageDescription); ?></p>
                <?php endif; ?>
            </div>
            <div class="stats-card">
                <span class="label">Records Displayed</span>
                <strong><?= $recordsDisplayed; ?></strong>
                <small>rows limited to <?= (int) $perPageLimit; ?> per view</small>
            </div>
        </header>

        <?php if (!empty($navLinks)): ?>
            <nav class="report-tabs">
                <?php foreach ($navLinks as $link): ?>
                    <a class="tab <?= !empty($link['active']) ? 'active' : ''; ?>" href="<?= $sanitize($link['url']); ?>">
                        <?= $sanitize($link['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <?php if ($filterFormHtml): ?>
            <section class="filters-panel">
                <?= $filterFormHtml; ?>
            </section>
        <?php endif; ?>

        <section class="report-table">
            <header>
                <div>
                    <p class="eyebrow"><?= $sanitize($tableTitle); ?></p>
                    <h2><?= $sanitize($tableSubtitle ?? 'Dataset'); ?></h2>
                </div>
                <div class="meta">
                    <span>Last refreshed: <?= date('M d, Y h:i A'); ?></span>
                    <span>|</span>
                    <span>Filters active: <?= $activeFilterCount; ?></span>
                </div>
            </header>

            <?php if (empty($tableRows)): ?>
                <div class="empty-state">
                    <h3>No records found</h3>
                    <p><?= $sanitize($emptyState); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($tableColumns as $column): ?>
                                    <th><?= $sanitize($column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableRows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?= $sanitize($value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script src="/tgif_bi/assets/js/inventory-reports.js"></script>
</body>
</html>

