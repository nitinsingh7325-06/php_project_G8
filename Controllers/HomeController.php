<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => "The Wave Men's Salon",
            'featured' => Service::featured(6),
            'reviews' => Review::approved(6),
            'avgRating' => Review::average(),
            'staff' => User::staffList(),
        ]);
    }

    public function about(): void
    {
        $about = Database::fetch("SELECT setting_value FROM settings WHERE setting_key='about_text'");
        $this->view('home/about', [
            'title' => 'About Us',
            'about' => $about['setting_value'] ?? '',
            'staff' => User::staffList(),
        ]);
    }

    public function services(): void
    {
        $category = $this->input('category');
        $search = $this->input('q');
        $catId = null;
        if ($category) {
            $cat = Database::fetch('SELECT id FROM service_categories WHERE slug = ?', [$category]);
            $catId = $cat ? (int) $cat['id'] : null;
        }
        $this->view('home/services', [
            'title' => 'Our Services',
            'categories' => Service::categories(),
            'services' => Service::active($catId, $search ? (string) $search : null),
            'activeCategory' => $category,
            'search' => $search,
        ]);
    }

    public function pricing(): void
    {
        $this->view('home/pricing', [
            'title' => 'Pricing',
            'groups' => Service::pricingGrouped(),
        ]);
    }

    public function gallery(): void
    {
        $items = Database::fetchAll('SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order');
        $this->view('home/gallery', [
            'title' => 'Gallery',
            'items' => $items,
        ]);
    }

    public function staff(): void
    {
        $this->view('home/staff', [
            'title' => 'Our Stylists',
            'staff' => User::staffList(),
        ]);
    }

    public function reviews(): void
    {
        $this->view('home/reviews', [
            'title' => 'Reviews',
            'reviews' => Review::approved(50),
            'avgRating' => Review::average(),
        ]);
    }

    public function contact(): void
    {
        $map = Database::fetch("SELECT setting_value FROM settings WHERE setting_key='google_maps_embed'");
        $this->view('home/contact', [
            'title' => 'Contact',
            'mapEmbed' => $map['setting_value'] ?? '',
            'salon' => config('salon'),
        ]);
    }

    public function contactSubmit(): void
    {
        if (!$this->csrfCheck()) {
            $this->json(['success' => false, 'message' => 'Invalid token'], 422);
        }
        [$data, $errors] = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10',
        ]);
        if ($errors) {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }
        log_message('info', 'Contact form', $data);
        $this->json(['success' => true, 'message' => 'Thank you! We will get back to you shortly.']);
    }
}
