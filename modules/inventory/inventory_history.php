<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/api/db_connect.php';
require_once __DIR__ . '/services/InventoryReportService.php';
require_once __DIR__ . '/helpers/ReportExporter.php';
require_once __DIR__ . '/helpers/ReportNavigation.php';

$service        = new InventoryReportService($conn);
$filters        = $service->normalizeFilters($_GET);
$navLinks       = getInventoryReportNav('history');
$reportData     = $service->getInventoryHistoryReport($filters);
$filterOptions  = $service->getFilterOptions();
$exportFormat   = isset($_GET['export']) ? strtolower((string) $_GET['export']) : null;

if ($exportFormat) {
    if (!in_array($exportFormat, ['pdf', 'excel'], true)) {
        http_response_code(400);
        echo 'Unsupported export format.';
        exit;
    }

    ReportExporter::export(
        $exportFormat,
        $reportData,
        'inventory_history',
        $conn,
        (int) $_SESSION['user_id'],
        array_merge($filters, ['report' => 'inventory_history'])
    );
}

ob_start();
?>
<form id="reportFilters" method="get" class="filters-form">
    <div class="filter-grid">
        <label>
            <span>Date From</span>
            <input type="date" name="from_date" value="<?= htmlspecialchars($filters['from_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            <span>Date To</span>
            <input type="date" name="to_date" value="<?= htmlspecialchars($filters['to_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <label>
            <span>Item</span>
            <select name="product_id">
                <option value="">All Products</option>
                <?php foreach ($filterOptions['products'] as $product): ?>
                    <option value="<?= htmlspecialchars($product['id']); ?>" <?= (string)($filters['product_id'] ?? '') === (string)$product['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($product['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Rows per page</span>
            <select name="per_page">
                <?php foreach ([25, 50, 100, 150, 200] as $limit): ?>
                    <option value="<?= $limit; ?>" <?= (int)$filters['per_page'] === $limit ? 'selected' : ''; ?>>
                        <?= $limit; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="filter-actions">
        <div class="quick-range">
            <span>Quick Range:</span>
            <button type="button" data-range="7">7D</button>
            <button type="button" data-range="30">30D</button>
            <button type="button" data-range="90">90D</button>
        </div>
        <div class="actions-right">
            <button type="submit" class="btn primary">Apply Filters</button>
            <button type="submit" name="export" value="excel" class="btn secondary">Export Excel</button>
            <button type="submit" name="export" value="pdf" class="btn outline">Export PDF</button>
        </div>
    </div>
</form>
<?php
$filterFormHtml = ob_get_clean();

$activeFilterCount = count(array_filter(
    $filters,
    static function ($value, $key) {
        if (in_array($key, ['per_page', 'page'], true)) {
            return false;
        }
        return $value !== null && $value !== '';
    },
    ARRAY_FILTER_USE_BOTH
));

$pageTitle        = 'Inventory History Reports';
$pageDescription  = 'Monitor every inventory adjustment with clear audit trails, timestamps, and responsible users.';
$pageEyebrow      = 'Inventory Intelligence';
$tableTitle       = 'Inventory History Dataset';
$tableSubtitle    = 'Historical log of inventory actions';
$tableColumns     = $reportData['columns'];
$tableRows        = $reportData['rows'];
$perPage          = $filters['per_page'];
$emptyState       = 'No inventory history records match your filters.';

include __DIR__ . '/views/report_layout.php';

