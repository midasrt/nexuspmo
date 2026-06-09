<?php

namespace App\Models;

use CodeIgniter\Model;

class DependencyModel extends Model
{
    protected $table            = 'project_dependencies';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'dep_project_id', 'dep_project_name', 'type', 'status'
    ];
}
