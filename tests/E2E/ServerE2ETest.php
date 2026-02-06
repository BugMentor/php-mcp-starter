<?php

declare(strict_types=1);

$skipIfNoServer = fn (): bool => !is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'server.php');

test('E2E initialize', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
    ]);
    $output = e2eSendRequest($request);
    expect($output)->not->toBeEmpty();
    $response = json_decode($output, true);
    expect($response)->not->toBeNull()
        ->and($response['jsonrpc'])->toBe('2.0')
        ->and($response['id'])->toBe(1)
        ->and($response)->toHaveKey('result')
        ->and($response['result']['serverInfo']['name'])->toBe('php-sales-agent')
        ->and($response['result']['serverInfo']['version'])->toBe('1.0.0');
})->skip($skipIfNoServer, 'server.php not found');

test('E2E tools list', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
    ]);
    $output = e2eSendRequest($request);
    $response = json_decode($output, true);
    expect($response['result']['tools'])->toHaveCount(1)
        ->and($response['result']['tools'][0]['name'])->toBe('query_sales');
})->skip($skipIfNoServer, 'server.php not found');

test('E2E tools call', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'query_sales',
            'arguments' => ['start_date' => '2024-03-01', 'end_date' => '2024-03-31'],
        ],
    ]);
    $output = e2eSendRequest($request);
    $response = json_decode($output, true);
    expect($response['result']['isError'])->toBeFalse();
    $data = json_decode($response['result']['content'][0]['text'], true);
    expect($data['status'])->toBe('success')
        ->and($data['period'])->toBe('2024-03-01 to 2024-03-31');
})->skip($skipIfNoServer, 'server.php not found');
