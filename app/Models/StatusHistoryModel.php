<?php

namespace App\Models;

use CodeIgniter\Model;

class StatusHistoryModel extends Model
{
    protected $table            = 'project_status_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'date', 'status', 'note'
    ];
}
