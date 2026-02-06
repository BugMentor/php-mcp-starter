<?php

declare(strict_types=1);

use BugMentor\Mcp\Server;
use BugMentor\Mcp\Tools\SalesTool;

function runServerWithInput(string $input): string
{
    $stdin = fopen('php://memory', 'r+');
    $stdout = fopen('php://memory', 'r+');
    $stderr = fopen('php://memory', 'r+');
    fwrite($stdin, $input);
    rewind($stdin);

    $server = new Server('test-server', '1.0.0');
    $server->registerTool(new SalesTool());
    $server->run($stdin, $stdout, $stderr);

    rewind($stdout);
    $output = stream_get_contents($stdout);
    fclose($stdin);
    fclose($stdout);
    fclose($stderr);
    return $output;
}

function runServerWithRequests(array $requestLines): array
{
    $input = implode("", array_map(fn ($r) => (is_string($r) ? $r : json_encode($r)) . "\n", $requestLines));
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

    $responses = [];
    foreach (explode("\n", trim($raw)) as $line) {
        if ($line !== '') {
            $responses[] = json_decode($line, true);
        }
    }
    return $responses;
}

function e2eSendRequest(string $requestJson): string
{
    $projectRoot = dirname(__DIR__);
    $serverPath = $projectRoot . DIRECTORY_SEPARATOR . 'server.php';
    $phpBinary = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open(
        [$phpBinary, $serverPath],
        $descriptorSpec,
        $pipes,
        $projectRoot,
        null,
        ['bypass_shell' => true]
    );
    if (!is_resource($proc)) {
        throw new RuntimeException('proc_open failed');
    }
    fwrite($pipes[0], $requestJson . "\n");
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    return trim((string) $stdout);
}
