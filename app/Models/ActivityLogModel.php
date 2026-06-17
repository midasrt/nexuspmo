<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table            = 'user_activity_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'user_name', 'user_role',
        'action', 'module', 'target_id', 'target_name',
        'description', 'ip_address', 'created_at',
    ];
    protected $useTimestamps    = false; // managed manually
}
