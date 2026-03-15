<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Settings extends Model
{
    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM system_settings WHERE setting_key=:k LIMIT 1');
        $stmt->execute(['k' => $key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string)$v : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare('INSERT INTO system_settings (setting_key,setting_value,created_at,updated_at) VALUES (:k,:v,NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()');
        $stmt->execute(['k' => $key, 'v' => $value]);
    }
}
