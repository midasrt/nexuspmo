<?php

namespace App\Models;

use CodeIgniter\Model;

class PhaseModel extends Model
{
    protected $table            = 'project_phases';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'name', 'description', 'start', 'end', 'status', 'sequence'
    ];
}
