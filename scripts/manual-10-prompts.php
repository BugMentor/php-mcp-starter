#!/usr/bin/env php
<?php

/**
 * Manual test: send 10 distinct prompts (JSON-RPC requests) to the MCP server and print responses.
 * Usage: php scripts/manual-10-prompts.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\SalesTool;

$prompts = [
    1 => [
        'label' => 'Initialize – handshake',
        'request' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'],
    ],
    2 => [
        'label' => 'List available tools',
        'request' => ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'],
    ],
    3 => [
        'label' => 'Query sales: January 2024',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
            'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31']],
        ],
    ],
    4 => [
        'label' => 'Query sales: Q2 2024 (Apr–Jun)',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 4, 'method' => 'tools/call',
            'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-04-01', 'end_date' => '2024-06-30']],
        ],
    ],
    5 => [
        'label' => 'Query sales: single day (Dec 25, 2024)',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call',
            'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-12-25', 'end_date' => '2024-12-25']],
        ],
    ],
    6 => [
        'label' => 'Call unknown tool (expect error)',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call',
            'params' => ['name' => 'weather_forecast', 'arguments' => []],
        ],
    ],
    7 => [
        'label' => 'Unknown method (expect error)',
        'request' => ['jsonrpc' => '2.0', 'id' => 7, 'method' => 'resources/list'],
    ],
    8 => [
        'label' => 'Notification – initialized (no response)',
        'request' => ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
    ],
    9 => [
        'label' => 'Query sales: missing args (uses defaults)',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 9, 'method' => 'tools/call',
            'params' => ['name' => 'query_sales', 'arguments' => []],
        ],
    ],
    10 => [
        'label' => 'Query sales: full year 2023',
        'request' => [
            'jsonrpc' => '2.0', 'id' => 10, 'method' => 'tools/call',
            'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2023-01-01', 'end_date' => '2023-12-31']],
        ],
    ],
];

$input = implode("", array_map(fn ($p) => json_encode($p['request']) . "\n", $prompts));
$stdin = fopen('php://memory', 'r+');
$stdout = fopen('php://memory', 'r+');
$stderr = fopen('php://memory', 'r+');
fwrite($stdin, $input);
rewind($stdin);

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool());
$server->run($stdin, $stdout, $stderr);

rewind($stdout);
$raw = stream_get_contents($stdout);
fclose($stdin);
fclose($stdout);
fclose($stderr);

$lines = array_filter(explode("\n", trim($raw)));
$lineIndex = 0;

echo "========== MANUAL TEST: 10 PROMPTS ==========\n\n";

foreach ($prompts as $num => $prompt) {
    echo "--- Prompt {$num}: {$prompt['label']} ---\n";
    // Notifications have no id and produce no response
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

echo "========== END (10 prompts) ==========\n";
