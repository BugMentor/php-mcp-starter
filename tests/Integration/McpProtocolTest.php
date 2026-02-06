<?php

declare(strict_types=1);

test('full sequence initialize, list, call', function (): void {
    $requests = [
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize'],
        ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'],
        [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'query_sales',
                'arguments' => ['start_date' => '2024-06-01', 'end_date' => '2024-06-30'],
            ],
        ],
    ];
    $responses = runServerWithRequests($requests);
    expect($responses)->toHaveCount(3);
    expect($responses[0]['id'])->toBe(1)
        ->and($responses[0]['result']['protocolVersion'])->toBe('2024-11-05')
        ->and($responses[0]['result']['serverInfo']['name'])->toBe('php-sales-agent');
    expect($responses[1]['id'])->toBe(2)
        ->and($responses[1]['result']['tools'])->toHaveCount(1)
        ->and($responses[1]['result']['tools'][0]['name'])->toBe('query_sales');
    expect($responses[2]['id'])->toBe(3)
        ->and($responses[2]['result']['isError'])->toBeFalse();
    $content = json_decode($responses[2]['result']['content'][0]['text'], true);
    expect($content['period'])->toBe('2024-06-01 to 2024-06-30')
        ->and($content)->toHaveKey('total_revenue');
});

test('multiple tool calls in sequence', function (): void {
    $requests = [
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-15']]],
        ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'query_sales', 'arguments' => ['start_date' => '2024-02-01', 'end_date' => '2024-02-28']]],
    ];
    $responses = runServerWithRequests($requests);
    expect($responses)->toHaveCount(2)
        ->and($responses[0]['result']['isError'])->toBeFalse()
        ->and($responses[1]['result']['isError'])->toBeFalse();
    expect($responses[0]['result']['content'][0]['text'])->toContain('2024-01-01 to 2024-01-15')
        ->and($responses[1]['result']['content'][0]['text'])->toContain('2024-02-01 to 2024-02-28');
});

test('notification then request', function (): void {
    $requests = [
        ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'],
    ];
    $responses = runServerWithRequests($requests);
    expect($responses)->toHaveCount(1)
        ->and($responses[0]['id'])->toBe(1)
        ->and($responses[0]['result'])->toHaveKey('tools');
});
