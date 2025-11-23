<?php
declare(strict_types=1);

function getInventoryReportNav(string $active = 'history'): array
{
    $base = '/tgif_bi/modules/inventory/';

    return [
        [
            'label'  => 'Inventory History',
            'url'    => $base . 'inventory_history.php',
            'active' => $active === 'history',
        ],
        [
            'label'  => 'Stock Transactions',
            'url'    => $base . 'reports/stock_transactions.php',
            'active' => $active === 'stock',
        ],
        [
            'label'  => 'Location Reports',
            'url'    => $base . 'reports/location_reports.php',
            'active' => $active === 'locations',
        ],
        [
            'label'  => 'Purchase Orders',
            'url'    => $base . 'reports/purchase_order_reports.php',
            'active' => $active === 'purchase_orders',
        ],
    ];
}

