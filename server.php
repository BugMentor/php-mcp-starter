#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use BugMentor\Mcp\Database;
use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\CreateOrderTool;
use BugMentor\Mcp\Tools\ListCustomersTool;
use BugMentor\Mcp\Tools\ListProductsTool;
use BugMentor\Mcp\Tools\ListSalesTool;
use BugMentor\Mcp\Tools\SalesTool;

if (is_file(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

$pdo = null;
try {
    $pdo = Database::createFromEnv();
} catch (Throwable) {
    // No DB: register only SalesTool (mock mode) so server still runs
}

$server = new Server('php-sales-agent', '1.0.0');
$server->registerTool(new SalesTool($pdo));
if ($pdo !== null) {
    $server->registerTool(new ListProductsTool($pdo));
    $server->registerTool(new ListCustomersTool($pdo));
    $server->registerTool(new ListSalesTool($pdo));
    $server->registerTool(new CreateOrderTool($pdo));
}

$server->run();
