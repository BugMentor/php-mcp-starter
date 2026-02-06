#!/usr/bin/env php
<?php

/**
 * Manual test: send 15 prompts from docs/MANUAL_TESTING.md to the MCP server.
 * Works with or without DB (with DB: 5 tools; without: 1 tool).
 * Usage: php scripts/manual-15-prompts.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

use BugMentor\Mcp\Database;
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
    $pdo = Database::createFromEnv();
} catch (Throwable) {
    // No DB: server will have only SalesTool
}

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool($pdo));
if ($pdo !== null) {
    $server->registerTool(new ListProductsTool($pdo));
    $server->registerTool(new ListCustomersTool($pdo));
    $server->registerTool(new ListSalesTool($pdo));
    $server->registerTool(new CreateOrderTool($pdo));
}

$prompts = [
    1  => ['label' => 'Initialize', 'request' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']],
    2  => ['label' => 'List tools', 'request' => ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']],
    3  => ['label' => 'Query sales – January 2024', 'request' => ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31']]]],
    4  => ['label' => 'Query sales – Q2 2024', 'request' => ['jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-04-01', 'end_date' => '2024-06-30']]]],
    5  => ['label' => 'Query sales – single day', 'request' => ['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-12-25', 'end_date' => '2024-12-25']]]],
    6  => ['label' => 'List products (default)', 'request' => ['jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call', 'params' => ['name' => 'list_products', 'arguments' => []]]],
    7  => ['label' => 'List products (limit 3)', 'request' => ['jsonrpc' => '2.0', 'id' => 7, 'method' => 'tools/call', 'params' => ['name' => 'list_products', 'arguments' => ['limit' => 3]]]],
    8  => ['label' => 'List customers (default)', 'request' => ['jsonrpc' => '2.0', 'id' => 8, 'method' => 'tools/call', 'params' => ['name' => 'list_customers', 'arguments' => []]]],
    9  => ['label' => 'List customers (limit 5)', 'request' => ['jsonrpc' => '2.0', 'id' => 9, 'method' => 'tools/call', 'params' => ['name' => 'list_customers', 'arguments' => ['limit' => 5]]]],
    10 => ['label' => 'List sales (default)', 'request' => ['jsonrpc' => '2.0', 'id' => 10, 'method' => 'tools/call', 'params' => ['name' => 'list_sales', 'arguments' => []]]],
    11 => ['label' => 'List sales (date filter)', 'request' => ['jsonrpc' => '2.0', 'id' => 11, 'method' => 'tools/call', 'params' => ['name' => 'list_sales', 'arguments' => ['start_date' => '2024-06-01', 'end_date' => '2024-06-30', 'limit' => 10]]]],
    12 => ['label' => 'Create order', 'request' => ['jsonrpc' => '2.0', 'id' => 12, 'method' => 'tools/call', 'params' => ['name' => 'create_order', 'arguments' => ['customer_id' => 1, 'product_id' => 1, 'quantity' => 2]]]],
    13 => ['label' => 'Unknown tool', 'request' => ['jsonrpc' => '2.0', 'id' => 13, 'method' => 'tools/call', 'params' => ['name' => 'weather_forecast', 'arguments' => []]]],
    14 => ['label' => 'Unknown method', 'request' => ['jsonrpc' => '2.0', 'id' => 14, 'method' => 'resources/list']],
    15 => ['label' => 'Notification (no response)', 'request' => ['jsonrpc' => '2.0', 'method' => 'notifications/initialized']],
];

$input = implode("", array_map(fn ($p) => json_encode($p['request']) . "\n", $prompts));
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
$lineIndex = 0;

echo "========== MANUAL TEST: 15 PROMPTS (see docs/MANUAL_TESTING.md) ==========\n\n";

foreach ($prompts as $num => $prompt) {
    echo "--- Prompt {$num}: {$prompt['label']} ---\n";
    $isNotification = !isset($prompt['request']['id']);
    if ($isNotification) {
        echo "(no response – notification)\n";
    } elseif (isset($lines[$lineIndex])) {
        $response = json_decode($lines[$lineIndex], true);
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $lineIndex++;
    } else {
        echo "(no response)\n";
    }
    echo "\n";
}

echo "========== END (15 prompts) ==========\n";
