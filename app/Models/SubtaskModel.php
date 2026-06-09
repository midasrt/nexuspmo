<?php

namespace App\Models;

use CodeIgniter\Model;

class SubtaskModel extends Model
{
    protected $table            = 'project_phase_subtasks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'phase_id', 'name', 'description', 'start', 'end', 'status', 'sequence'
    ];
}
