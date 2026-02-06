<?php

namespace BugMentor\Mcp\Tools;

use PDO;

class ListCustomersTool
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function getName(): string
    {
        return 'list_customers';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Lists customers. Use to show customer names and emails.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'limit' => ['type' => 'integer', 'description' => 'Max number of customers to return (default 50)', 'default' => 50],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $args): string
    {
        $limit = min(100, max(1, (int) ($args['limit'] ?? 50)));
        $stmt = $this->pdo->prepare('SELECT id, name, email FROM customers ORDER BY id LIMIT ?');
        $stmt->execute([$limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode([
            'status' => 'success',
            'customers' => $rows,
            'count' => count($rows),
        ]);
    }
}
