<?php

declare(strict_types=1);

use BugMentor\Mcp\Tools\SalesTool;

beforeEach(function (): void {
    $this->tool = new SalesTool();
});

test('getName returns query_sales', function (): void {
    expect($this->tool->getName())->toBe('query_sales');
});

test('getDefinition has name, description and inputSchema with required dates', function (): void {
    $def = $this->tool->getDefinition();
    expect($def['name'])->toBe('query_sales')
        ->and($def)->toHaveKey('description')
        ->and($def)->toHaveKey('inputSchema')
        ->and($def['inputSchema']['type'])->toBe('object')
        ->and($def['inputSchema']['properties'])->toHaveKey('start_date')
        ->and($def['inputSchema']['properties'])->toHaveKey('end_date')
        ->and($def['inputSchema']['required'])->toContain('start_date')
        ->and($def['inputSchema']['required'])->toContain('end_date');
});

test('execute returns valid JSON with period and revenue', function (): void {
    $args = ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'];
    $result = $this->tool->execute($args);
    expect(json_decode($result))->not->toBeFalse();
    $data = json_decode($result, true);
    expect($data['status'])->toBe('success')
        ->and($data['period'])->toBe('2024-01-01 to 2024-01-31')
        ->and($data)->toHaveKey('total_revenue')
        ->and($data['total_revenue'])->toBeInt()
        ->and($data['currency'])->toBe('USD');
});

test('execute with missing dates returns error', function (): void {
    $result = $this->tool->execute([]);
    $data = json_decode($result, true);
    expect($data['status'])->toBe('error')
        ->and($data['message'])->toContain('start_date')
        ->and($data['message'])->toContain('end_date');
});

test('execute revenue is in reasonable range', function (): void {
    $args = ['start_date' => '2024-01-01', 'end_date' => '2024-01-31'];
    for ($i = 0; $i < 5; $i++) {
        $result = $this->tool->execute($args);
        $data = json_decode($result, true);
        expect($data['total_revenue'])->toBeGreaterThanOrEqual(10000)
            ->and($data['total_revenue'])->toBeLessThanOrEqual(50000);
    }
});
