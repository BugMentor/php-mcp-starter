<?php

namespace BugMentor\Mcp\Tools;

use PDO;

class ListProductsTool
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function getName(): string
    {
        return 'list_products';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Lists all products in the catalog. Use to show product names, SKUs, and prices.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'limit' => ['type' => 'integer', 'description' => 'Max number of products to return (default 50)', 'default' => 50],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $args): string
    {
        $limit = min(100, max(1, (int) ($args['limit'] ?? 50)));
        $stmt = $this->pdo->prepare('SELECT id, name, sku, unit_price FROM products ORDER BY id LIMIT ?');
        $stmt->execute([$limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['unit_price'] = (float) $r['unit_price'];
        }
        return json_encode([
            'status' => 'success',
            'products' => $rows,
            'count' => count($rows),
        ]);
    }
}
