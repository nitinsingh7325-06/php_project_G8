<?php

declare(strict_types=1);

namespace App\Services;

class QrCodeService
{
    /**
     * Generate QR via Google Charts API (fallback) or local SVG.
     */
    public function generate(string $bookingId): string
    {
        $dir = storage_path('qrcodes');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $bookingId . '.png';
        $path = $dir . '/' . $filename;
        $publicRel = 'storage/qrcodes/' . $filename;

        $data = urlencode($bookingId);
        $apiUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl={$data}&choe=UTF-8";

        $image = @file_get_contents($apiUrl);
        if ($image !== false) {
            file_put_contents($path, $image);
        } else {
            // Local SVG fallback
            $svg = $this->svgQr($bookingId);
            $svgPath = $dir . '/' . $bookingId . '.svg';
            file_put_contents($svgPath, $svg);
            return 'storage/qrcodes/' . $bookingId . '.svg';
        }

        // Symlink/copy to public for serving
        $publicDir = dirname(__DIR__, 2) . '/public/storage/qrcodes';
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        @copy($path, $publicDir . '/' . $filename);

        return $publicRel;
    }

    private function svgQr(string $text): string
    {
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="300" height="300" fill="#121212"/>
  <rect x="20" y="20" width="60" height="60" fill="#D4AF37"/>
  <rect x="220" y="20" width="60" height="60" fill="#D4AF37"/>
  <rect x="20" y="220" width="60" height="60" fill="#D4AF37"/>
  <rect x="40" y="40" width="20" height="20" fill="#121212"/>
  <rect x="240" y="40" width="20" height="20" fill="#121212"/>
  <rect x="40" y="240" width="20" height="20" fill="#121212"/>
  <text x="150" y="160" fill="#D4AF37" font-size="14" text-anchor="middle" font-family="monospace">{$safe}</text>
</svg>
SVG;
    }
}
