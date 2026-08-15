<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        $user = Session::user();
        $rating = (int) $this->input('rating');
        if ($rating < 1 || $rating > 5) {
            $this->json(['success' => false, 'message' => 'Rating must be 1-5'], 422);
        }
        Review::create([
            'customer_id' => $user['id'],
            'appointment_id' => $this->input('appointment_id') ?: null,
            'staff_id' => $this->input('staff_id') ?: null,
            'rating' => $rating,
            'title' => $this->input('title'),
            'comment' => $this->input('comment'),
            'is_approved' => 0,
        ]);
        $this->json(['success' => true, 'message' => 'Thank you for your review!']);
    }
}
