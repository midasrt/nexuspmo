<?php

namespace App\Models;

use CodeIgniter\Model;

class ActionItemModel extends Model
{
    protected $table            = 'project_action_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'project_id', 'title', 'owner', 'due', 'done', 'resolved_date'
    ];
}
