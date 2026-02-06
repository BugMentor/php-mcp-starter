#!/usr/bin/env php
<?php

/**
 * Exploratory test: send various requests (happy path, errors, edge cases) and print results.
 * Usage: php scripts/exploratory.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\SalesTool;

function runRequests(Server $server, array $requests): array
{
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
    $out = [];
    foreach (explode("\n", trim($raw)) as $line) {
        if ($line !== '') {
            $out[] = json_decode($line, true);
        }
    }
    return $out;
}

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool());

$scenarios = [
    '1. Initialize' => [['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize']],
    '2. Tools list' => [['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']],
    '3. Tool call (valid)' => [[
        'jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call',
        'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-06-01', 'end_date' => '2024-06-30']],
    ]],
    '4. Unknown method' => [['jsonrpc' => '2.0', 'id' => 4, 'method' => 'unknown/method']],
    '5. Unknown tool' => [[
        'jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call',
        'params' => ['name' => 'fake_tool', 'arguments' => []],
    ]],
    '6. Notification (no response)' => [['jsonrpc' => '2.0', 'method' => 'notifications/initialized']],
    '7. Tool call (missing args)' => [[
        'jsonrpc' => '2.0', 'id' => 6, 'method' => 'tools/call',
        'params' => ['name' => 'query_sales', 'arguments' => []],
    ]],
];

echo "=== Exploratory MCP tests ===\n\n";
foreach ($scenarios as $name => $requests) {
    echo "--- $name ---\n";
    $responses = runRequests($server, $requests);
    foreach ($responses as $i => $r) {
        echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    if (empty($responses)) {
        echo "(no response, e.g. notification)\n";
    }
    echo "\n";
}
echo "Exploratory run complete.\n";
