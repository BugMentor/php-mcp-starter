<?php

declare(strict_types=1);

test('initialize returns protocol and server info', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'initialize',
    ]) . "\n";
    $output = runServerWithInput($request);
    expect($output)->not->toBeEmpty();
    $response = json_decode(trim($output), true);
    expect($response['jsonrpc'])->toBe('2.0')
        ->and($response['id'])->toBe(1)
        ->and($response)->toHaveKey('result')
        ->and($response['result']['protocolVersion'])->toBe('2024-11-05')
        ->and($response['result']['serverInfo']['name'])->toBe('test-server')
        ->and($response['result']['serverInfo']['version'])->toBe('1.0.0')
        ->and($response['result']['capabilities'])->toHaveKey('tools');
});

test('tools list returns registered tools', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/list',
    ]) . "\n";
    $output = runServerWithInput($request);
    $response = json_decode(trim($output), true);
    expect($response)->toHaveKey('result')
        ->and($response['result'])->toHaveKey('tools')
        ->and($response['result']['tools'])->toHaveCount(1)
        ->and($response['result']['tools'][0]['name'])->toBe('query_sales');
});

test('tools call invokes tool and returns content', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'query_sales',
            'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'],
        ],
    ]) . "\n";
    $output = runServerWithInput($request);
    $response = json_decode(trim($output), true);
    expect($response)->toHaveKey('result')
        ->and($response['result']['isError'])->toBeFalse()
        ->and($response['result']['content'])->toHaveCount(1)
        ->and($response['result']['content'][0]['type'])->toBe('text');
    $data = json_decode($response['result']['content'][0]['text'], true);
    expect($data['status'])->toBe('success')
        ->and($data)->toHaveKey('total_revenue');
});

test('tools call unknown tool returns error', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 4,
        'method' => 'tools/call',
        'params' => ['name' => 'nonexistent_tool', 'arguments' => []],
    ]) . "\n";
    $output = runServerWithInput($request);
    $response = json_decode(trim($output), true);
    expect($response)->toHaveKey('error')
        ->and($response['error']['code'])->toBe(-32601)
        ->and($response['error']['message'])->toBe('Tool not found');
});

test('unknown method returns method not found', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'id' => 5,
        'method' => 'foo/bar',
    ]) . "\n";
    $output = runServerWithInput($request);
    $response = json_decode(trim($output), true);
    expect($response)->toHaveKey('error')
        ->and($response['error']['code'])->toBe(-32601)
        ->and($response['error']['message'])->toBe('Method not found');
});

test('notification initialized produces no output', function (): void {
    $request = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized',
    ]) . "\n";
    $output = runServerWithInput($request);
    expect($output)->toBe('');
});
