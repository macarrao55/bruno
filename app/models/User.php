<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, r.name AS role_name FROM users u INNER JOIN roles r ON r.id=u.role_id WHERE u.email=:email AND u.active=1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    public function all(): array
    {
        return $this->db->query('SELECT u.id,u.name,u.email,u.active,r.name AS role_name FROM users u INNER JOIN roles r ON r.id=u.role_id ORDER BY u.id DESC')->fetchAll();
    }

    public function roles(): array
    {
        return $this->db->query('SELECT id,name FROM roles WHERE active=1 ORDER BY id')->fetchAll();
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO users (role_id,name,email,password,active,created_at) VALUES (:role_id,:name,:email,:password,:active,NOW())');
        return $stmt->execute($data);
    }
}
