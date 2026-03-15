<?php
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function money(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function userNameById(int $id): string
{
    $stmt = db()->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['nome'] ?? 'Desconhecido';
}

function roleLabel(string $nivel): string
{
    $map = [
        'administrador' => 'Administrador',
        'gerente' => 'Gerente',
        'caixa' => 'Caixa',
        'vendedor' => 'Vendedor',
        'entregador' => 'Entregador',
    ];
    return $map[$nivel] ?? ucfirst($nivel);
}

/**
 * Verifica se a coluna existe na tabela (compatibilidade com bancos antigos).
 */
function tableHasColumn(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    try {
        $stmt = db()->prepare('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([DB_NAME, $table, $column]);
        $cache[$key] = ((int) ($stmt->fetch()['c'] ?? 0)) > 0;
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}


/**
 * Recupera configuração dinâmica salva no banco.
 */
function getSetting(string $key, ?string $default = null): ?string
{
    static $cache = [];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    if (!tableExists('configuracoes')) {
        $cache[$key] = $default;
        return $cache[$key];
    }

    try {
        $stmt = db()->prepare('SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row['valor'] ?? $default;
    } catch (Throwable $e) {
        $cache[$key] = $default;
    }

    return $cache[$key];
}

function getSettingBool(string $key, bool $default = false): bool
{
    $value = getSetting($key, $default ? '1' : '0');
    return in_array(strtolower((string) $value), ['1', 'true', 'sim', 'yes', 'on'], true);
}

function tableExists(string $table): bool
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = db()->prepare('SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
        $stmt->execute([DB_NAME, $table]);
        $cache[$table] = ((int) ($stmt->fetch()['c'] ?? 0)) > 0;
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}
