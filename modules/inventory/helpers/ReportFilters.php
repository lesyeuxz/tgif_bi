<?php
declare(strict_types=1);

class ReportFilters
{
    public static function normalize(array $input): array
    {
        $filters = [
            'from_date'  => null,
            'to_date'    => null,
            'product_id' => null,
            'location'   => null,
            'supplier_id'=> null,
            'page'       => 1,
            'per_page'   => 50,
        ];

        if (!empty($input['from_date']) && self::isValidDate($input['from_date'])) {
            $filters['from_date'] = $input['from_date'];
        }

        if (!empty($input['to_date']) && self::isValidDate($input['to_date'])) {
            $filters['to_date'] = $input['to_date'];
        }

        if (!empty($input['product_id']) && ctype_digit((string)$input['product_id'])) {
            $filters['product_id'] = (int) $input['product_id'];
        }

        if (!empty($input['supplier_id']) && ctype_digit((string)$input['supplier_id'])) {
            $filters['supplier_id'] = (int) $input['supplier_id'];
        }

        if (!empty($input['location'])) {
            $filters['location'] = substr(trim($input['location']), 0, 150);
        }

        if (!empty($input['page']) && ctype_digit((string)$input['page'])) {
            $filters['page'] = max(1, (int)$input['page']);
        }

        if (!empty($input['per_page']) && ctype_digit((string)$input['per_page'])) {
            $filters['per_page'] = max(10, min(200, (int)$input['per_page']));
        }

        return $filters;
    }

    private static function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

