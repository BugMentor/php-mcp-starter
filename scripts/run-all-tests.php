#!/usr/bin/env php
<?php

/**
 * Run manual test, exploratory test, and PHPUnit (if available).
 * Usage: php scripts/run-all-tests.php
 */

$projectRoot = dirname(__DIR__);
$status = 0;
$php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';

echo "=== 1. Manual test ===\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($projectRoot . '/scripts/manual-test.php'), $manualExit);
if ($manualExit !== 0) {
    $status = 1;
}

echo "\n=== 2. Exploratory test ===\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($projectRoot . '/scripts/exploratory.php'), $exploreExit);
if ($exploreExit !== 0) {
    $status = 1;
}

echo "\n=== 3. Pest (unit, integration, E2E) ===\n";
$pest = $projectRoot . '/vendor/bin/pest';
if (is_file($pest)) {
    passthru(escapeshellarg($php) . ' ' . escapeshellarg($pest) . ' --configuration ' . escapeshellarg($projectRoot . '/phpunit.xml.dist'), $pestExit);
    if ($pestExit !== 0) {
        $status = 1;
    }
} else {
    echo "Pest not found. Run: composer install\n";
}

exit($status);
