<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', "The Wave Men's Salon"),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost/wave/public'),
    'timezone' => env('TIMEZONE', 'Asia/Kolkata'),
    'theme' => [
        'bg' => '#121212',
        'gold' => '#D4AF37',
        'gold_light' => '#E8D48B',
        'text' => '#F5F5F5',
        'muted' => '#A0A0A0',
        'card' => 'rgba(255,255,255,0.06)',
        'border' => 'rgba(212,175,55,0.25)',
    ],
    'salon' => [
        'name' => env('SALON_NAME', "The Wave Men's Salon"),
        'phone' => env('SALON_PHONE', '+91 6354193414'),
        'email' => env('SALON_EMAIL', 'contactwavemenssalon@gmail.com'),
        'address' => env('SALON_ADDRESS', 'Shop No.4, Ambika Arcade, Opp. Samarpan Party Plot, 50Ft. Road, Near D-Mart, Kuvadva Road, Rajkot'),
        'instagram' => env('SALON_INSTAGRAM', 'https://www.instagram.com/thewavemans_salon?igsh=Znh4N3VrbHE0bzJq'),
        'lat' => env('SALON_LAT', '22.3039'),
        'lng' => env('SALON_LNG', '70.8022'),
        'open' => env('SALON_OPEN', '09:00'),
        'close' => env('SALON_CLOSE', '21:00'),
    ],
    'otp' => [
        'expiry' => (int) env('OTP_EXPIRY_MINUTES', 5),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 3),
        'rate_window' => (int) env('OTP_RATE_WINDOW_MINUTES', 10),
        'length' => (int) env('OTP_LENGTH', 6),
    ],
    'loyalty' => [
        'per_rupee' => (int) env('LOYALTY_POINTS_PER_RUPEE', 1),
        'gold' => (int) env('LOYALTY_GOLD_THRESHOLD', 500),
        'platinum' => (int) env('LOYALTY_PLATINUM_THRESHOLD', 2000),
        'diamond' => (int) env('LOYALTY_DIAMOND_THRESHOLD', 5000),
    ],
];
