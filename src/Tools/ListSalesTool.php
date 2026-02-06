<?php

namespace BugMentor\Mcp\Tools;

use PDO;

class ListSalesTool
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function getName(): string
    {
        return 'list_sales';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Lists recent sales/orders. Optionally filter by date range. Use to show order history.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'limit' => ['type' => 'integer', 'description' => 'Max number of sales to return (default 20)', 'default' => 20],
                    'start_date' => ['type' => 'string', 'format' => 'date', 'description' => 'Filter from this date (YYYY-MM-DD)'],
                    'end_date' => ['type' => 'string', 'format' => 'date', 'description' => 'Filter to this date (YYYY-MM-DD)'],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $args): string
    {
        $limit = min(100, max(1, (int) ($args['limit'] ?? 20)));
        $start = $args['start_date'] ?? null;
        $end = $args['end_date'] ?? null;

        $where = [];
        $params = [];
        if ($start !== null && $start !== '') {
            $where[] = 's.sale_date >= ?::date';
            $params[] = $start;
        }
        if ($end !== null && $end !== '') {
            $where[] = 's.sale_date <= ?::date';
            $params[] = $end;
        }
        $sql = 'SELECT s.id, s.customer_id, c.name AS customer_name, s.product_id, p.name AS product_name,
                        s.quantity, s.unit_price, s.total, s.sale_date
                FROM sales s
                JOIN customers c ON c.id = s.customer_id
                JOIN products p ON p.id = s.product_id';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.sale_date DESC, s.id DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['unit_price'] = (float) $r['unit_price'];
            $r['total'] = (float) $r['total'];
        }
        return json_encode([
            'status' => 'success',
            'sales' => $rows,
            'count' => count($rows),
        ]);
    }
}
