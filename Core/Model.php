<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll('SELECT * FROM `' . static::$table . '` ORDER BY ' . $orderBy);
    }

    public static function find(int|string $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1',
            [$id]
        );
    }

    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        return Database::fetchAll(
            "SELECT * FROM `" . static::$table . "` WHERE `{$column}` {$operator} ?",
            [$value]
        );
    }

    public static function firstWhere(string $column, mixed $value): ?array
    {
        return Database::fetch(
            "SELECT * FROM `" . static::$table . "` WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }

    public static function create(array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            static::$table,
            implode('`,`', $cols),
            implode(',', $placeholders)
        );
        return Database::insert($sql, array_values($data));
    }

    public static function update(int|string $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = "`{$col}` = ?";
        }
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = ?',
            static::$table,
            implode(', ', $sets),
            static::$primaryKey
        );
        $params = array_values($data);
        $params[] = $id;
        Database::query($sql, $params);
        return true;
    }

    public static function delete(int|string $id): bool
    {
        Database::query(
            'DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?',
            [$id]
        );
        return true;
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS c FROM `' . static::$table . '` WHERE ' . $where,
            $params
        );
        return (int) ($row['c'] ?? 0);
    }

    public static function paginate(int $page = 1, int $perPage = 15, string $where = '1=1', array $params = [], string $order = 'id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = self::count($where, $params);
        $rows = Database::fetchAll(
            "SELECT * FROM `" . static::$table . "` WHERE {$where} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil(max(1, $total) / $perPage),
        ];
    }
}
