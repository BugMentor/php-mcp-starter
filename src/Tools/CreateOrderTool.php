<?php

namespace BugMentor\Mcp\Tools;

use PDO;

class CreateOrderTool
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function getName(): string
    {
        return 'create_order';
    }

    public function getDefinition(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'Creates a new sale/order for a customer and product. Use when the user wants to place an order.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Customer ID'],
                    'product_id' => ['type' => 'integer', 'description' => 'Product ID'],
                    'quantity' => ['type' => 'integer', 'description' => 'Quantity to order', 'minimum' => 1],
                    'sale_date' => ['type' => 'string', 'format' => 'date', 'description' => 'Date of sale (YYYY-MM-DD). Defaults to today.'],
                ],
                'required' => ['customer_id', 'product_id', 'quantity'],
            ],
        ];
    }

    public function execute(array $args): string
    {
        $customerId = (int) ($args['customer_id'] ?? 0);
        $productId = (int) ($args['product_id'] ?? 0);
        $quantity = (int) ($args['quantity'] ?? 0);
        $saleDate = $args['sale_date'] ?? date('Y-m-d');

        if ($customerId < 1 || $productId < 1 || $quantity < 1) {
            return json_encode([
                'status' => 'error',
                'message' => 'customer_id, product_id, and quantity (>= 1) are required',
            ]);
        }

        $stmt = $this->pdo->prepare('SELECT unit_price FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return json_encode(['status' => 'error', 'message' => 'Product not found']);
        }

        $stmt = $this->pdo->prepare('SELECT id FROM customers WHERE id = ?');
        $stmt->execute([$customerId]);
        if (!$stmt->fetch()) {
            return json_encode(['status' => 'error', 'message' => 'Customer not found']);
        }

        $unitPrice = (float) $product['unit_price'];
        $total = round($unitPrice * $quantity, 2);

        $stmt = $this->pdo->prepare(
            'INSERT INTO sales (customer_id, product_id, quantity, unit_price, total, sale_date) VALUES (?, ?, ?, ?, ?, ?::date) RETURNING id'
        );
        $stmt->execute([$customerId, $productId, $quantity, $unitPrice, $total, $saleDate]);
        $id = (int) $stmt->fetchColumn();

        return json_encode([
            'status' => 'success',
            'message' => 'Order created',
            'order' => [
                'id' => $id,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $total,
                'sale_date' => $saleDate,
            ],
        ]);
    }
}
