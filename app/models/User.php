<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT u.*, r.name AS role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.email = :email AND u.active = 1 AND u.deleted_at IS NULL LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        $user['permissions'] = $this->getPermissions((int) $user['id'], (int) $user['role_id']);
        return $user;
    }

    public function all(): array
    {
        return $this->db->query('SELECT u.id, u.name, u.email, u.active, r.name AS role_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.deleted_at IS NULL ORDER BY u.id DESC')->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO users (role_id, name, email, password, active, created_at, updated_at) VALUES (:role_id, :name, :email, :password, :active, NOW(), NOW())');
        return $stmt->execute($data);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'password' => $hash]);
    }

    public function toggleStatus(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET active = :active, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'active' => $active ? 1 : 0]);
    }

    private function getPermissions(int $userId, int $roleId): array
    {
        $permissions = [];
        $stmt = $this->db->prepare('SELECT p.slug FROM permissions p INNER JOIN role_permissions rp ON rp.permission_id = p.id WHERE rp.role_id = :role_id');
        $stmt->execute(['role_id' => $roleId]);
        $permissions = array_column($stmt->fetchAll(), 'slug');

        $stmt2 = $this->db->prepare('SELECT p.slug FROM permissions p INNER JOIN user_permissions up ON up.permission_id = p.id WHERE up.user_id = :user_id');
        $stmt2->execute(['user_id' => $userId]);
        $extra = array_column($stmt2->fetchAll(), 'slug');

        return array_values(array_unique(array_merge($permissions, $extra)));
    }
}
