<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class Order extends Model
{
    public function all(): array
    {
        $sql = 'SELECT o.*, c.name AS customer_name FROM orders o LEFT JOIN customers c ON c.id = o.customer_id ORDER BY o.id DESC LIMIT 100';
        return $this->db->query($sql)->fetchAll();
    }

    public function createFromPdv(array $payload): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO orders (customer_id, order_type, status, payment_method, subtotal, discount_amount, delivery_fee, total_amount, notes, created_by, created_at, updated_at) VALUES (:customer_id, :order_type, :status, :payment_method, :subtotal, :discount_amount, :delivery_fee, :total_amount, :notes, :created_by, NOW(), NOW())');
            $stmt->execute([
                'customer_id' => $payload['customer_id'] ?: null,
                'order_type' => $payload['order_type'],
                'status' => 'novo',
                'payment_method' => $payload['payment_method'],
                'subtotal' => $payload['subtotal'],
                'discount_amount' => $payload['discount_amount'],
                'delivery_fee' => $payload['delivery_fee'],
                'total_amount' => $payload['total_amount'],
                'notes' => $payload['notes'] ?? null,
                'created_by' => $payload['user_id'],
            ]);

            $orderId = (int) $this->db->lastInsertId();
            $itemStmt = $this->db->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price, notes, created_at, updated_at) VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price, :total_price, :notes, NOW(), NOW())');

            foreach ($payload['items'] as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $fin = $this->db->prepare("INSERT INTO financial_entries (entry_type, source, source_id, description, category, payment_method, amount, status, entry_date, created_at, updated_at) VALUES ('receita', 'order', :source_id, :description, 'vendas', :payment_method, :amount, 'realizado', CURDATE(), NOW(), NOW())");
            $fin->execute([
                'source_id' => $orderId,
                'description' => 'Pedido #' . $orderId,
                'payment_method' => $payload['payment_method'],
                'amount' => $payload['total_amount'],
            ]);

            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }
}
