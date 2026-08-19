<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Review extends Model
{
    protected static string $table = 'reviews';

    public static function approved(int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT r.*, u.name AS customer_name, u.avatar FROM reviews r
             JOIN users u ON u.id = r.customer_id
             WHERE r.is_approved = 1 ORDER BY r.is_featured DESC, r.created_at DESC LIMIT " . (int) $limit
        );
    }

    public static function average(): float
    {
        $row = Database::fetch('SELECT AVG(rating) a FROM reviews WHERE is_approved=1');
        return round((float) ($row['a'] ?? 0), 1);
    }
}
