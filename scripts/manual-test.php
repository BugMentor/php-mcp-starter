#!/usr/bin/env php
<?php

/**
 * Manual test: run the MCP server with sample requests and print responses.
 * Usage: php scripts/manual-test.php
 * Or pipe your own JSON-RPC lines: echo '{"jsonrpc":"2.0","id":1,"method":"initialize"}' | php server.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\SalesTool;

$requests = [
    ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'],
    ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'],
    [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'query_sales',
            'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'],
        ],
    ],
];

$input = implode("", array_map(fn ($r) => json_encode($r) . "\n", $requests));
$stdin = fopen('php://memory', 'r+');
$stdout = fopen('php://memory', 'r+');
$stderr = fopen('php://memory', 'r+');
fwrite($stdin, $input);
rewind($stdin);

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool());
$server->run($stdin, $stdout, $stderr);

rewind($stdout);
$output = stream_get_contents($stdout);
rewind($stderr);
$errors = stream_get_contents($stderr);
fclose($stdin);
fclose($stdout);
fclose($stderr);

echo "=== STDERR (server log) ===\n";
echo $errors;
echo "\n=== STDOUT (JSON-RPC responses) ===\n";
foreach (explode("\n", trim($output)) as $line) {
    if ($line === '') continue;
    $decoded = json_decode($line, true);
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
echo "\nManual test finished.\n";
