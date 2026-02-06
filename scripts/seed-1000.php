#!/usr/bin/env php
<?php

/**
 * Seed the database with exactly 1000 records: 30 products, 70 customers, 900 sales.
 * Run from project root: php scripts/seed-1000.php
 * Requires .env with DB_* and pdo_pgsql (or run via Docker demo image).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (is_file(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

use BugMentor\Mcp\Database;

$pdo = Database::createFromEnv();

echo "Seeding to 1000 records (30 products, 70 customers, 900 sales) ...\n";

// Truncate and reset sequences
$pdo->exec('TRUNCATE TABLE sales, products, customers RESTART IDENTITY CASCADE');

// 30 products
$products = [];
for ($i = 1; $i <= 30; $i++) {
    $name = sprintf('Product %d', $i);
    $sku = sprintf('PRD-%03d', $i);
    $unit_price = round(9.99 + (mt_rand(0, 400) / 10), 2);
    $products[] = ['name' => $name, 'sku' => $sku, 'unit_price' => $unit_price];
}
$stmt = $pdo->prepare('INSERT INTO products (name, sku, unit_price) VALUES (?, ?, ?)');
foreach ($products as $row) {
    $stmt->execute([$row['name'], $row['sku'], $row['unit_price']]);
}
echo "  products: 30\n";

// 70 customers
$stmt = $pdo->prepare('INSERT INTO customers (name, email) VALUES (?, ?)');
for ($i = 1; $i <= 70; $i++) {
    $stmt->execute([sprintf('Customer %d', $i), sprintf('customer%d@example.com', $i)]);
}
echo "  customers: 70\n";

// 900 sales: random customer_id 1-70, product_id 1-30, quantity 1-10, dates in 2024
$stmt = $pdo->prepare(
    'INSERT INTO sales (customer_id, product_id, quantity, unit_price, total, sale_date) VALUES (?, ?, ?, ?, ?, ?)'
);
$start = new DateTime('2024-01-01');
$end = new DateTime('2024-12-31');
$interval = $start->getTimestamp();
$range = $end->getTimestamp() - $interval;
for ($i = 0; $i < 900; $i++) {
    $customerId = mt_rand(1, 70);
    $productId = mt_rand(1, 30);
    $quantity = mt_rand(1, 10);
    $unitPrice = (float) $products[$productId - 1]['unit_price'];
    $total = round($quantity * $unitPrice, 2);
    $saleDate = date('Y-m-d', $interval + mt_rand(0, $range));
    $stmt->execute([$customerId, $productId, $quantity, $unitPrice, $total, $saleDate]);
}
echo "  sales: 900\n";

// Verify
$counts = [
    'products' => (int) $pdo->query('SELECT count(*) FROM products')->fetchColumn(),
    'customers' => (int) $pdo->query('SELECT count(*) FROM customers')->fetchColumn(),
    'sales' => (int) $pdo->query('SELECT count(*) FROM sales')->fetchColumn(),
];
$total = $counts['products'] + $counts['customers'] + $counts['sales'];
echo "Total: {$total} records (products: {$counts['products']}, customers: {$counts['customers']}, sales: {$counts['sales']})\n";
echo "Done.\n";
