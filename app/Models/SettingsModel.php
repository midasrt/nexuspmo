<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key_name', 'value'];

    public function getSetting($key, $default = null)
    {
        $row = $this->where('key_name', $key)->first();
        return $row ? $row['value'] : $default;
    }

    public function setSetting($key, $value)
    {
        $row = $this->where('key_name', $key)->first();
        if ($row) {
            $this->update($row['id'], ['value' => (string)$value]);
        } else {
            $this->insert(['key_name' => $key, 'value' => (string)$value]);
        }
    }
}
