<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class LoyaltyService
{
    public function earn(int $userId, float $amount, string $refType, int $refId): void
    {
        $points = (int) floor($amount * (int) env('LOYALTY_POINTS_PER_RUPEE', 1));
        if ($points <= 0) {
            return;
        }
        Database::insert(
            'INSERT INTO loyalty_transactions (user_id, points, type, reference_type, reference_id, description) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $points, 'earn', $refType, $refId, "Earned {$points} points"]
        );
        Database::query('UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?', [$points, $userId]);
        $this->refreshTier($userId);
    }

    public function redeem(int $userId, int $points, string $description = 'Redeemed points'): bool
    {
        $user = Database::fetch('SELECT loyalty_points FROM users WHERE id = ?', [$userId]);
        if (!$user || (int) $user['loyalty_points'] < $points) {
            return false;
        }
        Database::insert(
            'INSERT INTO loyalty_transactions (user_id, points, type, description) VALUES (?, ?, ?, ?)',
            [$userId, -$points, 'redeem', $description]
        );
        Database::query('UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?', [$points, $userId]);
        $this->refreshTier($userId);
        return true;
    }

    public function refreshTier(int $userId): void
    {
        $user = Database::fetch('SELECT loyalty_points FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }
        $tier = loyalty_tier((int) $user['loyalty_points']);
        Database::query('UPDATE users SET membership_tier = ? WHERE id = ?', [$tier, $userId]);
    }
}
