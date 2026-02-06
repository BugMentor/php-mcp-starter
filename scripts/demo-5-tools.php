#!/usr/bin/env php
<?php

/**
 * Demo: run MCP server with 5 tools (requires PostgreSQL via docker compose up -d).
 * Usage: php scripts/demo-5-tools.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\CreateOrderTool;
use BugMentor\Mcp\Tools\ListCustomersTool;
use BugMentor\Mcp\Tools\ListProductsTool;
use BugMentor\Mcp\Tools\ListSalesTool;
use BugMentor\Mcp\Tools\SalesTool;

if (is_file($projectRoot . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
    $dotenv->safeLoad();
}

$pdo = null;
try {
    $pdo = BugMentor\Mcp\Database::createFromEnv();
} catch (Throwable $e) {
    fwrite(STDERR, "Database not available: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Start PostgreSQL: docker compose up -d\n");
    exit(1);
}

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool($pdo));
$server->registerTool(new ListProductsTool($pdo));
$server->registerTool(new ListCustomersTool($pdo));
$server->registerTool(new ListSalesTool($pdo));
$server->registerTool(new CreateOrderTool($pdo));

$requests = [
    ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
    ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'list_products', 'arguments' => ['limit' => 3]]],
    ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'list_customers', 'arguments' => ['limit' => 3]]],
    ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-06-30']]],
    ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call', 'params' => ['name' => 'list_sales', 'arguments' => ['limit' => 3]]],
    ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call', 'params' => ['name' => 'create_order', 'arguments' => ['customer_id' => 1, 'product_id' => 1, 'quantity' => 2]]],
];

$input = implode("", array_map(fn ($r) => json_encode($r) . "\n", $requests));
$stdin = fopen('php://memory', 'r+');
$stdout = fopen('php://memory', 'r+');
$stderr = fopen('php://memory', 'r+');
fwrite($stdin, $input);
rewind($stdin);
$server->run($stdin, $stdout, $stderr);
rewind($stdout);
$raw = stream_get_contents($stdout);
fclose($stdin);
fclose($stdout);
fclose($stderr);

$lines = array_filter(explode("\n", trim($raw)));
echo "========== DEMO: 5 TOOLS (PostgreSQL) ==========\n\n";
foreach ($lines as $i => $line) {
    $r = json_decode($line, true);
    $id = $r['id'] ?? $i + 1;
    if (isset($r['result']['tools'])) {
        echo "--- tools/list (" . count($r['result']['tools']) . " tools) ---\n";
        foreach ($r['result']['tools'] as $t) {
            echo "  - " . $t['name'] . "\n";
        }
    } elseif (isset($r['result']['content'])) {
        $text = $r['result']['content'][0]['text'] ?? '';
        $data = json_decode($text, true);
        if ($data) {
            $label = isset($data['products']) ? 'list_products' : (isset($data['customers']) ? 'list_customers' : (isset($data['sales']) ? 'list_sales' : (isset($data['order']) ? 'create_order' : 'query_sales')));
            echo "--- " . $label . " ---\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "--- response $id ---\n" . $text . "\n";
        }
    } else {
        echo "--- response $id ---\n" . json_encode($r, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
}
echo "========== END ==========\n";
