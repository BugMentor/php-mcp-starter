<?php

declare(strict_types=1);

/**
 * Runs the 15 manual test prompts from docs/MANUAL_TESTING.md.
 * Server uses SalesTool only (no DB), so list_products, list_customers, list_sales, create_order return "Tool not found".
 */

test('manual 15 prompts from docs/MANUAL_TESTING.md', function (): void {
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

    $requests = array_values(array_map(fn ($p) => $p['request'], $prompts));
    $responses = runServerWithRequests($requests);

    // Notification produces no response; we get 14 response lines
    expect($responses)->toHaveCount(14);

    // 1. Initialize
    expect($responses[0]['id'])->toBe(1)
        ->and($responses[0]['result']['serverInfo']['name'])->toBe('php-sales-agent')
        ->and($responses[0]['result']['serverInfo']['version'])->toBe('1.0.0')
        ->and($responses[0]['result']['protocolVersion'])->toBe('2024-11-05');

    // 2. List tools (1 tool when no DB)
    expect($responses[1]['id'])->toBe(2)
        ->and($responses[1]['result']['tools'])->toHaveCount(1)
        ->and($responses[1]['result']['tools'][0]['name'])->toBe('query_sales');

    // 3. Query sales – January 2024
    $r3 = json_decode($responses[2]['result']['content'][0]['text'], true);
    expect($responses[2]['id'])->toBe(3)
        ->and($responses[2]['result']['isError'])->toBeFalse()
        ->and($r3['status'])->toBe('success')
        ->and($r3['period'])->toBe('2024-01-01 to 2024-01-31')
        ->and($r3)->toHaveKey('total_revenue')
        ->and($r3['currency'])->toBe('USD');

    // 4. Query sales – Q2 2024
    $r4 = json_decode($responses[3]['result']['content'][0]['text'], true);
    expect($responses[3]['result']['isError'])->toBeFalse()
        ->and($r4['period'])->toBe('2024-04-01 to 2024-06-30');

    // 5. Query sales – single day
    $r5 = json_decode($responses[4]['result']['content'][0]['text'], true);
    expect($responses[4]['result']['isError'])->toBeFalse()
        ->and($r5['period'])->toBe('2024-12-25 to 2024-12-25');

    // 6–12. DB-only tools and create_order → Tool not found (no PDO in test)
    foreach ([5, 6, 7, 8, 9, 10, 11] as $i) {
        expect($responses[$i]['error']['code'])->toBe(-32601)
            ->and($responses[$i]['error']['message'])->toBe('Tool not found');
    }

    // 13. Unknown tool
    expect($responses[12]['error']['code'])->toBe(-32601)
        ->and($responses[12]['error']['message'])->toBe('Tool not found');

    // 14. Unknown method
    expect($responses[13]['error']['code'])->toBe(-32601)
        ->and($responses[13]['error']['message'])->toBe('Method not found');
});
