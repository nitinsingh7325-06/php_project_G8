<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Service extends Model
{
    protected static string $table = 'services';

    public static function active(?int $categoryId = null, ?string $search = null): array
    {
        $sql = 'SELECT s.*, c.name AS category_name, c.slug AS category_slug
                FROM services s JOIN service_categories c ON c.id = s.category_id
                WHERE s.is_active = 1';
        $params = [];
        if ($categoryId) {
            $sql .= ' AND s.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search) {
            $sql .= ' AND (s.name LIKE ? OR s.description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY c.sort_order, s.sort_order, s.name';
        return Database::fetchAll($sql, $params);
    }

    public static function featured(int $limit = 6): array
    {
        return Database::fetchAll(
            'SELECT s.*, c.name AS category_name FROM services s
             JOIN service_categories c ON c.id = s.category_id
             WHERE s.is_active=1 AND s.is_featured=1 ORDER BY s.sort_order LIMIT ' . (int) $limit
        );
    }

    public static function categories(): array
    {
        return Database::fetchAll('SELECT * FROM service_categories WHERE is_active=1 ORDER BY sort_order');
    }

    public static function pricingGrouped(): array
    {
        $cats = self::categories();
        $result = [];
        foreach ($cats as $cat) {
            $result[] = [
                'category' => $cat,
                'services' => self::active((int) $cat['id']),
            ];
        }
        return $result;
    }
}
