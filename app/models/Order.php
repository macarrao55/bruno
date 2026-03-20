<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class Order extends Model
{
    private static bool $lifecycleColumnsEnsured = false;

    public function all(array $filters = []): array
    {
        $this->ensureLifecycleColumns();

        $prepExpression = 'NULL';
        if ($this->hasColumn('orders', 'prep_started_at') && $this->hasColumn('orders', 'created_at')) {
            $prepExpression = 'TIMESTAMPDIFF(MINUTE, o.created_at, o.prep_started_at)';
        } elseif ($this->hasColumn('orders', 'ready_at') && $this->hasColumn('orders', 'created_at')) {
            $prepExpression = 'TIMESTAMPDIFF(MINUTE, o.created_at, o.ready_at)';
        }

        $deliveryExpression = 'NULL';
        if ($this->hasColumn('orders', 'delivered_at') && $this->hasColumn('orders', 'created_at')) {
            $deliveryExpression = 'TIMESTAMPDIFF(MINUTE, o.created_at, o.delivered_at)';
        }

        $where = [];
        $params = [];

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['order_type'] ?? '') !== '') {
            $where[] = 'o.order_type = :order_type';
            $params['order_type'] = $filters['order_type'];
        }
        if (($filters['payment_method'] ?? '') !== '') {
            $where[] = 'o.payment_method = :payment_method';
            $params['payment_method'] = $filters['payment_method'];
        }
        if (($filters['customer'] ?? '') !== '') {
            $where[] = 'c.name LIKE :customer';
            $params['customer'] = '%' . $filters['customer'] . '%';
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'DATE(o.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'DATE(o.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (($filters['date'] ?? '') !== '') {
            $where[] = 'DATE(o.created_at) = :date_exact';
            $params['date_exact'] = $filters['date'];
        }

        $sql = "SELECT o.*, c.name customer_name,
                       {$prepExpression} AS minutes_to_prep,
                       {$deliveryExpression} AS minutes_to_delivery
                FROM orders o
                LEFT JOIN customers c ON c.id = o.customer_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= " ORDER BY o.id DESC
                LIMIT 150";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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

        $this->db->beginTransaction();
        try {
            $orderId = $this->insertOrderWithFallback($payload);

            $itemColumns = ['order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price'];
            $itemValues = [':order_id', ':product_id', ':product_name', ':quantity', ':unit_price', ':total_price'];
            if ($this->hasColumn('order_items', 'unit_cost')) {
                $itemColumns[] = 'unit_cost';
                $itemValues[] = ':unit_cost';
            }
            if ($this->hasColumn('order_items', 'notes')) {
                $itemColumns[] = 'notes';
                $itemValues[] = ':notes';
            }
            if ($this->hasColumn('order_items', 'created_at')) {
                $itemColumns[] = 'created_at';
                $itemValues[] = 'NOW()';
            }
            if ($this->hasColumn('order_items', 'updated_at')) {
                $itemColumns[] = 'updated_at';
                $itemValues[] = 'NOW()';
            }
            $itemStmt = $this->db->prepare('INSERT INTO order_items (' . implode(',', $itemColumns) . ') VALUES (' . implode(',', $itemValues) . ')');

            $addonColumns = ['order_item_id', 'addon_name', 'addon_price'];
            $addonValues = [':order_item_id', ':addon_name', ':addon_price'];
            if ($this->hasColumn('order_item_addons', 'addon_id')) {
                $addonColumns[] = 'addon_id';
                $addonValues[] = ':addon_id';
            }
            if ($this->hasColumn('order_item_addons', 'created_at')) {
                $addonColumns[] = 'created_at';
                $addonValues[] = 'NOW()';
            }
            if ($this->hasColumn('order_item_addons', 'updated_at')) {
                $addonColumns[] = 'updated_at';
                $addonValues[] = 'NOW()';
            }
            $addonStmt = $this->db->prepare('INSERT INTO order_item_addons (' . implode(',', $addonColumns) . ') VALUES (' . implode(',', $addonValues) . ')');

            $recipeSql = 'SELECT si.id stock_item_id, pr.quantity_used FROM product_recipes pr INNER JOIN stock_items si ON si.id=pr.stock_item_id WHERE pr.product_id=:pid';
            if ($this->hasColumn('product_recipes', 'active')) {
                $recipeSql .= ' AND pr.active=1';
            }
            $recipeStmt = $this->db->prepare($recipeSql);
            $stockDownSql = 'UPDATE stock_items SET current_stock = current_stock - :qty';
            if ($this->hasColumn('stock_items', 'updated_at')) {
                $stockDownSql .= ', updated_at=NOW()';
            }
            $stockDownSql .= ' WHERE id=:sid';
            $stockDown = $this->db->prepare($stockDownSql);

            $stockMovColumns = ['stock_item_id', 'movement_type', 'quantity'];
            $stockMovValues = [':sid', '"saida"', ':qty'];
            if ($this->hasColumn('stock_movements', 'unit_cost')) {
                $stockMovColumns[] = 'unit_cost';
                $stockMovValues[] = ':unit_cost';
            }
            if ($this->hasColumn('stock_movements', 'notes')) {
                $stockMovColumns[] = 'notes';
                $stockMovValues[] = ':notes';
            }
            if ($this->hasColumn('stock_movements', 'reference_type')) {
                $stockMovColumns[] = 'reference_type';
                $stockMovValues[] = '"order"';
            }
            if ($this->hasColumn('stock_movements', 'reference_id')) {
                $stockMovColumns[] = 'reference_id';
                $stockMovValues[] = ':order_id';
            }
            if ($this->hasColumn('stock_movements', 'created_at')) {
                $stockMovColumns[] = 'created_at';
                $stockMovValues[] = 'NOW()';
            }
            if ($this->hasColumn('stock_movements', 'updated_at')) {
                $stockMovColumns[] = 'updated_at';
                $stockMovValues[] = 'NOW()';
            }
            $stockMov = $this->db->prepare('INSERT INTO stock_movements (' . implode(',', $stockMovColumns) . ') VALUES (' . implode(',', $stockMovValues) . ')');

            foreach ($payload['items'] as $item) {
                $itemParams = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ];
                if ($this->hasColumn('order_items', 'unit_cost')) {
                    $itemParams['unit_cost'] = $item['unit_cost'] ?? 0;
                }
                if ($this->hasColumn('order_items', 'notes')) {
                    $itemParams['notes'] = $item['notes'] ?? null;
                }
                $itemStmt->execute($itemParams);
                $orderItemId = (int)$this->db->lastInsertId();

                foreach (($item['addons'] ?? []) as $ad) {
                    $addonParams = [
                        'order_item_id' => $orderItemId,
                        'addon_name' => $ad['name'],
                        'addon_price' => $ad['price'],
                    ];
                    if ($this->hasColumn('order_item_addons', 'addon_id')) {
                        $addonParams['addon_id'] = $ad['id'];
                    }
                    $addonStmt->execute($addonParams);
                }

                $recipeStmt->execute(['pid' => $item['product_id']]);
                foreach ($recipeStmt->fetchAll() as $recipe) {
                    $q = (float)$recipe['quantity_used'] * (float)$item['quantity'];
                    $stockDown->execute(['qty' => $q, 'sid' => $recipe['stock_item_id']]);
                    $stockMovParams = ['sid' => $recipe['stock_item_id'], 'qty' => $q];
                    if ($this->hasColumn('stock_movements', 'unit_cost')) {
                        $stockMovParams['unit_cost'] = 0;
                    }
                    if ($this->hasColumn('stock_movements', 'notes')) {
                        $stockMovParams['notes'] = 'Baixa automática do pedido';
                    }
                    if ($this->hasColumn('stock_movements', 'reference_id')) {
                        $stockMovParams['order_id'] = $orderId;
                    }
                    $stockMov->execute($stockMovParams);
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
        $typeCol = $this->hasColumn('financial_entries', 'type') ? 'type' : ($this->hasColumn('financial_entries', 'entry_type') ? 'entry_type' : null);
        $sourceCol = $this->hasColumn('financial_entries', 'source') ? 'source' : null;
        $sourceIdCol = $this->hasColumn('financial_entries', 'source_id') ? 'source_id' : null;

        $fColumns = [];
        $fValues = [];
        $fParams = [];

        if ($typeCol) {
            $fColumns[] = $typeCol;
            $fValues[] = ':entry_type';
            $fParams['entry_type'] = 'entrada';
        }
        if ($sourceCol) {
            $fColumns[] = $sourceCol;
            $fValues[] = ':source';
            $fParams['source'] = 'pedido';
        }
        if ($sourceIdCol) {
            $fColumns[] = $sourceIdCol;
            $fValues[] = ':source_id';
            $fParams['source_id'] = $orderId;
        }

        $fColumns[] = 'description';
        $fValues[] = ':description';
        $fParams['description'] = 'Venda pedido #' . $orderId;

        if ($this->hasColumn('financial_entries', 'category')) {
            $fColumns[] = 'category';
            $fValues[] = ':category';
            $fParams['category'] = 'vendas';
        }

        $fColumns[] = 'payment_method';
        $fValues[] = ':payment_method';
        $fParams['payment_method'] = $payload['payment_method'];

        $fColumns[] = 'amount';
        $fValues[] = ':amount';
        $fParams['amount'] = $payload['total_amount'];

        if ($this->hasColumn('financial_entries', 'entry_date')) {
            $fColumns[] = 'entry_date';
            $fValues[] = 'CURDATE()';
        }
        if ($this->hasColumn('financial_entries', 'status')) {
            $fColumns[] = 'status';
            $fValues[] = ':status';
            $fParams['status'] = 'realizado';
        }
        if ($this->hasColumn('financial_entries', 'created_at')) {
            $fColumns[] = 'created_at';
            $fValues[] = 'NOW()';
        }
        if ($this->hasColumn('financial_entries', 'updated_at')) {
            $fColumns[] = 'updated_at';
            $fValues[] = 'NOW()';
        }

        $f = $this->db->prepare('INSERT INTO financial_entries (' . implode(',', $fColumns) . ') VALUES (' . implode(',', $fValues) . ')');
        $f->execute($fParams);

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

        $cashTypeCol = $this->hasColumn('cash_movements', 'type') ? 'type' : ($this->hasColumn('cash_movements', 'movement_type') ? 'movement_type' : null);
        $cmColumns = ['cash_register_id'];
        $cmValues = [':cash_register_id'];
        $cmParams = ['cash_register_id' => $payload['cash_register_id']];

        if ($cashTypeCol) {
            $cmColumns[] = $cashTypeCol;
            $cmValues[] = ':cash_type';
            $cmParams['cash_type'] = 'venda';
        }

        $cmColumns[] = 'payment_method';
        $cmValues[] = ':payment_method';
        $cmParams['payment_method'] = $payment;

        $cmColumns[] = 'amount';
        $cmValues[] = ':amount';
        $cmParams['amount'] = $payload['total_amount'];

        if ($this->hasColumn('cash_movements', 'description')) {
            $cmColumns[] = 'description';
            $cmValues[] = ':description';
            $cmParams['description'] = 'Venda pedido #' . $orderId;
        }
        if ($this->hasColumn('cash_movements', 'order_id')) {
            $cmColumns[] = 'order_id';
            $cmValues[] = ':order_id';
            $cmParams['order_id'] = $orderId;
        }
        if ($this->hasColumn('cash_movements', 'created_at')) {
            $cmColumns[] = 'created_at';
            $cmValues[] = 'NOW()';
        }
        if ($this->hasColumn('cash_movements', 'updated_at')) {
            $cmColumns[] = 'updated_at';
            $cmValues[] = 'NOW()';
        }

        $cm = $this->db->prepare('INSERT INTO cash_movements (' . implode(',', $cmColumns) . ') VALUES (' . implode(',', $cmValues) . ')');
        $cm->execute($cmParams);
    }

    private function updateCustomerAndLoyalty(array $payload, int $orderId): void
    {
        if (!$payload['customer_id']) return;

        $customerSets = [];
        if ($this->hasColumn('customers', 'total_spent')) {
            $customerSets[] = 'total_spent = total_spent + :t';
        }
        if ($this->hasColumn('customers', 'orders_count')) {
            $customerSets[] = 'orders_count = orders_count + 1';
        }
        if ($this->hasColumn('customers', 'updated_at')) {
            $customerSets[] = 'updated_at=NOW()';
        }
        if ($customerSets) {
            $u = $this->db->prepare('UPDATE customers SET ' . implode(', ', $customerSets) . ' WHERE id=:id');
            $u->execute(['t' => $payload['total_amount'], 'id' => $payload['customer_id']]);
        }

        $settings = new Settings();
        $loyaltyEnabled = $settings->get('loyalty_enabled', '1') === '1';
        if (!$loyaltyEnabled) {
            return;
        }

        $minOrderValue = (float)$settings->get('loyalty_min_order_value', '0');
        if ((float)$payload['total_amount'] < $minOrderValue) {
            return;
        }

        $ppr = (float)$settings->get('points_per_real', '1');
        $points = (int)floor($payload['total_amount'] * $ppr);
        if ($points <= 0) return;

        if (!$this->hasTable('loyalty_transactions')) {
            return;
        }

        $ltColumns = ['customer_id', 'order_id', 'points'];
        $ltValues = [':c', ':o', ':p'];
        $ltParams = ['c' => $payload['customer_id'], 'o' => $orderId, 'p' => $points];

        if ($this->hasColumn('loyalty_transactions', 'type')) {
            $ltColumns[] = 'type';
            $ltValues[] = ':type';
            $ltParams['type'] = 'credito';
        }
        if ($this->hasColumn('loyalty_transactions', 'description')) {
            $ltColumns[] = 'description';
            $ltValues[] = ':description';
            $ltParams['description'] = 'Pontos por pedido #' . $orderId;
        }
        if ($this->hasColumn('loyalty_transactions', 'created_at')) {
            $ltColumns[] = 'created_at';
            $ltValues[] = 'NOW()';
        }
        if ($this->hasColumn('loyalty_transactions', 'updated_at')) {
            $ltColumns[] = 'updated_at';
            $ltValues[] = 'NOW()';
        }

        $lt = $this->db->prepare('INSERT INTO loyalty_transactions (' . implode(',', $ltColumns) . ') VALUES (' . implode(',', $ltValues) . ')');
        $lt->execute($ltParams);
    }

    public function changeStatus(int $id, string $status): bool
    {
        $this->ensureLifecycleColumns();

        $sets = ['status=:s'];
        $params = ['s' => $status, 'id' => $id];

        if ($this->hasColumn('orders', 'updated_at')) {
            $sets[] = 'updated_at=NOW()';
        }

        if ($status === 'em preparo' && $this->hasColumn('orders', 'prep_started_at')) {
            $sets[] = 'prep_started_at=COALESCE(prep_started_at, NOW())';
        }

        if ($status === 'pronto') {
            if ($this->hasColumn('orders', 'prep_started_at')) {
                $sets[] = 'prep_started_at=COALESCE(prep_started_at, NOW())';
            }
            if ($this->hasColumn('orders', 'ready_at')) {
                $sets[] = 'ready_at=COALESCE(ready_at, NOW())';
            }
        }

        if ($status === 'entregue') {
            if ($this->hasColumn('orders', 'prep_started_at')) {
                $sets[] = 'prep_started_at=COALESCE(prep_started_at, NOW())';
            }
            if ($this->hasColumn('orders', 'ready_at')) {
                $sets[] = 'ready_at=COALESCE(ready_at, NOW())';
            }
            if ($this->hasColumn('orders', 'delivered_at')) {
                $sets[] = 'delivered_at=COALESCE(delivered_at, NOW())';
            }
        }

        $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id=:id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    private function insertOrderWithFallback(array $payload): int
    {
        $userCandidates = ['user_id', 'created_by', 'operator_id'];
        $changeCandidates = ['change_amount', 'change_for'];

        $last = null;
        foreach ($userCandidates as $userColumn) {
            foreach ([null, ...$changeCandidates] as $changeColumn) {
                try {
                    return $this->insertOrder($payload, $userColumn, $changeColumn);
                } catch (\PDOException $e) {
                    $msg = $e->getMessage();
                    if (str_contains($msg, "Unknown column")) {
                        $last = $e;
                        continue;
                    }
                    throw $e;
                }
            }
        }

        if ($last) {
            throw $last;
        }

        throw new \RuntimeException('Não foi possível inserir pedido com o schema atual.');
    }

    private function insertOrder(array $payload, string $userColumn, ?string $changeColumn): int
    {
        $columns = ['customer_id', $userColumn, 'order_type', 'status', 'payment_method', 'subtotal', 'discount_amount', 'delivery_fee', 'total_amount'];
        $values = [':customer_id', ':user_ref', ':order_type', '"novo"', ':payment_method', ':subtotal', ':discount', ':delivery', ':total'];
        $params = [
            'customer_id' => $payload['customer_id'] ?: null,
            'user_ref' => $payload['user_id'],
            'order_type' => $payload['order_type'],
            'payment_method' => $payload['payment_method'],
            'subtotal' => $payload['subtotal'],
            'discount' => $payload['discount_amount'],
            'delivery' => $payload['delivery_fee'],
            'total' => $payload['total_amount'],
        ];

        if ($changeColumn) {
            $columns[] = $changeColumn;
            $values[] = ':change_amount';
            $params['change_amount'] = $payload['change_amount'];
        }

        if ($this->hasColumn('orders', 'notes')) {
            $columns[] = 'notes';
            $values[] = ':notes';
            $params['notes'] = $payload['notes'];
        }

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
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
        $stmt->execute(['t' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function ensureLifecycleColumns(): void
    {
        if (self::$lifecycleColumnsEnsured || !$this->hasTable('orders')) {
            return;
        }

        $columns = ['prep_started_at', 'ready_at', 'delivered_at'];
        foreach ($columns as $column) {
            if ($this->hasColumn('orders', $column)) {
                continue;
            }

            try {
                $this->db->exec("ALTER TABLE orders ADD COLUMN {$column} DATETIME NULL");
            } catch (\Throwable) {
                // Se não for possível alterar schema, seguimos sem bloquear o fluxo.
            }
        }

        self::$lifecycleColumnsEnsured = true;
    }
}
