#!/usr/bin/env php
<?php
/**
 * Generate Application Key
 */

$key = base64_encode(random_bytes(32));
echo "APP_KEY=base64:" . $key . "\n";