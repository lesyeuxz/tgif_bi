<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /tgif_bi/index.html');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/tgif_bi/api/db_connect.php';
require_once __DIR__ . '/../services/InventoryReportService.php';
require_once __DIR__ . '/../helpers/ReportExporter.php';

$service        = new InventoryReportService($conn);
$filters        = $service->normalizeFilters($_GET);
$navLinks       = [];
$reportData     = $service->getStockTransactionsReport($filters);
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
        'stock_transactions',
        $conn,
        (int) $_SESSION['user_id'],
        array_merge($filters, ['report' => 'stock_transactions'])
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
            <span>Location</span>
            <select name="location">
                <option value="">All Locations</option>
                <?php foreach ($filterOptions['locations'] as $location): ?>
                    <option value="<?= htmlspecialchars($location['id']); ?>" <?= (string)($filters['location'] ?? '') === (string)$location['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($location['label']); ?>
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

$pageTitle        = 'Stock Transaction Reports';
$pageDescription  = 'Track every stock movement (IN/OUT) with references, quantities, and responsible users for accurate BI insights.';
$pageEyebrow      = 'Inventory Intelligence';
$tableTitle       = 'Stock Transaction Dataset';
$tableSubtitle    = 'Detailed stock movement log';
$tableColumns     = $reportData['columns'];
$tableRows        = $reportData['rows'];
$perPage          = $filters['per_page'];
$emptyState       = 'No stock transactions found for the selected criteria.';

include __DIR__ . '/../views/report_layout.php';

