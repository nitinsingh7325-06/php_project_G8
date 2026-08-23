#!/usr/bin/env php
<?php
/**
 * Clear Application Cache
 */

declare(strict_types=1);

$cacheDirs = [
    __DIR__ . '/../storage/framework/cache',
    __DIR__ . '/../storage/framework/sessions',
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../storage/logs'
];

foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                unlink($file);
            }
        }
        echo "Cleared: " . $dir . "\n";
    }
}

echo "Cache cleared successfully!\n";