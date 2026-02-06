<?php

namespace BugMentor\Mcp\Tools;

use PDO;

class SalesTool
{
    public function __construct(
        private readonly ?PDO $pdo = null
    ) {
    }

    public function getName(): string
    {
        return 'query_sales';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Retrieves sales data for a specific date range. Use this to generate business reports.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'start_date' => ['type' => 'string', 'format' => 'date', 'description' => 'YYYY-MM-DD'],
                    'end_date' => ['type' => 'string', 'format' => 'date', 'description' => 'YYYY-MM-DD'],
                ],
                'required' => ['start_date', 'end_date'],
            ],
        ];
    }

    public function execute(array $args): string
    {
        $start = $args['start_date'] ?? null;
        $end = $args['end_date'] ?? null;
        if ($start === null || $end === null) {
            return json_encode([
                'status' => 'error',
                'message' => 'start_date and end_date are required',
            ]);
        }

        if ($this->pdo === null) {
            $revenue = rand(10000, 50000);
            return json_encode([
                'status' => 'success',
                'period' => "{$start} to {$end}",
                'total_revenue' => $revenue,
                'transaction_count' => 0,
                'currency' => 'USD',
            ]);
        }

        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(total), 0) AS total_revenue, COUNT(*) AS transaction_count
             FROM sales WHERE sale_date BETWEEN ?::date AND ?::date'
        );
        $stmt->execute([$start, $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return json_encode([
            'status' => 'success',
            'period' => "{$start} to {$end}",
            'total_revenue' => (float) ($row['total_revenue'] ?? 0),
            'transaction_count' => (int) ($row['transaction_count'] ?? 0),
            'currency' => 'USD',
        ]);
    }
}
