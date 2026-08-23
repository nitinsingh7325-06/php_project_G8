<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/bootstrap.php';

use App\Core\Database;

echo "Starting service updates...\n";

try {
    Database::query("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Clear old appointment services and services & categories
    Database::query("TRUNCATE TABLE appointment_services;");
    Database::query("TRUNCATE TABLE services;");
    Database::query("TRUNCATE TABLE service_categories;");
    
    // Insert new 5 Categories
    $categories = [
        ['id' => 1, 'name' => 'Haircuts & Hair Care', 'slug' => 'haircuts-care', 'description' => 'Scissor cuts and soothing hair washes', 'icon' => 'scissors', 'sort_order' => 1],
        ['id' => 2, 'name' => 'Beard Grooming', 'slug' => 'beard-grooming', 'description' => 'Precision beard trims, styling, and coloring', 'icon' => 'mustache', 'sort_order' => 2],
        ['id' => 3, 'name' => 'Facial & De-Tan Care', 'slug' => 'facial-detan', 'description' => 'De-tanning, clean-ups, facials, and face massages', 'icon' => 'face', 'sort_order' => 3],
        ['id' => 4, 'name' => 'Hair Colour', 'slug' => 'hair-colour', 'description' => 'Professional matrix, loreal, and wella hair coloring', 'icon' => 'palette', 'sort_order' => 4],
        ['id' => 5, 'name' => 'Hair Spa & Treatments', 'slug' => 'hair-spa', 'description' => 'Nourishing and restorative hair spa rituals', 'icon' => 'spa', 'sort_order' => 5],
    ];

    foreach ($categories as $cat) {
        Database::insert(
            "INSERT INTO service_categories (id, name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)",
            [$cat['id'], $cat['name'], $cat['slug'], $cat['description'], $cat['icon'], $cat['sort_order']]
        );
    }

    // Insert 26 exact separated services
    $services = [
        // Category 1: Haircuts & Hair Care
        ['cat' => 1, 'name' => 'Haircut', 'slug' => 'haircut', 'desc' => 'Precision haircut and styling', 'dur' => 30, 'price' => 200.00, 'feat' => 1],
        ['cat' => 1, 'name' => 'Normal Hairwash', 'slug' => 'normal-hairwash', 'desc' => 'Refreshing hair wash and scalp clean', 'dur' => 15, 'price' => 100.00, 'feat' => 0],
        ['cat' => 1, 'name' => 'Premium Hairwash', 'slug' => 'premium-hairwash', 'desc' => 'Deep conditioning hair wash ritual', 'dur' => 20, 'price' => 150.00, 'feat' => 1],

        // Category 2: Beard Grooming
        ['cat' => 2, 'name' => 'Normal Beard', 'slug' => 'normal-beard', 'desc' => 'Classic beard trim and outline', 'dur' => 20, 'price' => 150.00, 'feat' => 1],
        ['cat' => 2, 'name' => 'Fade Beard', 'slug' => 'fade-beard', 'desc' => 'Modern sharp gradient fade beard shaping', 'dur' => 25, 'price' => 200.00, 'feat' => 1],
        ['cat' => 2, 'name' => 'Beard Colour', 'slug' => 'beard-colour', 'desc' => 'Natural coverage beard colouring', 'dur' => 30, 'price' => 300.00, 'feat' => 0],

        // Category 3: Facial & De-Tan Care
        ['cat' => 3, 'name' => 'De-Tan ₹500', 'slug' => 'detan-500', 'desc' => 'Essential skin de-tan treatment', 'dur' => 30, 'price' => 500.00, 'feat' => 0],
        ['cat' => 3, 'name' => 'De-Tan ₹700', 'slug' => 'detan-700', 'desc' => 'Advanced de-tan therapy with skin glow', 'dur' => 40, 'price' => 700.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'De-Tan ₹900', 'slug' => 'detan-900', 'desc' => 'Ultra de-tan deep pigmentation removal', 'dur' => 45, 'price' => 900.00, 'feat' => 0],
        ['cat' => 3, 'name' => 'Clean-Up ₹1000', 'slug' => 'clean-up-1000', 'desc' => 'Deep pore skin clean-up and steam', 'dur' => 45, 'price' => 1000.00, 'feat' => 0],
        ['cat' => 3, 'name' => 'Clean-Up ₹1250', 'slug' => 'clean-up-1250', 'desc' => 'Premium skin clean-up with blackhead removal', 'dur' => 50, 'price' => 1250.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Facial Oxy Life (₹1500)', 'slug' => 'facial-oxy-life-1500', 'desc' => 'Oxygenating skin radiance facial by Oxy Life', 'dur' => 60, 'price' => 1500.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Facial N+ (₹1800)', 'slug' => 'facial-n-plus-1800', 'desc' => 'Intensive skin repair facial by N+', 'dur' => 60, 'price' => 1800.00, 'feat' => 0],
        ['cat' => 3, 'name' => 'Facial N+ (₹2000)', 'slug' => 'facial-n-plus-2000', 'desc' => 'Advanced whitening & glow facial by N+', 'dur' => 60, 'price' => 2000.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Facial Coteskin (₹2500)', 'slug' => 'facial-coteskin-2500', 'desc' => 'Dermatological deep hydrate facial by Coteskin', 'dur' => 65, 'price' => 2500.00, 'feat' => 0],
        ['cat' => 3, 'name' => 'Facial Coteskin (₹3000)', 'slug' => 'facial-coteskin-3000', 'desc' => 'Luxury anti-aging & brightness facial by Coteskin', 'dur' => 75, 'price' => 3000.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Facial Briller (₹3500)', 'slug' => 'facial-briller-3500', 'desc' => 'Ultra luxury diamond shine facial by Briller', 'dur' => 75, 'price' => 3500.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Facial Lotus (₹4000)', 'slug' => 'facial-lotus-4000', 'desc' => 'Supreme herbal luxury facial by Lotus', 'dur' => 90, 'price' => 4000.00, 'feat' => 1],
        ['cat' => 3, 'name' => 'Face Massage', 'slug' => 'face-massage-250', 'desc' => 'Relaxing muscle stress relief face massage', 'dur' => 25, 'price' => 250.00, 'feat' => 0],

        // Category 4: Hair Colour
        ['cat' => 4, 'name' => 'Hair Colour Matrix', 'slug' => 'hair-colour-matrix-300', 'desc' => 'Matrix rich shade hair colouring', 'dur' => 45, 'price' => 300.00, 'feat' => 1],
        ['cat' => 4, 'name' => 'Hair Colour Loreal', 'slug' => 'hair-colour-loreal-350', 'desc' => 'L\'Oreal Paris salon hair color finish', 'dur' => 45, 'price' => 350.00, 'feat' => 1],
        ['cat' => 4, 'name' => 'Hair Colour Wella', 'slug' => 'hair-colour-wella-400', 'desc' => 'Wella Koleston premium hair colour', 'dur' => 45, 'price' => 400.00, 'feat' => 1],

        // Category 5: Hair Spa & Treatments
        ['cat' => 5, 'name' => 'Normal Hair Spa', 'slug' => 'normal-hairspa-500', 'desc' => 'Essential moisture hair spa ritual', 'dur' => 45, 'price' => 500.00, 'feat' => 0],
        ['cat' => 5, 'name' => 'Hair Spa Matrix', 'slug' => 'hair-spa-matrix-600', 'desc' => 'Matrix Biolage deep nourishment hair spa', 'dur' => 60, 'price' => 600.00, 'feat' => 1],
        ['cat' => 5, 'name' => 'Hair Spa Loreal', 'slug' => 'hair-spa-loreal-800', 'desc' => 'L\'Oreal Professionnel mythic hair spa', 'dur' => 60, 'price' => 800.00, 'feat' => 1],
        ['cat' => 5, 'name' => 'Hair Spa Wella', 'slug' => 'hair-spa-wella-1100', 'desc' => 'Wella System Professional intense therapy spa', 'dur' => 60, 'price' => 1100.00, 'feat' => 1],
    ];

    $sort = 1;
    foreach ($services as $svc) {
        Database::insert(
            "INSERT INTO services (category_id, name, slug, description, duration_minutes, price, discount_price, is_featured, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 1, ?)",
            [$svc['cat'], $svc['name'], $svc['slug'], $svc['desc'], $svc['dur'], $svc['price'], $svc['feat'], $sort++]
        );
    }

    // Link existing demo appointments to the new services
    $appointments = Database::fetchAll("SELECT id, final_amount FROM appointments");
    $allServiceIds = Database::fetchAll("SELECT id, price, duration_minutes FROM services");

    foreach ($appointments as $appt) {
        // Find best match or pick default service
        $svc = $allServiceIds[array_rand($allServiceIds)];
        Database::insert(
            "INSERT INTO appointment_services (appointment_id, service_id, price, duration_minutes) VALUES (?, ?, ?, ?)",
            [$appt['id'], $svc['id'], $svc['price'], $svc['duration_minutes']]
        );
    }

    Database::query("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Successfully updated " . count($services) . " services across " . count($categories) . " categories!\n";

} catch (\Throwable $e) {
    Database::query("SET FOREIGN_KEY_CHECKS = 1;");
    echo "Error seeding services: " . $e->getMessage() . "\n";
}
