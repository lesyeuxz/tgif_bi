<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/ReportFilters.php';

class InventoryReportService
{
    public const REPORT_TYPES = [
        'history'          => 'Inventory History Reports',
        'stock'            => 'Stock Transaction Reports',
        'locations'        => 'Location Reports',
        'purchase_orders'  => 'Purchase Order Reports',
    ];

    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
    }

    public function getReportTypes(): array
    {
        return self::REPORT_TYPES;
    }

    public function normalizeFilters(array $input): array
    {
        return ReportFilters::normalize($input);
    }

    public function getFilterOptions(): array
    {
        $locationSql = "
            SELECT DISTINCT location AS id, location AS label
            FROM inventory_location_history
            WHERE location IS NOT NULL AND location != ''
            UNION
            SELECT DISTINCT location AS id, location AS label
            FROM stock_transactions
            WHERE location IS NOT NULL AND location != ''
            ORDER BY id ASC
        ";

        return [
            'products'  => $this->fetchOptions("SELECT product_id AS id, product_name AS label FROM products ORDER BY product_name ASC"),
            'locations' => $this->fetchOptions($locationSql),
            'suppliers' => $this->fetchOptions("SELECT supplier_id AS id, supplier_name AS label FROM suppliers ORDER BY supplier_name ASC"),
        ];
    }

    public function getReport(string $type, array $filters): array
    {
        return match ($type) {
            'stock'           => $this->getStockTransactionsReport($filters),
            'locations'       => $this->getLocationReport($filters),
            'purchase_orders' => $this->getPurchaseOrderReport($filters),
            default           => $this->getInventoryHistoryReport($filters),
        };
    }

    public function getInventoryHistoryReport(array $filters): array
    {
        return $this->buildInventoryHistoryReport($filters);
    }

    public function getStockTransactionsReport(array $filters): array
    {
        return $this->buildStockTransactionsReport($filters);
    }

    public function getLocationReport(array $filters): array
    {
        return $this->buildLocationReport($filters);
    }

    public function getPurchaseOrderReport(array $filters): array
    {
        return $this->buildPurchaseOrderDetailReport($filters);
    }

    private function buildInventoryHistoryReport(array $filters): array
    {
        $sql = "
            SELECT ih.history_id,
                   ih.product_id,
                   p.product_name,
                   ih.action,
                   ih.quantity,
                   ih.remarks,
                   COALESCE(u.username, 'System') AS changed_by,
                   ih.created_at
            FROM inventory_history ih
            INNER JOIN products p ON ih.product_id = p.product_id
            LEFT JOIN users u ON ih.changed_by = u.user_id
            WHERE 1=1
        ";

        $bindings = $this->buildCommonProductFilters($filters, 'ih');
        $sql     .= $bindings['clause'];

        $sql .= " ORDER BY ih.created_at DESC LIMIT ?";
        $bindings['types'] .= 'i';
        $bindings['params'][] = $filters['per_page'];

        $rows = $this->fetchRows($sql, $bindings['types'], $bindings['params'], function ($row) {
            return [
                'id'          => $row['history_id'],
                'item'        => $row['product_name'],
                'action'      => ucfirst($row['action']),
                'quantity'    => number_format((float)$row['quantity'], 0),
                'remarks'     => $row['remarks'] ?? '—',
                'changed_by'  => $row['changed_by'],
                'created_at'  => date('Y-m-d H:i:s', strtotime($row['created_at'])),
            ];
        });

        return [
            'key'     => 'history',
            'title'   => self::REPORT_TYPES['history'],
            'columns' => ['History #','Item','Action','Quantity','Remarks','Changed By','Date/Time'],
            'rows'    => $rows,
        ];
    }

    private function buildStockTransactionsReport(array $filters): array
    {
        $sql = "
            SELECT st.transaction_id,
                   st.product_id,
                   p.product_name,
                   st.transaction_type,
                   st.quantity,
                   st.reference_no,
                   st.location,
                   COALESCE(u.username, 'System') AS performed_by,
                   st.created_at
            FROM stock_transactions st
            INNER JOIN products p ON st.product_id = p.product_id
            LEFT JOIN users u ON st.performed_by = u.user_id
            WHERE 1=1
        ";

        $bindings = $this->buildCommonProductFilters($filters, 'st');

        if (!empty($filters['location'])) {
            $sql .= " AND st.location = ?";
            $bindings['types'] .= 's';
            $bindings['params'][] = $filters['location'];
        }

        $sql .= " ORDER BY st.created_at DESC LIMIT ?";
        $bindings['types'] .= 'i';
        $bindings['params'][] = $filters['per_page'];

        $rows = $this->fetchRows($sql, $bindings['types'], $bindings['params'], function ($row) {
            return [
                'id'           => $row['transaction_id'],
                'item'         => $row['product_name'],
                'type'         => $row['transaction_type'],
                'quantity'     => number_format((float)$row['quantity'], 2),
                'reference'    => $row['reference_no'] ?? '—',
                'location'     => $row['location'] ?? '—',
                'performed_by' => $row['performed_by'],
                'created_at'   => date('Y-m-d H:i:s', strtotime($row['created_at'])),
            ];
        });

        return [
            'key'     => 'stock',
            'title'   => self::REPORT_TYPES['stock'],
            'columns' => ['Txn #','Item','Type','Quantity','Reference','Location','User','Date/Time'],
            'rows'    => $rows,
        ];
    }

    private function buildLocationReport(array $filters): array
    {
        $sql = "
            SELECT ilh.movement_id,
                   ilh.product_id,
                   p.product_name,
                   ilh.location,
                   ilh.storage_area,
                   ilh.remarks,
                   COALESCE(u.username, 'System') AS assigned_by,
                   ilh.movement_date
            FROM inventory_location_history ilh
            INNER JOIN products p ON ilh.product_id = p.product_id
            LEFT JOIN users u ON ilh.assigned_by = u.user_id
            WHERE 1=1
        ";

        $bindings = [
            'types'  => '',
            'params' => [],
            'clause' => '',
        ];

        if (!empty($filters['product_id'])) {
            $bindings['clause'] .= " AND ilh.product_id = ?";
            $bindings['types'] .= 'i';
            $bindings['params'][] = $filters['product_id'];
        }

        if (!empty($filters['location'])) {
            $bindings['clause'] .= " AND ilh.location = ?";
            $bindings['types'] .= 's';
            $bindings['params'][] = $filters['location'];
        }

        $dateClause = $this->buildDateClause($filters, 'ilh.movement_date');
        $bindings['clause'] .= $dateClause['clause'];
        $bindings['types'] .= $dateClause['types'];
        $bindings['params'] = array_merge($bindings['params'], $dateClause['params']);

        $sql .= $bindings['clause'];
        $sql .= " ORDER BY ilh.movement_date DESC LIMIT ?";
        $bindings['types'] .= 'i';
        $bindings['params'][] = $filters['per_page'];

        $rows = $this->fetchRows($sql, $bindings['types'], $bindings['params'], function ($row) {
            return [
                'id'           => $row['movement_id'],
                'item'         => $row['product_name'],
                'location'     => $row['location'],
                'area'         => $row['storage_area'] ?? '—',
                'remarks'      => $row['remarks'] ?? '—',
                'assigned_by'  => $row['assigned_by'],
                'movement'     => date('Y-m-d H:i:s', strtotime($row['movement_date'])),
            ];
        });

        return [
            'key'     => 'locations',
            'title'   => self::REPORT_TYPES['locations'],
            'columns' => ['Move #','Item','Location','Storage Area','Remarks','Assigned By','Movement Date'],
            'rows'    => $rows,
        ];
    }

    private function buildPurchaseOrderDetailReport(array $filters): array
    {
        $sql = "
            SELECT po.po_id,
                   po.order_date,
                   po.status,
                   s.supplier_name,
                   COALESCE(u.username, 'System') AS created_by,
                   p.product_name,
                   poi.quantity,
                   poi.unit_cost,
                   (poi.quantity * poi.unit_cost) AS line_total
            FROM purchase_orders po
            INNER JOIN suppliers s ON po.supplier_id = s.supplier_id
            LEFT JOIN users u ON po.created_by = u.user_id
            INNER JOIN purchase_order_items poi ON po.po_id = poi.po_id
            INNER JOIN products p ON poi.product_id = p.product_id
            WHERE 1=1
        ";

        $bindings = [
            'types'  => '',
            'params' => [],
            'clause' => '',
        ];

        if (!empty($filters['supplier_id'])) {
            $sql .= " AND po.supplier_id = ?";
            $bindings['types'] .= 'i';
            $bindings['params'][] = $filters['supplier_id'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND poi.product_id = ?";
            $bindings['types'] .= 'i';
            $bindings['params'][] = $filters['product_id'];
        }

        // Handle date field (not datetime) for purchase orders
        if (!empty($filters['from_date'])) {
            $sql .= " AND po.order_date >= ?";
            $bindings['types'] .= 's';
            $bindings['params'][] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $sql .= " AND po.order_date <= ?";
            $bindings['types'] .= 's';
            $bindings['params'][] = $filters['to_date'];
        }

        $sql .= " ORDER BY po.order_date DESC, po.po_id DESC LIMIT ? ";
        $bindings['types'] .= 'i';
        $bindings['params'][] = $filters['per_page'];

        $rows = $this->fetchRows($sql, $bindings['types'], $bindings['params'], function ($row) {
            return [
                'po_id'        => $row['po_id'],
                'order_date'   => $row['order_date'],
                'supplier'     => $row['supplier_name'],
                'item'         => $row['product_name'],
                'quantity'     => number_format((float)$row['quantity'], 2),
                'unit_cost'    => number_format((float)$row['unit_cost'], 2),
                'line_total'   => number_format((float)$row['line_total'], 2),
                'status'       => ucfirst($row['status']),
                'created_by'   => $row['created_by'],
            ];
        });

        return [
            'key'     => 'purchase_orders',
            'title'   => self::REPORT_TYPES['purchase_orders'],
            'columns' => ['PO #','Order Date','Supplier','Item','Qty Ordered','Unit Cost','Line Total','Status','Created By'],
            'rows'    => $rows,
        ];
    }

    private function buildCommonProductFilters(array $filters, string $alias): array
    {
        $types  = '';
        $params = [];
        $clause = '';

        if (!empty($filters['product_id'])) {
            $clause .= " AND {$alias}.product_id = ?";
            $types  .= 'i';
            $params[] = $filters['product_id'];
        }

        $dateClause = $this->buildDateClause($filters, "{$alias}.created_at");
        $clause    .= $dateClause['clause'];
        $types     .= $dateClause['types'];
        $params     = array_merge($params, $dateClause['params']);

        return compact('types', 'params', 'clause');
    }

    private function buildDateClause(array $filters, string $column): array
    {
        $clause = '';
        $types  = '';
        $params = [];

        if (!empty($filters['from_date'])) {
            $clause .= " AND {$column} >= ?";
            $types  .= 's';
            $params[] = $filters['from_date'] . ' 00:00:00';
        }

        if (!empty($filters['to_date'])) {
            $clause .= " AND {$column} <= ?";
            $types  .= 's';
            $params[] = $filters['to_date'] . ' 23:59:59';
        }

        return compact('clause', 'types', 'params');
    }

    private function fetchOptions(string $sql): array
    {
        $options = [];
        if ($result = $this->db->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $options[] = [
                    'id'    => $row['id'],
                    'label' => $row['label'],
                ];
            }
        }
        return $options;
    }

    private function fetchRows(string $sql, string $types, array $params, callable $formatter): array
    {
        $rows = [];
        $stmt = $this->db->prepare($sql);
        if ($types !== '') {
            $this->bindParams($stmt, $types, $params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $formatter($row);
        }
        $stmt->close();
        return $rows;
    }

    private function bindParams(mysqli_stmt $stmt, string $types, array $params): void
    {
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}
