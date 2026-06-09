<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectDetailedHistoryModel extends Model
{
    protected $table            = 'project_detailed_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'activity', 'old_state', 'new_state', 'date', 'created_at'
    ];
}
