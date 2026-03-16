<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class Order extends Model
{
    public function all(): array
    {
        $sql = 'SELECT o.*, c.name customer_name FROM orders o LEFT JOIN customers c ON c.id=o.customer_id ORDER BY o.id DESC LIMIT 150';
        return $this->db->query($sql)->fetchAll();
    }

    public function findWithItems(int $id): ?array
    {
        $phoneCol = $this->hasColumn('customers', 'phone') ? 'phone' : 'phone_main';
        $addressCol = $this->hasColumn('customers', 'address') ? 'address' : 'endereco';
        $neighborhoodCol = $this->hasColumn('customers', 'neighborhood') ? 'neighborhood' : 'bairro';

        $stmt = $this->db->prepare("SELECT o.*, c.name customer_name, c.{$phoneCol} AS phone, c.{$addressCol} AS address, c.{$neighborhoodCol} AS neighborhood
                                    FROM orders o
                                    LEFT JOIN customers c ON c.id=o.customer_id
                                    WHERE o.id=:id");
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $i = $this->db->prepare('SELECT * FROM order_items WHERE order_id=:id');
        $i->execute(['id' => $id]);
        $items = $i->fetchAll();
        foreach ($items as &$item) {
            $a = $this->db->prepare('SELECT * FROM order_item_addons WHERE order_item_id=:ii');
            $a->execute(['ii' => $item['id']]);
            $item['addons'] = $a->fetchAll();
        }
        $order['items'] = $items;
        return $order;
    }

    public function createFromPdv(array $payload): int
    {
        $this->validatePayload($payload);

        $userColumn = $this->resolveOrdersUserColumn();
        $changeColumn = $this->hasColumn('orders', 'change_amount') ? 'change_amount' : ($this->hasColumn('orders', 'change_for') ? 'change_for' : null);

        $this->db->beginTransaction();
        try {
            $columns = ['customer_id', $userColumn, 'order_type', 'status', 'payment_method', 'subtotal', 'discount_amount', 'delivery_fee', 'total_amount'];
            $values = [':customer_id', ':user_ref', ':order_type', '"novo"', ':payment_method', ':subtotal', ':discount', ':delivery', ':total'];

            if ($changeColumn) {
                $columns[] = $changeColumn;
                $values[] = ':change_amount';
            }

            $columns[] = 'notes';
            $values[] = ':notes';

            if ($this->hasColumn('orders', 'created_at')) {
                $columns[] = 'created_at';
                $values[] = 'NOW()';
            }
            if ($this->hasColumn('orders', 'updated_at')) {
                $columns[] = 'updated_at';
                $values[] = 'NOW()';
            }

            $sql = 'INSERT INTO orders (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ')';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'customer_id' => $payload['customer_id'] ?: null,
                'user_ref' => $payload['user_id'],
                'order_type' => $payload['order_type'],
                'payment_method' => $payload['payment_method'],
                'subtotal' => $payload['subtotal'],
                'discount' => $payload['discount_amount'],
                'delivery' => $payload['delivery_fee'],
                'total' => $payload['total_amount'],
                'change_amount' => $payload['change_amount'],
                'notes' => $payload['notes'],
            ]);
            $orderId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare('INSERT INTO order_items (order_id,product_id,product_name,quantity,unit_price,unit_cost,total_price,notes,created_at,updated_at) VALUES (:order_id,:product_id,:product_name,:quantity,:unit_price,:unit_cost,:total_price,:notes,NOW(),NOW())');
            $addonStmt = $this->db->prepare('INSERT INTO order_item_addons (order_item_id,addon_id,addon_name,addon_price,created_at,updated_at) VALUES (:order_item_id,:addon_id,:addon_name,:addon_price,NOW(),NOW())');
            $recipeStmt = $this->db->prepare('SELECT si.id stock_item_id, pr.quantity_used FROM product_recipes pr INNER JOIN stock_items si ON si.id=pr.stock_item_id WHERE pr.product_id=:pid AND pr.active=1');
            $stockDown = $this->db->prepare('UPDATE stock_items SET current_stock = current_stock - :qty, updated_at=NOW() WHERE id=:sid');
            $stockMov = $this->db->prepare('INSERT INTO stock_movements (stock_item_id,movement_type,quantity,unit_cost,notes,reference_type,reference_id,created_at,updated_at) VALUES (:sid,"saida",:qty,0,:notes,"order",:order_id,NOW(),NOW())');

            foreach ($payload['items'] as $item) {
                $itemStmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'total_price' => $item['total_price'],
                    'notes' => $item['notes'] ?? null,
                ]);
                $orderItemId = (int)$this->db->lastInsertId();

                foreach (($item['addons'] ?? []) as $ad) {
                    $addonStmt->execute([
                        'order_item_id' => $orderItemId,
                        'addon_id' => $ad['id'],
                        'addon_name' => $ad['name'],
                        'addon_price' => $ad['price'],
                    ]);
                }

                if ((int)$item['controls_stock'] === 1) {
                    $recipeStmt->execute(['pid' => $item['product_id']]);
                    foreach ($recipeStmt->fetchAll() as $recipe) {
                        $q = (float)$recipe['quantity_used'] * (float)$item['quantity'];
                        $stockDown->execute(['qty' => $q, 'sid' => $recipe['stock_item_id']]);
                        $stockMov->execute(['sid' => $recipe['stock_item_id'], 'qty' => $q, 'notes' => 'Baixa automática do pedido', 'order_id' => $orderId]);
                    }
                }
            }

            $this->createFinancialAndCash($payload, $orderId);
            $this->updateCustomerAndLoyalty($payload, $orderId);

            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validatePayload(array $payload): void
    {
        if (($payload['total_amount'] ?? 0) <= 0) {
            throw new \InvalidArgumentException('Total da venda inválido.');
        }

        if (empty($payload['items']) || !is_array($payload['items'])) {
            throw new \InvalidArgumentException('Pedido sem itens.');
        }

        foreach ($payload['items'] as $item) {
            if (($item['quantity'] ?? 0) <= 0) {
                throw new \InvalidArgumentException('Item com quantidade inválida.');
            }
        }
    }

    private function createFinancialAndCash(array $payload, int $orderId): void
    {
        $f = $this->db->prepare('INSERT INTO financial_entries (type,source,source_id,description,category,payment_method,amount,entry_date,status,created_at,updated_at) VALUES ("entrada","pedido",:sid,:d,"vendas",:pm,:a,CURDATE(),"realizado",NOW(),NOW())');
        $f->execute(['sid' => $orderId, 'd' => 'Venda pedido #' . $orderId, 'pm' => $payload['payment_method'], 'a' => $payload['total_amount']]);

        if ($payload['payment_method'] === 'crediario') {
            $dueDays = (int)(new Settings())->get('crediario_due_days', '30');
            $ar = $this->db->prepare('INSERT INTO accounts_receivable (customer_id,order_id,total_amount,paid_amount,due_date,status,created_at,updated_at) VALUES (:c,:o,:t,0,DATE_ADD(CURDATE(), INTERVAL :dd DAY),"pendente",NOW(),NOW())');
            $ar->execute(['c' => $payload['customer_id'], 'o' => $orderId, 't' => $payload['total_amount'], 'dd' => $dueDays]);
            return;
        }

        if (!$payload['cash_register_id']) return;
        $pixInCash = (new Settings())->get('pix_entra_no_caixa', '1') === '1';
        $payment = $payload['payment_method'];
        if ($payment === 'pix' && !$pixInCash) return;

        $cm = $this->db->prepare('INSERT INTO cash_movements (cash_register_id,type,payment_method,amount,description,order_id,created_at,updated_at) VALUES (:r,"venda",:p,:a,:d,:o,NOW(),NOW())');
        $cm->execute(['r' => $payload['cash_register_id'], 'p' => $payment, 'a' => $payload['total_amount'], 'd' => 'Venda pedido #' . $orderId, 'o' => $orderId]);
    }

    private function updateCustomerAndLoyalty(array $payload, int $orderId): void
    {
        if (!$payload['customer_id']) return;

        $u = $this->db->prepare('UPDATE customers SET total_spent = total_spent + :t, orders_count = orders_count + 1, updated_at=NOW() WHERE id=:id');
        $u->execute(['t' => $payload['total_amount'], 'id' => $payload['customer_id']]);

        $ppr = (float)(new Settings())->get('points_per_real', '1');
        $points = (int)floor($payload['total_amount'] * $ppr);
        if ($points <= 0) return;

        $lt = $this->db->prepare('INSERT INTO loyalty_transactions (customer_id,order_id,points,type,description,created_at,updated_at) VALUES (:c,:o,:p,"credito",:d,NOW(),NOW())');
        $lt->execute(['c' => $payload['customer_id'], 'o' => $orderId, 'p' => $points, 'd' => 'Pontos por pedido #' . $orderId]);
    }

    public function changeStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:id');
        return $stmt->execute(['s' => $status, 'id' => $id]);
    }

    private function resolveOrdersUserColumn(): string
    {
        if ($this->hasColumn('orders', 'user_id')) return 'user_id';
        if ($this->hasColumn('orders', 'created_by')) return 'created_by';
        if ($this->hasColumn('orders', 'operator_id')) return 'operator_id';
        return 'user_id';
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
